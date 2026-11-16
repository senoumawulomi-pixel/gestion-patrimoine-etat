<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bien extends Model
{
    protected $table = 'biens';

    protected $fillable = [
        'organisation_id',
        'code', 'designation', 'categorie_id', 'localisation', 'service_affecte',
        'etat', 'statut', 'date_acquisition', 'valeur_acquisition', 'duree_amortissement',
        'type_patrimoine', 'est_public', 'zone_publique', 'description_publique',
    ];

    protected $casts = [
        'date_acquisition' => 'date',
        'valeur_acquisition' => 'float',
        'est_public' => 'boolean',
    ];

    protected $appends = ['valeur_nette'];

    public function categorie()
    {
        return $this->belongsTo(Categorie::class);
    }

    /** Valeur nette comptable : amortissement linéaire sur la durée prévue (jamais stockée). */
    public function getValeurNetteAttribute(): ?float
    {
        if ($this->type_patrimoine !== 'materiel' || ! $this->date_acquisition || $this->valeur_acquisition === null) {
            return null;
        }

        $annees = max(0, $this->date_acquisition->diffInDays(now()) / 365.25);
        $duree = max(1, (int) $this->duree_amortissement);
        $taux = min(1, $annees / $duree);
        return round($this->valeur_acquisition * (1 - $taux), 2);
    }
}
