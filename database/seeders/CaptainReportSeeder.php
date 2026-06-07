<?php

namespace Database\Seeders;

use App\Models\CaptainReport;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;

class CaptainReportSeeder extends Seeder
{
    private const CLIENT_CANCELLATIONS = 15;

    private const CAPTAIN_REJECTIONS = 15;

    /** Share of captain rejection reports that already have a single admin reply. */
    private const CAPTAIN_REPLIED_RATIO = 0.45;

    /**
     * @var list<int>
     */
    private array $usedReservationIds = [];

    public function run(): void
    {
        $admin = User::query()
            ->where('type', 'admin')
            ->orderBy('id')
            ->first();

        if ($admin === null) {
            $this->command?->warn('CaptainReportSeeder skipped: no admin user found for seeded replies.');

            return;
        }

        $clientCreated = $this->seedClientCancellations();
        $captainCreated = $this->seedCaptainRejections($admin);

        $this->command?->info(sprintf(
            'CaptainReportSeeder: %d client cancellation(s), %d captain rejection(s).',
            $clientCreated,
            $captainCreated,
        ));
    }

    private function seedClientCancellations(): int
    {
        $created = 0;

        $candidates = Reservation::query()
            ->whereNotNull('trip_id')
            ->whereNotNull('trip_car_id')
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereNotIn('id', $this->usedReservationIds)
            ->with('tripCar:id,captain_id')
            ->inRandomOrder()
            ->limit(self::CLIENT_CANCELLATIONS * 4)
            ->get();

        foreach ($candidates as $reservation) {
            if ($created >= self::CLIENT_CANCELLATIONS) {
                break;
            }

            if ($this->reservationAlreadyReported($reservation->id)) {
                continue;
            }

            CaptainReport::query()->create([
                'type' => CaptainReport::TYPE_CLIENT,
                'reservation_id' => $reservation->id,
                'trip_id' => $reservation->trip_id,
                'captain_id' => $reservation->tripCar?->captain_id,
                'message' => fake()->randomElement([
                    'I will not be able to attend the trip due to an urgent personal matter.',
                    'My plans changed and I need to cancel this booking.',
                    'I booked the wrong pickup time by mistake.',
                    'لن أتمكن من حضور الرحلة بسبب ظرف طارئ.',
                    'تغيرت خططي ومحتاج ألغي الحجز.',
                    'حجزت وقت الالتقاط غلط بالغلط.',
                ]),
            ]);

            $reservation->update(['status' => 'cancelled']);
            $this->usedReservationIds[] = (int) $reservation->id;
            $created++;
        }

        return $created;
    }

    private function seedCaptainRejections(User $admin): int
    {
        $created = 0;

        $candidates = Reservation::query()
            ->whereNotNull('trip_id')
            ->whereNotNull('trip_car_id')
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereNotIn('id', $this->usedReservationIds)
            ->whereHas('tripCar', static fn ($q) => $q->whereNotNull('captain_id'))
            ->with('tripCar:id,captain_id,trip_id')
            ->inRandomOrder()
            ->limit(self::CAPTAIN_REJECTIONS * 4)
            ->get();

        foreach ($candidates as $reservation) {
            if ($created >= self::CAPTAIN_REJECTIONS) {
                break;
            }

            if ($this->reservationAlreadyReported($reservation->id)) {
                continue;
            }

            $captainId = (int) $reservation->tripCar?->captain_id;
            if ($captainId <= 0) {
                continue;
            }

            $withAdminReply = fake()->boolean((int) (self::CAPTAIN_REPLIED_RATIO * 100));

            CaptainReport::query()->create([
                'type' => CaptainReport::TYPE_CAPTAIN,
                'reservation_id' => $reservation->id,
                'trip_id' => $reservation->trip_id,
                'captain_id' => $captainId,
                'message' => fake()->randomElement([
                    'The client did not show up at the pickup point on time.',
                    'The client was not reachable by phone before departure.',
                    'The client behavior was inappropriate and I could not accept them on the trip.',
                    'العميل ما حضرش في مكان الالتقاط في المعاد.',
                    'مقدرتش أوصل للعميل على التليفون قبل الانطلاق.',
                    'سلوك العميل كان غير مناسب ومقدرتش أكمل الرحلة معاه.',
                ]),
                'admin_reply' => $withAdminReply
                    ? fake()->randomElement([
                        'Noted. We will review this case and follow up with the client if needed.',
                        'Thank you for the report. This has been logged for review.',
                        'تم استلام البلاغ وسيتم مراجعته من الإدارة.',
                        'شكرًا على الإبلاغ، تم تسجيل الملاحظة.',
                    ])
                    : null,
                'replied_at' => $withAdminReply ? now()->subDays(fake()->numberBetween(0, 14)) : null,
                'replied_by' => $withAdminReply ? $admin->id : null,
            ]);

            $reservation->update(['status' => 'cancelled']);
            $this->usedReservationIds[] = (int) $reservation->id;
            $created++;
        }

        return $created;
    }

    private function reservationAlreadyReported(int $reservationId): bool
    {
        return CaptainReport::query()->where('reservation_id', $reservationId)->exists();
    }
}
