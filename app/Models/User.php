<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Support\CaptainDocumentCollections;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, InteractsWithMedia, Notifiable, SoftDeletes;

   
    protected $guarded = [];

    
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function notificationDeliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    public function tripCarsAsCaptain(): HasMany
    {
        return $this->hasMany(TripCar::class, 'captain_id');
    }


    public function receivedFeedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class, 'captain_id');
    }

    public function sentFeedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class, 'client_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'client_id');
    }

    public function currentCaptainTrip(): BelongsTo
    {
        return $this->belongsTo(Trip::class, 'trip_id');
    }

    public function parentCompany(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function childClients(): HasMany
    {
        return $this->hasMany(User::class, 'company_id');
    }

    public function isCompanyOperator(): bool
    {
        $this->loadMissing('role');

        return $this->type === 'admin'
            && $this->role !== null
            && $this->role->name_en === 'Company';
    }

    public function hasPermission(string $name): bool
    {
        $this->loadMissing('role.permissions');

        return $this->role?->permissions->contains('name', $name) ?? false;
    }

    /**
     * @param  list<string>  $names
     */
    public function hasAnyPermission(array $names): bool
    {
        foreach ($names as $name) {
            if ($this->hasPermission($name)) {
                return true;
            }
        }

        return false;
    }

    public function hasFcmToken(): bool
    {
        return filled($this->fcm_token);
    }

    protected $hidden = [
        'password',
        'remember_token',
        'fcm_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'balance' => 'decimal:2',
            'has_trip' => 'boolean',
            'lat' => 'decimal:8',
            'long' => 'decimal:8',
            'license_expiry_date' => 'date',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

        $this->addMediaCollection('image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

        $documentMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/pdf',
        ];

        foreach (CaptainDocumentCollections::keys() as $collection) {
            $this->addMediaCollection($collection)
                ->singleFile()
                ->acceptsMimeTypes($documentMimeTypes);
        }
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        if (! self::mediaLibraryImageDriverIsAvailable()) {
            return;
        }

        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->keepOriginalImageFormat()
            ->performOnCollections('avatar')
            ->nonQueued();
    }

    private static function mediaLibraryImageDriverIsAvailable(): bool
    {
        return match (config('media-library.image_driver', 'gd')) {
            'imagick' => extension_loaded('imagick'),
            'vips' => extension_loaded('vips'),
            default => extension_loaded('gd'),
        };
    }

    /**
     * Default vehicle assigned to this captain (nullable).
     *
     * @return HasOne<Car, $this>
     */
    public function car(): HasOne
    {
        return $this->hasOne(Car::class, 'captain_id');
    }
}
