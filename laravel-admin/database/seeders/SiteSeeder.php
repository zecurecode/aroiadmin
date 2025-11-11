<?php

namespace Database\Seeders;

use App\Models\Site;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sites = [
            [
                'name' => 'Namsos',
                'site_id' => 7,
                'url' => 'https://namsos.aroiasia.no',
                'consumer_key' => 'ck_2a4b75485f94a9e44674cbdfe3e31f170f89013c',
                'consumer_secret' => 'cs_bbb666f44f58067e29b496caba424de4b478ff19',
                'license' => 6714,
                'active' => true,
            ],
            [
                'name' => 'Lade',
                'site_id' => 4,
                'url' => 'https://lade.aroiasia.no',
                'consumer_key' => 'ck_bafedee6aeb279a36d03d49e5e1c1cead0f83a70',
                'consumer_secret' => 'cs_2bb6e76e95027487336568b0951fbefc369132ff',
                'license' => 12381,
                'active' => true,
            ],
            [
                'name' => 'Moan',
                'site_id' => 6,
                'url' => 'https://moan.aroiasia.no',
                'consumer_key' => 'ck_81b9ce602a9f1d43fe4f43bf3a0ec9a8d2124243',
                'consumer_secret' => 'cs_489cef04590eea21373aa829246c9e35b9b20745',
                'license' => 5203,
                'active' => true,
            ],
            [
                'name' => 'Gramyra',
                'site_id' => 5,
                'url' => 'https://gramyra.aroiasia.no',
                'consumer_key' => 'ck_0c755f0c8a5ac6e00d407980e011c23bf653f611',
                'consumer_secret' => 'cs_9d75556f8c1936dae310351d5dfd46396cba2ba1',
                'license' => 6715,
                'active' => true,
            ],
            [
                'name' => 'Frosta',
                'site_id' => 10,
                'url' => 'https://frosta.aroiasia.no',
                'consumer_key' => 'ck_d0badbe232a9a4ecb216111bdef901516eea4dfa',
                'consumer_secret' => 'cs_0f5862a1783a7690ed30e92f6ca837fcabfea1c4',
                'license' => 14780,
                'active' => true,
            ],
            [
                'name' => 'Hell',
                'site_id' => 11,
                'url' => 'https://hell.aroiasia.no',
                'consumer_key' => 'ck_45df43fcf8ff4c3868c82bce06f2d847c6b39010',
                'consumer_secret' => 'cs_ae29770a0bc73e905558c883a76e2050181b9c7b',
                'license' => 0,
                'active' => true,
            ],
            [
                'name' => 'Steinkjer',
                'site_id' => 13,
                'url' => 'https://steinkjer.aroiasia.no',
                'consumer_key' => 'ck_placeholder_steinkjer',
                'consumer_secret' => 'cs_placeholder_steinkjer',
                'license' => 30221,
                'active' => true,
            ],
        ];

        foreach ($sites as $siteData) {
            Site::updateOrCreate(
                ['site_id' => $siteData['site_id']],
                $siteData
            );
        }

        echo "Sites seeded successfully!\n";
    }
}
