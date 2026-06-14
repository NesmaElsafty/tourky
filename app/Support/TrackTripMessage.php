<?php

namespace App\Support;

use App\Http\Resources\CaptainTrackTripResource;
use App\Models\TrackTrip;

class TrackTripMessage
{
    public static function display(?TrackTrip $trackTrip, string $locale): ?string
    {
        if ($trackTrip === null) {
            return null;
        }

        if (CaptainTrackTripResource::parseLocation($trackTrip->message) !== null) {
            return null;
        }

        $rejectReason = self::parseRejectReason($trackTrip->message);
        if ($rejectReason !== null) {
            return self::rejectMessage($trackTrip, $locale, $rejectReason);
        }

        if ($trackTrip->client_id !== null) {
            return self::clientConfirmedMessage($trackTrip, $locale);
        }

        if ($trackTrip->point_id !== null) {
            return self::arrivalMessage($trackTrip, $locale);
        }

        return $trackTrip->message;
    }

    private static function parseRejectReason(?string $message): ?string
    {
        if ($message === null || $message === '') {
            return null;
        }

        $decoded = json_decode($message, true);
        if (! is_array($decoded) || ($decoded['log'] ?? null) !== 'reject') {
            return null;
        }

        $reason = trim((string) ($decoded['reason'] ?? ''));

        return $reason !== '' ? $reason : null;
    }

    private static function arrivalMessage(TrackTrip $trackTrip, string $locale): string
    {
        $trackTrip->loadMissing(['captain:id,name', 'point:id,name_en,name_ar']);

        return trans('api.track_trips.message_arrival', [
            'captain' => $trackTrip->captain?->name ?? '',
            'point' => self::pointName($trackTrip, $locale),
            'at' => self::timestamp($trackTrip),
        ], $locale);
    }

    private static function clientConfirmedMessage(TrackTrip $trackTrip, string $locale): string
    {
        $trackTrip->loadMissing(['captain:id,name', 'client:id,name', 'point:id,name_en,name_ar']);

        return trans('api.track_trips.message_pickup', [
            'captain' => $trackTrip->captain?->name ?? '',
            'client' => $trackTrip->client?->name ?? '',
            'point' => self::pointName($trackTrip, $locale),
            'at' => self::timestamp($trackTrip),
        ], $locale);
    }

    private static function rejectMessage(TrackTrip $trackTrip, string $locale, string $reason): string
    {
        $trackTrip->loadMissing(['captain:id,name', 'client:id,name', 'point:id,name_en,name_ar']);

        return trans('api.track_trips.message_reject', [
            'captain' => $trackTrip->captain?->name ?? '',
            'client' => $trackTrip->client?->name ?? '',
            'reason' => $reason,
            'point' => self::pointName($trackTrip, $locale),
            'at' => self::timestamp($trackTrip),
        ], $locale);
    }

    private static function pointName(TrackTrip $trackTrip, string $locale): string
    {
        $point = $trackTrip->point;
        $name = $locale === 'ar'
            ? ($point?->name_ar ?: $point?->name_en)
            : ($point?->name_en ?: $point?->name_ar);

        return $name ?? (string) ($trackTrip->point_id ?? '');
    }

    private static function timestamp(TrackTrip $trackTrip): string
    {
        return $trackTrip->created_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s');
    }
}
