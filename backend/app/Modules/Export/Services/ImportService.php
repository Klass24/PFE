<?php

namespace App\Modules\Export\Services;

use App\Modules\Driver\Models\Driver;
use App\Modules\Document\Models\Document;
use App\Modules\Expense\Models\Expense;
use App\Modules\Incident\Models\Incident;
use App\Modules\Maintenance\Models\MaintenanceSchedule;
use App\Modules\Mission\Models\Mission;
use App\Modules\User\Models\User;
use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportService
{
    /**
     * Import véhicules depuis CSV/XLSX.
     * Colonnes attendues : plate_number, brand, model, year, type, fuel_type, mileage, status
     */
    public function importVehicles(string $filePath, string $extension): array
    {
        $rows = $this->readFile($filePath, $extension);
        $required = ['plate_number', 'brand', 'model', 'year'];
        $this->validateHeaders($rows[0] ?? [], $required);

        $companyId = auth()->user()->company_id;
        $imported = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach (array_slice($rows, 1) as $i => $row) {
                $line = $i + 2;
                try {
                    Vehicle::updateOrCreate(
                        ['plate_number' => trim($row['plate_number']), 'company_id' => $companyId],
                        [
                            'uuid'       => Str::uuid(),
                            'company_id' => $companyId,
                            'brand'      => trim($row['brand']),
                            'model'      => trim($row['model']),
                            'year'       => (int) $row['year'],
                            'type'       => trim($row['type'] ?? 'car'),
                            'fuel_type'  => trim($row['fuel_type'] ?? 'diesel'),
                            'mileage'    => (int) ($row['mileage'] ?? 0),
                            'status'     => trim($row['status'] ?? 'available'),
                        ]
                    );
                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = "Ligne {$line}: {$e->getMessage()}";
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        Log::info('Vehicles imported', ['count' => $imported, 'errors' => count($errors), 'by' => auth()->id()]);
        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * Import dépenses depuis CSV/XLSX.
     * Colonnes attendues : mission_uuid, type, amount, liters, mileage, notes
     */
    public function importExpenses(string $filePath, string $extension): array
    {
        $rows = $this->readFile($filePath, $extension);
        $required = ['mission_uuid', 'type', 'amount'];
        $this->validateHeaders($rows[0] ?? [], $required);

        $companyId = auth()->user()->company_id;
        $imported = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach (array_slice($rows, 1) as $i => $row) {
                $line = $i + 2;
                try {
                    $mission = Mission::where('uuid', trim($row['mission_uuid']))
                        ->where('company_id', $companyId)
                        ->firstOrFail();

                    Expense::create([
                        'uuid'       => Str::uuid(),
                        'company_id' => $companyId,
                        'mission_id' => $mission->id,
                        'type'       => trim($row['type']),
                        'amount'     => (float) $row['amount'],
                        'liters'     => !empty($row['liters']) ? (float) $row['liters'] : null,
                        'mileage'    => !empty($row['mileage']) ? (int) $row['mileage'] : null,
                        'notes'      => trim($row['notes'] ?? ''),
                        'created_by' => auth()->id(),
                    ]);
                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = "Ligne {$line}: {$e->getMessage()}";
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        Log::info('Expenses imported', ['count' => $imported, 'errors' => count($errors), 'by' => auth()->id()]);
        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * Import missions depuis CSV/XLSX.
     * Colonnes minimales attendues : origin, destination, planned_departure
     * vehicle_uuid / driver_uuid sont optionnels si les colonnes métier sont présentes.
     */
    public function importMissions(string $filePath, string $extension): array
    {
        $rows = $this->readFile($filePath, $extension);
        $required = ['origin', 'destination', 'planned_departure'];
        $this->validateHeaders($rows[0] ?? [], $required);

        $companyId = auth()->user()->company_id;
        $imported = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach (array_slice($rows, 1) as $i => $row) {
                $line = $i + 2;
                try {
                    $vehicleUuid = trim((string) ($row['vehicle_uuid'] ?? ''));
                    $vehiclePlate = trim((string) ($row['vehicle_plate_number'] ?? $row['vehicle'] ?? ''));
                    $vehicleQuery = Vehicle::where('company_id', $companyId);
                    if ($vehicleUuid !== '') {
                        $vehicleQuery->where('uuid', $vehicleUuid);
                    } elseif ($vehiclePlate !== '') {
                        $vehicleQuery->where('plate_number', $vehiclePlate);
                    } else {
                        throw new \InvalidArgumentException('vehicle_uuid ou vehicle_plate_number requis');
                    }
                    $vehicle = $vehicleQuery->firstOrFail();

                    $driverUuid = trim((string) ($row['driver_uuid'] ?? ''));
                    $driverName = trim((string) ($row['driver_name'] ?? $row['driver'] ?? $row['name'] ?? ''));
                    $driverQuery = Driver::where('company_id', $companyId);
                    if ($driverUuid !== '') {
                        $driverQuery->where('uuid', $driverUuid);
                    } elseif ($driverName !== '') {
                        $driverQuery->whereHas('user', fn($q) => $q->where('name', $driverName));
                    } else {
                        throw new \InvalidArgumentException('driver_uuid ou driver_name requis');
                    }
                    $driver = $driverQuery->firstOrFail();

                    Mission::create([
                        'uuid' => Str::uuid(),
                        'company_id' => $companyId,
                        'vehicle_id' => $vehicle->id,
                        'driver_id' => $driver->id,
                        'origin' => trim((string) $row['origin']),
                        'destination' => trim((string) $row['destination']),
                        'planned_departure' => $row['planned_departure'] ?? $row['departure'] ?? null,
                        'planned_arrival' => $row['planned_arrival'] ?? $row['arrival'] ?? null,
                        'status' => trim((string) ($row['status'] ?? 'planned')),
                        'notes' => trim((string) ($row['notes'] ?? '')),
                    ]);
                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = "Ligne {$line}: {$e->getMessage()}";
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        Log::info('Missions imported', ['count' => $imported, 'errors' => count($errors), 'by' => auth()->id()]);
        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * Import incidents depuis CSV/XLSX.
     * Colonnes attendues : mission_uuid, type, description, latitude, longitude
     */
    public function importIncidents(string $filePath, string $extension): array
    {
        $rows = $this->readFile($filePath, $extension);
        $required = ['mission_uuid', 'type', 'description'];
        $this->validateHeaders($rows[0] ?? [], $required);

        $companyId = auth()->user()->company_id;
        $imported = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach (array_slice($rows, 1) as $i => $row) {
                $line = $i + 2;
                try {
                    $mission = Mission::where('uuid', trim($row['mission_uuid']))
                        ->where('company_id', $companyId)
                        ->firstOrFail();

                    Incident::create([
                        'uuid'        => Str::uuid(),
                        'company_id'  => $companyId,
                        'mission_id'  => $mission->id,
                        'type'        => trim($row['type']),
                        'description' => trim($row['description']),
                        'latitude'    => !empty($row['latitude']) ? (float) $row['latitude'] : null,
                        'longitude'   => !empty($row['longitude']) ? (float) $row['longitude'] : null,
                        'reported_at' => now(),
                    ]);
                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = "Ligne {$line}: {$e->getMessage()}";
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        Log::info('Incidents imported', ['count' => $imported, 'errors' => count($errors), 'by' => auth()->id()]);
        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * Import documents depuis CSV/XLSX.
     * Colonnes attendues : documentable_type, documentable_uuid, type, file_path
     */
    public function importDocuments(string $filePath, string $extension): array
    {
        $rows = $this->readFile($filePath, $extension);
        $required = ['documentable_type', 'type'];
        $this->validateHeaders($rows[0] ?? [], $required);

        $companyId = auth()->user()->company_id;
        $imported = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach (array_slice($rows, 1) as $i => $row) {
                $line = $i + 2;
                try {
                    $type = strtolower(trim((string) $row['documentable_type']));
                    $documentableUuid = trim((string) ($row['documentable_uuid'] ?? ''));
                    $sourceDocument = null;

                    if ($documentableUuid === '' && !empty($row['uuid'])) {
                        $sourceDocument = Document::where('uuid', trim((string) $row['uuid']))
                            ->where('company_id', $companyId)
                            ->first();
                    }

                    $documentable = null;
                    if ($documentableUuid !== '') {
                        if (str_contains($type, 'vehicle')) {
                            $documentable = Vehicle::where('uuid', $documentableUuid)
                                ->where('company_id', $companyId)
                                ->firstOrFail();
                        } elseif (str_contains($type, 'driver')) {
                            $documentable = Driver::where('uuid', $documentableUuid)
                                ->where('company_id', $companyId)
                                ->firstOrFail();
                        } else {
                            throw new \InvalidArgumentException('documentable_type doit contenir vehicle ou driver');
                        }
                    } elseif ($sourceDocument) {
                        $documentable = $sourceDocument->documentable;
                        if (!$documentable) {
                            throw new \InvalidArgumentException('impossible de résoudre documentable depuis uuid');
                        }
                    } else {
                        throw new \InvalidArgumentException('documentable_uuid manquant (ou uuid existant requis)');
                    }

                    $filePath = trim((string) ($row['file_path'] ?? ''));
                    if ($filePath === '' && $sourceDocument) {
                        $filePath = (string) $sourceDocument->file_path;
                    }
                    if ($filePath === '') {
                        $filePath = 'imports/documents/' . Str::uuid() . '.bin';
                    }

                    Document::create([
                        'uuid' => Str::uuid(),
                        'company_id' => $companyId,
                        'documentable_type' => $documentable::class,
                        'documentable_id' => $documentable->id,
                        'type' => trim((string) $row['type']),
                        'file_path' => $filePath,
                        'issue_date' => $row['issue_date'] ?: null,
                        'expiry_date' => $row['expiry_date'] ?: null,
                        'alert_sent' => !empty($row['alert_sent']) && filter_var($row['alert_sent'], FILTER_VALIDATE_BOOLEAN),
                    ]);
                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = "Ligne {$line}: {$e->getMessage()}";
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        Log::info('Documents imported', ['count' => $imported, 'errors' => count($errors), 'by' => auth()->id()]);
        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * Import maintenances depuis CSV/XLSX.
     * Colonnes attendues : vehicle_uuid, maintenance_type
     */
    public function importMaintenance(string $filePath, string $extension): array
    {
        $rows = $this->readFile($filePath, $extension);
        $required = ['vehicle_uuid', 'maintenance_type'];
        $this->validateHeaders($rows[0] ?? [], $required);

        $companyId = auth()->user()->company_id;
        $imported = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach (array_slice($rows, 1) as $i => $row) {
                $line = $i + 2;
                try {
                    $vehicle = Vehicle::where('uuid', trim((string) $row['vehicle_uuid']))
                        ->where('company_id', $companyId)
                        ->firstOrFail();

                    MaintenanceSchedule::create([
                        'uuid' => Str::uuid(),
                        'company_id' => $companyId,
                        'vehicle_id' => $vehicle->id,
                        'maintenance_type' => trim((string) $row['maintenance_type']),
                        'frequency_km' => !empty($row['frequency_km']) ? (int) $row['frequency_km'] : null,
                        'frequency_months' => !empty($row['frequency_months']) ? (int) $row['frequency_months'] : null,
                        'last_service_date' => $row['last_service_date'] ?: null,
                        'last_service_km' => !empty($row['last_service_km']) ? (int) $row['last_service_km'] : null,
                        'next_due_date' => $row['next_due_date'] ?: null,
                        'next_due_km' => !empty($row['next_due_km']) ? (int) $row['next_due_km'] : null,
                        'status' => trim((string) ($row['status'] ?? 'active')),
                        'notes' => trim((string) ($row['notes'] ?? '')),
                    ]);
                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = "Ligne {$line}: {$e->getMessage()}";
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        Log::info('Maintenance imported', ['count' => $imported, 'errors' => count($errors), 'by' => auth()->id()]);
        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * Import chauffeurs depuis CSV/XLSX.
     * Colonnes attendues : name, email, license_number, license_expiry, phone
     */
    public function importDrivers(string $filePath, string $extension): array
    {
        $rows = $this->readFile($filePath, $extension);
        $required = ['name', 'email', 'license_number', 'license_expiry'];
        $this->validateHeaders($rows[0] ?? [], $required);

        $companyId = auth()->user()->company_id;
        $imported = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach (array_slice($rows, 1) as $i => $row) {
                $line = $i + 2;
                try {
                    $user = \App\Modules\User\Models\User::updateOrCreate(
                        ['email' => trim($row['email']), 'company_id' => $companyId],
                        [
                            'uuid'       => Str::uuid(),
                            'company_id' => $companyId,
                            'name'       => trim($row['name']),
                            'password'   => bcrypt('FleetControl2026!'),
                            'is_active'  => true,
                        ]
                    );
                    if (!$user->hasRole('driver')) {
                        $user->assignRole('driver');
                    }

                    Driver::updateOrCreate(
                        ['user_id' => $user->id, 'company_id' => $companyId],
                        [
                            'uuid'           => Str::uuid(),
                            'company_id'     => $companyId,
                            'license_number' => trim($row['license_number']),
                            'license_expiry' => $row['license_expiry'],
                            'phone'          => trim($row['phone'] ?? ''),
                            'status'         => 'available',
                        ]
                    );
                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = "Ligne {$line}: {$e->getMessage()}";
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        Log::info('Drivers imported', ['count' => $imported, 'errors' => count($errors), 'by' => auth()->id()]);
        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * Import utilisateurs depuis CSV/XLSX.
     * Colonnes attendues : name, email, role
     */
    public function importUsers(string $filePath, string $extension): array
    {
        $rows = $this->readFile($filePath, $extension);
        $required = ['name', 'email', 'role'];
        $this->validateHeaders($rows[0] ?? [], $required);

        $companyId = auth()->user()->company_id;
        $imported = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach (array_slice($rows, 1) as $i => $row) {
                $line = $i + 2;
                try {
                    $role = trim((string) $row['role']);
                    if (!in_array($role, ['admin', 'manager', 'driver'], true)) {
                        throw new \InvalidArgumentException('role invalide: admin, manager ou driver');
                    }

                    $user = User::updateOrCreate(
                        ['email' => trim((string) $row['email']), 'company_id' => $companyId],
                        [
                            'uuid' => Str::uuid(),
                            'company_id' => $companyId,
                            'name' => trim((string) $row['name']),
                            'password' => bcrypt((string) ($row['password'] ?? 'FleetControl2026!')),
                            'is_active' => !isset($row['is_active']) || filter_var($row['is_active'], FILTER_VALIDATE_BOOLEAN),
                        ]
                    );

                    $user->syncRoles([$role]);
                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = "Ligne {$line}: {$e->getMessage()}";
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        Log::info('Users imported', ['count' => $imported, 'errors' => count($errors), 'by' => auth()->id()]);
        return ['imported' => $imported, 'errors' => $errors];
    }

    // ─── Helpers ────────────────────────────────────────────────────────

    private function readFile(string $filePath, string $extension): array
    {
        if (in_array($extension, ['xlsx', 'xls'])) {
            return $this->readExcel($filePath);
        }
        return $this->readCsv($filePath);
    }

    private function readCsv(string $filePath): array
    {
        $rows = [];
        $headers = null;

        if (($handle = fopen($filePath, 'r')) !== false) {
            // Detect delimiter
            $firstLine = fgets($handle);
            rewind($handle);
            $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                if ($headers === null) {
                    $candidateHeaders = array_map(
                        fn($h) => $this->mapHeaderAlias($this->normalizeHeaderKey((string) $h)),
                        $data
                    );

                    // Ignore les lignes d'en-têtes vides (ex: ligne blanche au début du fichier).
                    if (!$this->hasAnyNonEmptyValue($candidateHeaders)) {
                        continue;
                    }

                    $headers = $candidateHeaders;
                    $rows[] = $headers;
                } else {
                    $row = [];
                    foreach ($headers as $i => $key) {
                        $row[$key] = $data[$i] ?? '';
                    }
                    $rows[] = $row;
                }
            }
            fclose($handle);
        }

        return $rows;
    }

    private function readExcel(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, false);
        $spreadsheet->disconnectWorksheets();

        $rows = [];
        $headers = null;

        foreach ($data as $rowData) {
            if ($headers === null) {
                $candidateHeaders = array_map(
                    fn($h) => $this->mapHeaderAlias($this->normalizeHeaderKey((string) $h)),
                    $rowData
                );

                if (!$this->hasAnyNonEmptyValue($candidateHeaders)) {
                    continue;
                }

                $headers = $candidateHeaders;
                $rows[] = $headers;
            } else {
                $row = [];
                foreach ($headers as $i => $key) {
                    $row[$key] = $rowData[$i] ?? '';
                }
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function validateHeaders(array $firstRow, array $required): void
    {
        $headerValues = $this->isAssocArray($firstRow) ? array_keys($firstRow) : $firstRow;

        $headers = array_map(
            fn($h) => $this->mapHeaderAlias($this->normalizeHeaderKey((string) $h)),
            $headerValues
        );

        $missing = array_diff($required, $headers);
        if (!empty($missing)) {
            throw ValidationException::withMessages([
                'file' => 'Colonnes manquantes : ' . implode(', ', $missing),
            ]);
        }
    }

    private function normalizeHeaderKey(string $header): string
    {
        // Rend l'import tolérant aux accents, espaces et variantes de casse.
        $header = str_replace(["\xEF\xBB\xBF", "\u{FEFF}", "\u{00A0}"], ' ', $header);
        $header = preg_replace('/\s+/u', ' ', $header) ?? $header;

        return Str::snake(trim(Str::ascii($header)));
    }

    private function mapHeaderAlias(string $header): string
    {
        $aliases = [
            // Compatibilité avec les exports FR (véhicules)
            'plaque' => 'plate_number',
            'immatriculation' => 'plate_number',
            'marque' => 'brand',
            'modele' => 'model',
            'annee' => 'year',
            'chassis' => 'chassis_number',
            'carburant' => 'fuel_type',
            'kilometrage' => 'mileage',
            'statut' => 'status',

            // Compatibilité exports FR (chauffeurs)
            'nom' => 'name',
            'chauffeur' => 'name',
            'n_permis' => 'license_number',
            'no_permis' => 'license_number',
            'numero_permis' => 'license_number',
            'n_de_permis' => 'license_number',
            'expiration_permis' => 'license_expiry',
            'exp_permis' => 'license_expiry',
            'expiraton_permis' => 'license_expiry',

            // Compatibilité exports FR (missions)
            'origine' => 'origin',
            'depart' => 'planned_departure',
            'arrivee' => 'planned_arrival',
            'vehicule' => 'vehicle_plate_number',
            'vehicle' => 'vehicle_plate_number',
            'chauffeur_nom' => 'driver_name',
            'conducteur' => 'driver_name',

            // Compatibilité exports FR (documents)
            'entite' => 'documentable_type',
            'entite_uuid' => 'documentable_uuid',
            'fichier' => 'file_path',
            'fichier_url' => 'file_path',
            'file' => 'file_path',
            'chemin_fichier' => 'file_path',
            'url_fichier' => 'file_path',

            // Alias fréquents
            'licence_number' => 'license_number',
            'license_no' => 'license_number',
        ];

        return $aliases[$header] ?? $header;
    }

    private function hasAnyNonEmptyValue(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function isAssocArray(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}
