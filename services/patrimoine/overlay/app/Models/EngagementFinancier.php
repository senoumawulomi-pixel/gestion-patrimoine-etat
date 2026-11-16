<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EngagementFinancier extends Model
{
    protected $table = 'engagements_financiers';

    protected $fillable = [
        'organisation_id', 'code', 'nature', 'designation', 'contrepartie',
        'montant_initial', 'solde_courant', 'date_effet', 'date_echeance',
        'statut', 'notes',
    ];

    protected $casts = [
        'montant_initial' => 'float',
        'solde_courant' => 'float',
        'date_effet' => 'date:Y-m-d',
        'date_echeance' => 'date:Y-m-d',
    ];
}
