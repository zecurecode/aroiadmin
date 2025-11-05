<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'site_id' => 7,
                'name' => 'Namsos',
                'license' => 6714,
                'phone' => '+47 74 21 50 50',
                'email' => 'namsos@aroiasia.no',
                'address' => 'Namsos, Norway',
                'active' => true,
            ],
            [
                'site_id' => 4,
                'name' => 'Lade',
                'license' => 12381,
                'phone' => '+47 73 52 50 50',
                'email' => 'lade@aroiasia.no',
                'address' => 'Lade, Trondheim, Norway',
                'active' => true,
            ],
            [
                'site_id' => 6,
                'name' => 'Moan',
                'license' => 5203,
                'phone' => '+47 73 52 50 50',
                'email' => 'moan@aroiasia.no',
                'address' => 'Moan, Trondheim, Norway',
                'active' => true,
            ],
            [
                'site_id' => 5,
                'name' => 'Gramyra',
                'license' => 6715,
                'phone' => '+47 73 52 50 50',
                'email' => 'gramyra@aroiasia.no',
                'address' => 'Gramyra, Trondheim, Norway',
                'active' => true,
            ],
            [
                'site_id' => 10,
                'name' => 'Frosta',
                'license' => 14780,
                'phone' => '+47 74 21 50 50',
                'email' => 'frosta@aroiasia.no',
                'address' => 'Frosta, Norway',
                'active' => true,
            ],
            [
                'site_id' => 11,
                'name' => 'Hell',
                'license' => 0,
                'phone' => '+47 74 21 50 50',
                'email' => 'hell@aroiasia.no',
                'address' => 'Hell, Norway',
                'active' => true,
            ],
            [
                'site_id' => 13,
                'name' => 'Steinkjer',
                'license' => 30221,
                'phone' => '+47 74 21 50 50',
                'email' => 'steinkjer@aroiasia.no',
                'address' => 'Steinkjer, Norway',
                'active' => true,
            ],
        ];

        foreach ($locations as $locationData) {
            Location::updateOrCreate(
                ['site_id' => $locationData['site_id']],
                $locationData
            );
        }

        echo "Locations seeded successfully!\n";
    }
}
