'use strict';

require('dotenv').config();

const http = require('http');
const crypto = require('crypto');
const { Server } = require('socket.io');
const { createAdapter } = require('@socket.io/redis-adapter');
const Redis = require('ioredis');
const axios = require('axios');

const PORT = Number.parseInt(process.env.PORT ?? '6001', 10);
const HOST = process.env.HOST ?? '0.0.0.0';
const LARAVEL_URL = (process.env.LARAVEL_URL ?? 'http://127.0.0.1:8000').replace(/\/$/, '');
const REDIS_URL = process.env.REDIS_URL ?? 'redis://127.0.0.1:6379';
const REDIS_KEY_PREFIX = process.env.REDIS_KEY_PREFIX ?? 'tracking:';
const AUTH_CACHE_TTL = Number.parseInt(process.env.AUTH_CACHE_TTL_SECONDS ?? '45', 10);
const TRIP_AUTH_CACHE_TTL = Number.parseInt(process.env.TRIP_AUTH_CACHE_TTL_SECONDS ?? '30', 10);
const HTTP_TIMEOUT_MS = Number.parseInt(process.env.HTTP_TIMEOUT_MS ?? '3500', 10);
const LOC_MIN_INTERVAL_MS = Number.parseInt(process.env.LOC_MIN_INTERVAL_MS ?? '400', 10);
const MAX_HTTP_BUFFER_SIZE = Number.parseInt(process.env.MAX_HTTP_BUFFER_SIZE ?? '512', 10);
const INTERNAL_SECRET = process.env.TRACKING_INTERNAL_SECRET ?? '';

const corsOriginsEnv = (process.env.CORS_ORIGINS ?? '').trim();
const cors =
  corsOriginsEnv.length === 0
    ? { origin: true, credentials: true }
    : { origin: corsOriginsEnv.split(',').map((s) => s.trim()), credentials: true };

function writeJson(res, statusCode, payload) {
  res.writeHead(statusCode, { 'Content-Type': 'application/json; charset=utf-8' });
  res.end(JSON.stringify(payload));
}

function broadcastLocation({ userId, tripId, coords, excludeSocket = null }) {
  const now = Date.now();
  const msg = { u: userId, la: coords.la, lo: coords.lo, t: now };
  if (excludeSocket !== null) {
    excludeSocket.broadcast.to(`trip:${tripId}`).emit('loc', msg);
    return;
  }
  io.to(`trip:${tripId}`).emit('loc', msg);
}

function parseJsonBody(req) {
  return new Promise((resolve, reject) => {
    let raw = '';
    req.on('data', (chunk) => {
      raw += chunk;
      if (raw.length > MAX_HTTP_BUFFER_SIZE * 4) {
        reject(new Error('payload_too_large'));
      }
    });
    req.on('end', () => {
      try {
        resolve(raw.length === 0 ? {} : JSON.parse(raw));
      } catch {
        reject(new Error('bad_json'));
      }
    });
    req.on('error', reject);
  });
}

const httpServer = http.createServer(async (req, res) => {
  if (req.method === 'GET' && req.url === '/health') {
    writeJson(res, 200, { ok: true });
    return;
  }

  if (req.method === 'POST' && req.url === '/internal/emit-loc') {
    if (!INTERNAL_SECRET) {
      writeJson(res, 503, { ok: false, e: 'missing_secret' });
      return;
    }

    const incomingSecret = req.headers['x-tracking-secret'];
    if (incomingSecret !== INTERNAL_SECRET) {
      writeJson(res, 401, { ok: false, e: 'unauthorized' });
      return;
    }

    try {
      const body = await parseJsonBody(req);
      const tripId = parseTripId(body.trip_id ?? body.tripId);
      const userId = parseTripId(body.user_id ?? body.userId);
      const coords = parseLatLng(body.la ?? body.lat, body.lo ?? body.lng ?? body.lon);

      if (tripId === null || userId === null || coords === null) {
        writeJson(res, 422, { ok: false, e: 'invalid_payload' });
        return;
      }

      broadcastLocation({ userId, tripId, coords });
      writeJson(res, 200, { ok: true });
      return;
    } catch (err) {
      if (err instanceof Error && err.message === 'payload_too_large') {
        writeJson(res, 413, { ok: false, e: 'payload_too_large' });
        return;
      }
      writeJson(res, 400, { ok: false, e: 'bad_json' });
      return;
    }
  }

  res.writeHead(200, { 'Content-Type': 'text/plain; charset=utf-8' });
  res.end('tourky-tracking-service');
});

