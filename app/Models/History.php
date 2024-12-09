<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class History extends Model
{
    use HasFactory, Notifiable,HasUuids;
    protected $table = 'history';
    protected $fillable = [
        'idSala',
        'matter',
        'title',
        'message',
        'role'
    ];
}
