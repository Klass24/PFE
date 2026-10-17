<?php

namespace App\Modules\Vehicle\Services;

class VehicleCatalog
{
    /**
     * Catalog indexed by normalized brand key.
     * Each entry: ['label' => string, 'models' => string[]]
     */
    private static array $catalog = [
        'toyota' => [
            'label'  => 'Toyota',
            'models' => ['Hilux', 'Land Cruiser', 'Prado', 'Fortuner', 'Corolla', 'Camry', 'Yaris', 'RAV4', 'Hiace', 'Coaster', 'Dyna'],
        ],
        'mercedes-benz' => [
            'label'  => 'Mercedes-Benz',
            'models' => ['Sprinter', 'Vito', 'Viano', 'Citan', 'Actros', 'Atego', 'Axor', 'Arocs', 'C-Class', 'E-Class', 'S-Class', 'GLE', 'GLC'],
        ],
        'renault' => [
            'label'  => 'Renault',
            'models' => ['Kangoo', 'Master', 'Trafic', 'Express', 'Clio', 'Mégane', 'Duster', 'Logan', 'Symbol', 'Trucks T', 'Trucks C', 'Trucks K'],
        ],
        'peugeot' => [
            'label'  => 'Peugeot',
            'models' => ['Expert', 'Boxer', 'Partner', '208', '308', '508', '3008', '5008', 'Landtrek'],
        ],
        'volkswagen' => [
            'label'  => 'Volkswagen',
            'models' => ['Crafter', 'Transporter', 'Caddy', 'Amarok', 'Golf', 'Passat', 'Tiguan', 'T-Roc', 'Polo'],
        ],
        'ford' => [
            'label'  => 'Ford',
            'models' => ['Transit', 'Transit Connect', 'Ranger', 'F-150', 'F-250', 'Focus', 'Mondeo', 'Kuga', 'Explorer', 'Super Duty'],
        ],
        'hyundai' => [
            'label'  => 'Hyundai',
            'models' => ['H-1', 'H-350', 'County', 'Tucson', 'Santa Fe', 'Elantra', 'i10', 'i20', 'i30', 'Starex'],
        ],
        'kia' => [
            'label'  => 'Kia',
            'models' => ['Bongo', 'Carnival', 'Sportage', 'Sorento', 'Stinger', 'K5', 'K8', 'Telluride'],
        ],
        'isuzu' => [
            'label'  => 'Isuzu',
            'models' => ['D-Max', 'NPR', 'NQR', 'NPS', 'FVR', 'FTR', 'Elf', 'Giga', 'Trooper'],
        ],
        'mitsubishi' => [
            'label'  => 'Mitsubishi',
            'models' => ['L200', 'Pajero', 'Outlander', 'L300', 'Canter', 'Fighter', 'Rosa', 'Fuso'],
        ],
        'nissan' => [
            'label'  => 'Nissan',
            'models' => ['Patrol', 'Navara', 'NV200', 'NV400', 'Urvan', 'Cabstar', 'Qashqai', 'X-Trail', 'Almera'],
        ],
        'man' => [
            'label'  => 'MAN',
            'models' => ['TGX', 'TGS', 'TGM', 'TGL', 'TGE', "Lion's Coach", "Lion's City", "Lion's Intercity"],
        ],
        'volvo' => [
            'label'  => 'Volvo',
            'models' => ['FH', 'FM', 'FMX', 'FE', 'FL', 'FLC', 'XC90', 'XC60', 'XC40', 'S90', 'V90'],
        ],
        'scania' => [
            'label'  => 'Scania',
            'models' => ['R-Series', 'S-Series', 'G-Series', 'P-Series', 'L-Series', 'XT', 'Irizar i6', 'Touring'],
        ],
        'daf' => [
            'label'  => 'DAF',
            'models' => ['XF', 'XG', 'CF', 'LF', 'CF Electric', 'LF Electric'],
        ],
        'iveco' => [
            'label'  => 'Iveco',
            'models' => ['Daily', 'Eurocargo', 'Stralis', 'Trakker', 'S-Way', 'E-Way', 'Crossway'],
        ],
        'citroen' => [
            'label'  => 'Citroën',
            'models' => ['Berlingo', 'Jumpy', 'Jumper', 'ë-Berlingo', 'C3', 'C4', 'C5 Aircross', 'Dispatch'],
        ],
        'fiat' => [
            'label'  => 'Fiat',
            'models' => ['Ducato', 'Doblò', 'Scudo', 'Tipo', '500X', 'Panda', 'Bravo'],
        ],
        'dacia' => [
            'label'  => 'Dacia',
            'models' => ['Duster', 'Logan', 'Sandero', 'Spring', 'Dokker', 'Lodgy', 'Jogger'],
        ],
        'hino' => [
            'label'  => 'Hino',
            'models' => ['500 Series', '300 Series', '700 Series', 'Ranger', 'Profia', 'Dutro'],
        ],
        'foton' => [
            'label'  => 'Foton',
            'models' => ['Tunland', 'Aumark', 'Auman', 'BJ 1049', 'BJ 1069', 'Tornado', 'Ollin'],
        ],
        'dongfeng' => [
            'label'  => 'Dongfeng',
            'models' => ['Rich', 'Glory', 'Captain T', 'KR', 'KX', 'GX', 'H30 Cross'],
        ],
    ];

    /**
     * Returns sorted list of brand labels.
     * @return string[]
     */
    public static function brands(): array
    {
        $brands = array_map(fn($v) => $v['label'], self::$catalog);
        sort($brands);
        return $brands;
    }

    /**
     * Returns models for a given brand label.
     * Matching is case-insensitive.
     * @return string[]
     */
    public static function modelsFor(string $brand): array
    {
        $normalized = strtolower(trim($brand));

        foreach (self::$catalog as $data) {
            if (strtolower($data['label']) === $normalized) {
                return $data['models'];
            }
        }

        return [];
    }
}
