<?php

namespace Database\Seeders;

use App\Models\PckCredential;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class PckCredentialsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        Log::info('PckCredentialsSeeder: Starting to seed PCK credentials');

        $locations = [
            [
                'tenant_id' => 12, // Steinkjer
                'name' => 'Steinkjer',
                'pck_username' => 'steinkjer_pck',
                'pck_password' => 'pck_steinkjer_2024!',
                'pck_license' => '30221',
            ],
            [
                'tenant_id' => 7, // Namsos
                'name' => 'Namsos',
                'pck_username' => 'namsos_pck',
                'pck_password' => 'pck_namsos_2024!',
                'pck_license' => '6714',
            ],
            [
                'tenant_id' => 4, // Lade
                'name' => 'Lade',
                'pck_username' => 'lade_pck',
                'pck_password' => 'pck_lade_2024!',
                'pck_license' => '12381',
            ],
            [
                'tenant_id' => 6, // Moan
                'name' => 'Moan',
                'pck_username' => 'moan_pck',
                'pck_password' => 'pck_moan_2024!',
                'pck_license' => '5203',
            ],
            [
                'tenant_id' => 5, // Gramyra
                'name' => 'Gramyra',
                'pck_username' => 'gramyra_pck',
                'pck_password' => 'pck_gramyra_2024!',
                'pck_license' => '6715',
            ],
            [
                'tenant_id' => 10, // Frosta
                'name' => 'Frosta',
                'pck_username' => 'frosta_pck',
                'pck_password' => 'pck_frosta_2024!',
                'pck_license' => '14780',
            ],
            [
                'tenant_id' => 11, // Hell
                'name' => 'Hell',
                'pck_username' => 'hell_pck',
                'pck_password' => 'pck_hell_2024!',
                'pck_license' => '0000', // No license assigned yet
            ],
        ];

        foreach ($locations as $location) {
            $credential = PckCredential::updateOrCreate(
                [
                    'tenant_id' => $location['tenant_id'],
                    'pck_username' => $location['pck_username'],
                ],
                [
                    'pck_password' => $location['pck_password'], // Will be automatically encrypted
                    'pck_license' => $location['pck_license'],
                    'wsdl_version' => '1.98',
                    'is_enabled' => true,
                    'ip_whitelist' => null, // No IP restrictions by default
                ]
            );

            Log::info('PckCredentialsSeeder: Created/updated credential', [
                'tenant_id' => $location['tenant_id'],
                'name' => $location['name'],
                'username' => $location['pck_username'],
                'license' => $location['pck_license'],
                'credential_id' => $credential->id,
            ]);
        }

        Log::info('PckCredentialsSeeder: Completed seeding PCK credentials', [
            'total_credentials' => count($locations),
        ]);

        // Output summary for manual verification
        $this->command->info('PCK Credentials seeded successfully:');
        foreach ($locations as $location) {
            $this->command->line("  - {$location['name']} (ID: {$location['tenant_id']}, License: {$location['pck_license']})");
        }
        
        $this->command->warn('IMPORTANT: These are sample credentials. Update passwords in production!');
        $this->command->info('Use: php artisan pck:update-credentials to manage credentials securely');
    }
}