const io = new Server(httpServer, {
  cors,
  serveClient: false,
  maxHttpBufferSize: MAX_HTTP_BUFFER_SIZE,
  connectTimeout: 10_000,
  pingTimeout: 20_000,
  pingInterval: 25_000,
  transports: ['websocket', 'polling'],
  allowEIO3: false,
  perMessageDeflate: {
    threshold: 256,
    zlibDeflateOptions: { level: 5 },
  },
});

const pubClient = new Redis(REDIS_URL, { lazyConnect: true });
const subClient = pubClient.duplicate();
const cacheRedis = new Redis(REDIS_URL, { lazyConnect: true });

const api = axios.create({
  baseURL: LARAVEL_URL,
  timeout: HTTP_TIMEOUT_MS,
  validateStatus: (s) => s < 500,
});

function hashToken(token) {
  return crypto.createHash('sha256').update(token, 'utf8').digest('hex');
}

function bearerHeaders(token) {
  return { Authorization: `Bearer ${token}`, Accept: 'application/json' };
}

async function getAuthPayload(token) {
  const key = `${REDIS_KEY_PREFIX}auth:${hashToken(token)}`;
  const cached = await cacheRedis.get(key);
  if (cached) {
    try {
      return JSON.parse(cached);
    } catch {
      await cacheRedis.del(key);
    }
  }

  const res = await api.get('/api/tracking/socket/me', { headers: bearerHeaders(token) });
  if (res.status !== 200 || !res.data?.id || !res.data?.type) {
    return null;
  }

  const payload = { id: res.data.id, type: res.data.type };
  await cacheRedis.setex(key, AUTH_CACHE_TTL, JSON.stringify(payload));
  return payload;
}

async function canAccessTrip(token, userId, tripId) {
  const tripKey = `${REDIS_KEY_PREFIX}trip:${userId}:${tripId}`;
  const hit = await cacheRedis.get(tripKey);
  if (hit === '1') {
    return true;
  }
  if (hit === '0') {
    return false;
  }

  const res = await api.post(
    '/api/tracking/socket/trip-authorize',
    { trip_id: tripId },
    { headers: { ...bearerHeaders(token), 'Content-Type': 'application/json' } },
  );

  if (res.status !== 200 || typeof res.data?.allowed !== 'boolean') {
    return false;
  }

  await cacheRedis.setex(tripKey, TRIP_AUTH_CACHE_TTL, res.data.allowed ? '1' : '0');
  return res.data.allowed;
}

function parseTripId(value) {
  const n = typeof value === 'string' ? Number.parseInt(value, 10) : Number(value);
  if (!Number.isFinite(n) || n <= 0) {
    return null;
  }
  return n;
}

function parseLatLng(la, lo) {
  const lat = typeof la === 'string' ? Number.parseFloat(la) : Number(la);
  const lng = typeof lo === 'string' ? Number.parseFloat(lo) : Number(lo);
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
    return null;
  }
  if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
    return null;
  }
  return { la: roundCoord(lat), lo: roundCoord(lng) };
}

function roundCoord(x) {
  return Math.round(x * 1e6) / 1e6;
}

