<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organisation extends Model
{
    public const CENTRAL_ID = '00000000-0000-4000-8000-000000000001';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['nom', 'code', 'type', 'parent_id'];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function enfants()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
