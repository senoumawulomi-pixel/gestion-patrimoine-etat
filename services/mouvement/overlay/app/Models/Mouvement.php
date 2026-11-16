<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mouvement extends Model
{
    protected $fillable = [
        'organisation_id', 'bien_id', 'type', 'depuis_service', 'vers_service', 'description',
        'cout', 'date_mouvement', 'user_name',
    ];

    protected $casts = ['date_mouvement' => 'date:Y-m-d', 'cout' => 'float'];
}