io.use(async (socket, next) => {
  const rawAuth = socket.handshake.auth;
  const header = socket.handshake.headers.authorization;
  const token =
    (typeof rawAuth?.token === 'string' && rawAuth.token) ||
    (typeof header === 'string' && header.startsWith('Bearer ') ? header.slice(7) : null);

  if (!token || token.length > 2048) {
    return next(new Error('auth_required'));
  }

  try {
    const user = await getAuthPayload(token);
    if (!user) {
      return next(new Error('unauthorized'));
    }
    socket.data.token = token;
    socket.data.user = user;
    socket.data.trips = new Set();
    socket.data.locThrottle = new Map();
    socket.data.lastBroadcast = new Map();
    return next();
  } catch {
    return next(new Error('auth_upstream'));
  }
});

io.on('connection', (socket) => {
  socket.on('disconnect', () => {
    socket.data.locThrottle?.clear();
    socket.data.lastBroadcast?.clear();
    socket.data.trips?.clear();
    delete socket.data.token;
  });

  socket.on('trip:join', async (payload, ack) => {
    const tripId = parseTripId(payload?.trip_id ?? payload?.tripId);
    if (tripId === null) {
      if (typeof ack === 'function') {
        ack({ ok: false, e: 'bad_trip' });
      }
      return;
    }

    try {
      const allowed = await canAccessTrip(socket.data.token, socket.data.user.id, tripId);
      if (!allowed) {
        if (typeof ack === 'function') {
          ack({ ok: false, e: 'forbidden' });
        }
        return;
      }
      const room = `trip:${tripId}`;
      await socket.join(room);
      socket.data.trips.add(tripId);
      if (typeof ack === 'function') {
        ack({ ok: true, trip_id: tripId });
      }
    } catch {
      if (typeof ack === 'function') {
        ack({ ok: false, e: 'upstream' });
      }
    }
  });

  socket.on('trip:leave', async (payload, ack) => {
    const tripId = parseTripId(payload?.trip_id ?? payload?.tripId);
    if (tripId === null) {
      if (typeof ack === 'function') {
        ack({ ok: false });
      }
      return;
    }
    await socket.leave(`trip:${tripId}`);
    socket.data.trips.delete(tripId);
    if (typeof ack === 'function') {
      ack({ ok: true });
    }
  });

  /**
   * Compact location broadcast (keys shortened for bandwidth).
   * payload: { trip_id, la, lo } (aliases: lat/lng accepted)
   */
  socket.on('loc', (payload) => {
    if (socket.data.user.type !== 'captain') {
      return;
    }

    const tripId = parseTripId(payload?.trip_id ?? payload?.tripId);
    if (tripId === null || !socket.data.trips.has(tripId)) {
      return;
    }

    const la = payload?.la ?? payload?.lat;
    const lo = payload?.lo ?? payload?.lng ?? payload?.lon;
    const coords = parseLatLng(la, lo);
    if (!coords) {
      return;
    }

    const throttleKey = tripId;
    const now = Date.now();
    const last = socket.data.locThrottle.get(throttleKey) ?? 0;
    if (now - last < LOC_MIN_INTERVAL_MS) {
      return;
    }
    socket.data.locThrottle.set(throttleKey, now);

    const dedupeKey = `${tripId}`;
    const prev = socket.data.lastBroadcast.get(dedupeKey);
    if (prev && prev.la === coords.la && prev.lo === coords.lo && now - prev.t < 5000) {
      return;
    }
    socket.data.lastBroadcast.set(dedupeKey, { ...coords, t: now });

    broadcastLocation({ userId: socket.data.user.id, tripId, coords, excludeSocket: socket });
  });
});

async function bootstrap() {
  await Promise.all([pubClient.connect(), subClient.connect(), cacheRedis.connect()]);
  io.adapter(createAdapter(pubClient, subClient));

  httpServer.listen(PORT, HOST, () => {
    console.log(`tracking-service listening on ${HOST}:${PORT} (laravel: ${LARAVEL_URL})`);
  });
}

function shutdown() {
  io.close(() => {
    Promise.all([pubClient.quit(), subClient.quit(), cacheRedis.quit()])
      .catch(() => {})
      .finally(() => process.exit(0));
  });
}

process.on('SIGTERM', shutdown);
process.on('SIGINT', shutdown);

bootstrap().catch((err) => {
  console.error(err);
  process.exit(1);
});
