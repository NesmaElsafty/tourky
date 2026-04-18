<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    public const USER_TYPES = ['client', 'captain'];

    protected $table = 'notifications';

    protected $guarded = [];
}
