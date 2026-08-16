<?php

namespace Database\Seeders;

use App\Models\Gender;
use App\Models\Schooladmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class test extends Seeder
{
    private array $regions = [
        'Adamawa', 'Centre', 'East', 'Far North', 'Littoral',
        'North', 'Northwest', 'South', 'Southwest', 'West'
    ];

    private array $cities = [
        // Major cities with their regions
        ['name' => 'Douala', 'region' => 'Littoral'],
        ['name' => 'Yaoundé', 'region' => 'Centre'],
        ['name' => 'Garoua', 'region' => 'North'],
        ['name' => 'Bamenda', 'region' => 'Northwest'],
        ['name' => 'Maroua', 'region' => 'Far North'],
        ['name' => 'Bafoussam', 'region' => 'West'],
        ['name' => 'Nkongsamba', 'region' => 'Littoral'],
        ['name' => 'Kumba', 'region' => 'Southwest'],
        ['name' => 'Limbe', 'region' => 'Southwest'],
        ['name' => 'Buea', 'region' => 'Southwest'],
        ['name' => 'Edea', 'region' => 'Littoral'],
        ['name' => 'Bertoua', 'region' => 'East'],
        ['name' => 'Mbalmayo', 'region' => 'Centre'],
        ['name' => 'Mamfe', 'region' => 'Southwest'],
        ['name' => 'Kousséri', 'region' => 'Far North'],
        ['name' => 'Mokolo', 'region' => 'Far North'],
        ['name' => 'Foumban', 'region' => 'West'],
        ['name' => 'Ebolowa', 'region' => 'South'],
        ['name' => 'Ngaoundéré', 'region' => 'Adamawa'],
        ['name' => 'Kribi', 'region' => 'South'],
        ['name' => 'Bafang', 'region' => 'West'],
        ['name' => 'Bali', 'region' => 'Northwest'],
        ['name' => 'Mbouda', 'region' => 'West'],
        ['name' => 'Dschang', 'region' => 'West'],
    ];

    private array $quarters = [
        // Douala quarters
        'Bonamoussadi', 'Bepanda', 'Akwa', 'Bonaberi', 'New Bell',
        'Bonapriso', 'Makepe', 'Logbaba', 'Ndogbong', 'Bonanjo',
        'Deido', 'Boko', 'Cité des Palmiers', 'Japoma', 'Bassa',
        // Yaoundé quarters
        'Mokolo', 'Mvog-Mbi', 'Elig-Effa', 'Nlongkak', 'Messa',
        'Etoudi', 'Obili', 'Nkolndongo', 'Biyem-Assi', 'Melen',
        'Nkoldongo', 'Jouvence', 'Mbankolo', 'Simbock', 'Ahala',
        // Other cities quarters
        'Bamenda I', 'Bamenda II', 'Bamenda III', 'Up Station',
        'Nkwen', 'Bayelle', 'Bafoussam I', 'Bafoussam II', 'Djem',
        'Tamdja', 'Banengo', 'Tougang', 'Kumbo', 'Nso', 'Donga',
        'Nkambé', 'Sankie', 'Fongo', 'Foncha', 'Mankon',
        // Common quarters
        'Camp SIC', 'Cité Verte', 'Cité des Cadres', 'Plateau',
        'Montée', 'Quartier Latin', 'Nouvelle Route', 'Omnisport',
        'Rond-Point', 'Carrière', 'Grand Hangar', 'Petit Plateau'
    ];

    private array $streetNames = [
        'Avenue du Général de Gaulle', 'Avenue Kennedy', 'Avenue Ahmadou Ahidjo',
        'Rue Paul Biya', 'Boulevard de la Liberté', 'Boulevard du 20 Mai',
        'Rue de l\'Hôpital', 'Rue du Commerce', 'Avenue des Cocotiers',
        'Rue de la Paix', 'Boulevard de l\'Indépendance', 'Avenue de l\'Unité',
        'Rue des Palmiers', 'Avenue de la Réunification', 'Rue du Marché',
        'Boulevard des Armées', 'Avenue de la Gare', 'Rue de l\'Église',
        'Avenue de la Mairie', 'Boulevard de la Poste', 'Rue de l\'École',
        'Avenue du Fleuve', 'Rue des Banques', 'Boulevard de la Paix',
        'Avenue Charles de Gaulle', 'Rue du Stade', 'Boulevard de l\'Aéroport'
    ];

    private array $buildingTypes = [
        'Immeuble', 'Villa', 'Résidence', 'Maison', 'Appartement',
        'Bâtiment', 'Complexe', 'Studio', 'Duplex', 'Pavillon'
    ];

    private array $phonePrefixes = [
        '6', '7', '9' // MTN, Orange, Camtel prefixes
    ];

    public function run(): void
    {
        $genders = Gender::all()->pluck('id')->toArray();
        $teachers = Schooladmin::all();

        foreach ($teachers as $teacher) {
            $addressData = $this->generateCameroonianAddress();
            $teacher->update([
                'username' => $this->generateUsername($teacher->name)
            ]);
        }
    }

        private function generateUsername(string $name): string
    {
        $normalized = strtolower(trim($name));
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
        $normalized = preg_replace('/[^a-z0-9\s]/', '', $normalized);

        $parts = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        $base = match (true) {
            empty($parts)       => 'user',
            count($parts) === 1 => $parts[0],
            default             => $parts[0][0] . end($parts),
        };

        $base = strlen($base) < 3 ? str_pad($base, 3, '0') : $base;

        $base = substr($base, 0, 20);

        $username = $base;
        $counter = 1;
        while (Schooladmin::where('username', $username)->exists()) {
            $username = $base . $counter++;
        }

        return $username;
    }
    private function generateCameroonianAddress(): array
    {
        $city = Arr::random($this->cities);
        $quarter = Arr::random($this->quarters);
        $street = Arr::random($this->streetNames);
        $buildingType = Arr::random($this->buildingTypes);
        $buildingNumber = rand(1, 999);
        $apartmentNumber = rand(1, 50);

        // Format types:
        // 1. Formal address with building number
        // 2. Formal address without building number
        // 3. Quarter-based address (common in Cameroon)
        // 4. Landmark-based address

        $formatType = rand(1, 4);

        switch ($formatType) {
            case 1:
                $address = "{$buildingType} {$buildingNumber}, {$street}, {$quarter}, {$city['name']}, Région du {$city['region']}";
                break;
            case 2:
                $address = "{$street}, {$quarter}, {$city['name']}, Région du {$city['region']}";
                break;
            case 3:
                $address = "Quartier {$quarter}, {$city['name']}, Région du {$city['region']}";
                break;
            case 4:
                $landmark = $this->getRandomLandmark($city['name']);
                $address = "Près de {$landmark}, {$quarter}, {$city['name']}, Région du {$city['region']}";
                break;
            default:
                $address = "{$quarter}, {$city['name']}, Région du {$city['region']}";
        }

        // Add apartment number for apartment buildings (30% chance)
        if ($formatType === 1 && rand(1, 100) <= 30) {
            $address = str_replace($buildingType, "{$buildingType} N°{$apartmentNumber}", $address);
        }

        return [
            'full_address' => $address,
            'city' => $city['name'],
            'region' => $city['region'],
            'quarter' => $quarter,
            'street' => $street,
        ];
    }

    private function getRandomLandmark(string $city): string
    {
        $landmarks = [
            'Douala' => [
                'Douala Grand Mall', 'Marché Central', 'Aéroport de Douala',
                'Port Autonome de Douala', 'La Pagode', 'Rond-Point Deido',
                'Gare Routière de Douala', 'Hôpital Laquintinie'
            ],
            'Yaoundé' => [
                'Palais des Congrès', 'Marché Mokolo', 'Aéroport de Yaoundé',
                'Boulevard du 20 Mai', 'Mont Fébé', 'Rond-Point Bastos',
                'Gare Centrale', 'Hôtel de Ville'
            ],
            'Bamenda' => [
                'Commercial Avenue', 'Bamenda Main Market', 'Up Station',
                'Bamenda Airport', 'Regional Hospital', 'Bamenda City Council'
            ],
            'Buea' => [
                'Mont Cameroun', 'Buea Main Market', 'Government House',
                'Buea University', 'Mile 16', 'Bongo Square'
            ],
            'Garoua' => [
                'Grand Marché de Garoua', 'Aéroport de Garoua', 'Palais du Lamido',
                'Rond-Point de la Place de l\'Indépendance'
            ],
            'Maroua' => [
                'Grand Marché de Maroua', 'Palais du Lamido de Maroua',
                'Aéroport de Maroua', 'Place de la Mairie'
            ]
        ];

        $cityLandmarks = $landmarks[$city] ?? [
            'Marché Central', 'Mairie', 'Hôpital Général',
            'Gare Routière', 'Stade Municipal'
        ];

        return Arr::random($cityLandmarks);
    }

    private function generateCameroonianPhone(): string
    {
        $prefix = Arr::random($this->phonePrefixes);
        $number = '';

        for ($i = 0; $i < 7; $i++) {
            $number .= rand(0, 9);
        }

        return "6$prefix$number";
    }

    // Additional method to get address components separately
    public function getAddressComponents(): array
    {
        return [
            'regions' => $this->regions,
            'cities' => $this->cities,
            'quarters' => $this->quarters,
            'streets' => $this->streetNames,
        ];
    }
}
