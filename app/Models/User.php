<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, InteractsWithMedia, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'language',
        'type',
        'role_id',
        'company_id',
    ];

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return HasMany<NotificationDelivery, $this>
     */
    public function notificationDeliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    /**
     * @return HasMany<TripCar, $this>
     */
    public function tripCarsAsCaptain(): HasMany
    {
        return $this->hasMany(TripCar::class, 'captain_id');
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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

        $this->addMediaCollection('documents');
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
}
