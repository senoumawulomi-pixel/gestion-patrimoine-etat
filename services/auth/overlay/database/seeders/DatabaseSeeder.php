<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Organisation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $comptes = [
            ['Administrateur central', 'admin@etat.gov', 'Admin@123', 'admin_central'],
            ['Gestionnaire', 'gestionnaire@etat.gov', 'Gest@123', 'gestionnaire'],
            ['Auditeur', 'auditeur@etat.gov', 'Audit@123', 'auditeur'],
        ];
        foreach ($comptes as [$name, $email, $pwd, $role]) {
            $u = User::firstOrNew(['email' => $email]);
            if (! $u->exists) {
                $u->forceFill([
                    'name' => $name, 'password' => Hash::make($pwd),
                    'role' => $role, 'organisation_id' => Organisation::CENTRAL_ID,
                ])->save();
            } elseif ($email === 'admin@etat.gov') {
                $u->forceFill(['role' => $role, 'organisation_id' => Organisation::CENTRAL_ID])->save();
            }
        }
    }
}
