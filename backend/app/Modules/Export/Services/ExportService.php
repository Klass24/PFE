<?php

namespace App\Modules\Export\Services;

use App\Modules\Document\Models\Document;
use App\Modules\Driver\Models\Driver;
use App\Modules\Expense\Models\Expense;
use App\Modules\Incident\Models\Incident;
use App\Modules\Maintenance\Models\MaintenanceSchedule;
use App\Modules\Mission\Models\Mission;
use App\Modules\User\Models\User;
use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    // ─── Vehicles ───────────────────────────────────────────────────────

    public function exportVehicles(array $filters, string $format): StreamedResponse
    {
        $query = Vehicle::query()
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['plate_number'] ?? null, fn($q, $v) => $q->where('plate_number', 'like', "%{$v}%"))
            ->when($filters['brand'] ?? null, fn($q, $v) => $q->where('brand', 'like', "%{$v}%"))
            ->when($filters['model'] ?? null, fn($q, $v) => $q->where('model', 'like', "%{$v}%"))
            ->when($filters['type'] ?? null, fn($q, $v) => $q->where('type', $v))
            ->when($filters['fuel_type'] ?? null, fn($q, $v) => $q->where('fuel_type', $v))
            ->when($filters['from'] ?? null, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn($q, $v) => $q->whereDate('created_at', '<=', $v));

        $headers = ['Plaque', 'Châssis', 'Marque', 'Modèle', 'Année', 'Type', 'Carburant', 'Kilométrage', 'Statut', 'Créé le'];
        $mapper = fn(Vehicle $v) => [
             $v->plate_number, $v->chassis_number ?? '-', $v->brand, $v->model,
            $v->year, $v->type ?? '-', $v->fuel_type ?? '-', $v->mileage, $v->status,
            $v->created_at?->format('d/m/Y'),
        ];

        return $this->export('vehicules', $query, $headers, $mapper, $format);
    }

    // ─── Drivers ────────────────────────────────────────────────────────

    public function exportDrivers(array $filters, string $format): StreamedResponse
    {
        $query = Driver::with('user:id,name,email')
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['name'] ?? null, fn($q, $v) => $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$v}%")))
            ->when($filters['email'] ?? null, fn($q, $v) => $q->whereHas('user', fn($u) => $u->where('email', 'like', "%{$v}%")))
            ->when($filters['license_number'] ?? null, fn($q, $v) => $q->where('license_number', 'like', "%{$v}%"))
            ->when($filters['from'] ?? null, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn($q, $v) => $q->whereDate('created_at', '<=', $v));

        $headers = ['UUID', 'Nom', 'Email', 'N° Permis', 'Expiration permis', 'Téléphone', 'Statut', 'Créé le'];
        $mapper = fn(Driver $d) => [
            $d->uuid, $d->user?->name ?? '-', $d->user?->email ?? '-',
            $d->license_number, $d->license_expiry?->format('d/m/Y') ?? '-',
            $d->phone ?? '-', $d->status, $d->created_at?->format('d/m/Y'),
        ];

        return $this->export('chauffeurs', $query, $headers, $mapper, $format);
    }

    // ─── Missions ───────────────────────────────────────────────────────

    public function exportMissions(array $filters, string $format): StreamedResponse
    {
        $query = Mission::with(['vehicle', 'driver.user'])
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['origin'] ?? null, fn($q, $v) => $q->where('origin', 'like', "%{$v}%"))
            ->when($filters['destination'] ?? null, fn($q, $v) => $q->where('destination', 'like', "%{$v}%"))
            ->when($filters['from'] ?? null, fn($q, $v) => $q->whereDate('start_date', '>=', $v))
            ->when($filters['to'] ?? null, fn($q, $v) => $q->whereDate('start_date', '<=', $v));

        $headers = ['UUID', 'vehicle_uuid', 'driver_uuid', 'origin', 'destination', 'planned_departure', 'planned_arrival', 'status', 'notes', 'Chauffeur', 'Véhicule'];
        $mapper = fn(Mission $m) => [
            $m->uuid,
            $m->vehicle?->uuid ?? '',
            $m->driver?->uuid ?? '',
            $m->origin,
            $m->destination,
            $m->start_date?->format('Y-m-d H:i:s') ?? '',
            $m->end_date?->format('Y-m-d H:i:s') ?? '',
            $m->status,
            $m->notes ?? '',
            $m->driver?->user?->name ?? '-',
            $m->vehicle?->plate_number ?? '-',
        ];

        return $this->export('missions', $query, $headers, $mapper, $format);
    }

    // ─── Expenses ───────────────────────────────────────────────────────

    public function exportExpenses(array $filters, string $format): StreamedResponse
    {
        $query = Expense::with('mission')
            ->when($filters['type'] ?? null, fn($q, $v) => $q->where('type', $v))
            ->when($filters['from'] ?? null, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn($q, $v) => $q->whereDate('created_at', '<=', $v));

        $headers = ['UUID', 'Mission', 'Type', 'Montant', 'Litres', 'Kilométrage', 'Date saisie', 'Sync'];
        $mapper = fn(Expense $e) => [
            $e->uuid, $e->mission?->uuid ?? '-', $e->type, $e->amount,
            $e->liters ?? '-', $e->mileage ?? '-',
            $e->date_saisie_reelle?->format('d/m/Y H:i') ?? $e->created_at->format('d/m/Y H:i'),
            $e->sync_status ?? 'synced',
        ];

        return $this->export('depenses', $query, $headers, $mapper, $format);
    }

    // ─── Incidents ──────────────────────────────────────────────────────

    public function exportIncidents(array $filters, string $format): StreamedResponse
    {
        $query = Incident::with('mission')
            ->when($filters['type'] ?? null, fn($q, $v) => $q->where('type', $v))
            ->when($filters['sync_status'] ?? null, fn($q, $v) => $q->where('sync_status', $v))
            ->when($filters['description'] ?? null, fn($q, $v) => $q->where('description', 'like', "%{$v}%"))
            ->when($filters['from'] ?? null, fn($q, $v) => $q->whereDate('reported_at', '>=', $v))
            ->when($filters['to'] ?? null, fn($q, $v) => $q->whereDate('reported_at', '<=', $v));

        $headers = ['UUID', 'Mission', 'Type', 'Description', 'Latitude', 'Longitude', 'Signalé le'];
        $mapper = fn(Incident $i) => [
            $i->uuid, $i->mission?->uuid ?? '-', $i->type, $i->description,
            $i->latitude ?? '-', $i->longitude ?? '-',
            $i->reported_at?->format('d/m/Y H:i') ?? $i->created_at->format('d/m/Y H:i'),
        ];

        return $this->export('incidents', $query, $headers, $mapper, $format);
    }

    // ─── Documents ──────────────────────────────────────────────────────

    public function exportDocuments(array $filters, string $format): StreamedResponse
    {
        $query = Document::query()
            ->with('documentable')
            ->when($filters['type'] ?? null, fn($q, $v) => $q->where('type', $v))
            ->when($filters['documentable_type'] ?? null, fn($q, $v) => $q->where('documentable_type', 'like', "%{$v}%"))
            ->when(array_key_exists('alert_sent', $filters) && $filters['alert_sent'] !== null && $filters['alert_sent'] !== '', fn($q) => $q->where('alert_sent', filter_var($filters['alert_sent'], FILTER_VALIDATE_BOOLEAN)))
            ->when($filters['status'] ?? null, function ($q, $v) {
                if ($v === 'expired') {
                    $q->whereDate('expiry_date', '<', now()->toDateString());
                    return;
                }
                if ($v === 'expiring') {
                    $q->whereDate('expiry_date', '>=', now()->toDateString())
                      ->whereDate('expiry_date', '<=', now()->addDays(30)->toDateString());
                    return;
                }
                if ($v === 'valid') {
                    $q->where(function ($sq) {
                        $sq->whereNull('expiry_date')
                           ->orWhereDate('expiry_date', '>', now()->addDays(30)->toDateString());
                    });
                }
            })
            ->when($filters['from'] ?? null, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn($q, $v) => $q->whereDate('created_at', '<=', $v));

        $headers = ['UUID', 'documentable_type', 'documentable_uuid', 'type', 'file_path', 'issue_date', 'expiry_date', 'alert_sent', 'Entité'];
        $mapper = fn(Document $d) => [
            $d->uuid,
            str_contains(strtolower((string) $d->documentable_type), 'vehicle') ? 'vehicle' : (str_contains(strtolower((string) $d->documentable_type), 'driver') ? 'driver' : $d->documentable_type),
            $d->documentable?->uuid ?? '',
            $d->type,
            $d->file_path,
            $d->issue_date?->format('Y-m-d') ?? '',
            $d->expiry_date?->format('Y-m-d') ?? '',
            $d->alert_sent ? 'true' : 'false',
            $d->documentable_type ?? '-',
        ];

        return $this->export('documents', $query, $headers, $mapper, $format);
    }

    public function exportMaintenance(array $filters, string $format): StreamedResponse
    {
        $query = MaintenanceSchedule::with('vehicle')
            ->when($filters['maintenance_type'] ?? null, fn($q, $v) => $q->where('maintenance_type', $v))
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['vehicle_uuid'] ?? null, fn($q, $v) => $q->whereHas('vehicle', fn($vq) => $vq->where('uuid', $v)))
            ->when($filters['vehicle_search'] ?? null, fn($q, $v) => $q->whereHas('vehicle', fn($vq) => $vq
                ->where('plate_number', 'like', "%{$v}%")
                ->orWhere('brand', 'like', "%{$v}%")
                ->orWhere('model', 'like', "%{$v}%")))
            ->when($filters['from'] ?? null, fn($q, $v) => $q->whereDate('next_due_date', '>=', $v))
            ->when($filters['to'] ?? null, fn($q, $v) => $q->whereDate('next_due_date', '<=', $v));

        $headers = ['UUID', 'Type', 'Vehicule', 'Statut', 'Dernier service', 'Prochaine echeance', 'Prochain km', 'Notes', 'Cree le'];
        $mapper = fn(MaintenanceSchedule $m) => [
            $m->uuid,
            $m->maintenance_type,
            $m->vehicle?->plate_number ?? '-',
            $m->status,
            $m->last_service_date?->format('d/m/Y') ?? '-',
            $m->next_due_date?->format('d/m/Y') ?? '-',
            $m->next_due_km ?? '-',
            $m->notes ?? '-',
            $m->created_at?->format('d/m/Y'),
        ];

        return $this->export('maintenance', $query, $headers, $mapper, $format);
    }

    public function exportUsers(array $filters, string $format): StreamedResponse
    {
        $query = User::query()
            ->when($filters['role'] ?? null, fn($q, $v) => $q->whereHas('roles', fn($r) => $r->where('name', $v)))
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('is_active', $v === 'active'))
            ->when($filters['name'] ?? null, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($filters['email'] ?? null, fn($q, $v) => $q->where('email', 'like', "%{$v}%"));

        $headers = ['UUID', 'Nom', 'Email', 'Role', 'Actif', 'Cree le'];
        $mapper = fn(User $u) => [
            $u->uuid,
            $u->name,
            $u->email,
            $u->roles->pluck('name')->implode(', '),
            $u->is_active ? 'Oui' : 'Non',
            $u->created_at?->format('d/m/Y'),
        ];

        return $this->export('utilisateurs', $query, $headers, $mapper, $format);
    }

    // ─── Generic export engine ──────────────────────────────────────────

    private function export(string $name, Builder $query, array $headers, callable $mapper, string $format): StreamedResponse
    {
        $filename = $name . '_' . now()->format('Ymd');

        Log::info("Export {$format}", ['file' => $filename, 'user' => auth()->id()]);

        if ($format === 'xlsx') {
            return $this->streamXlsx($filename . '.xlsx', $query, $headers, $mapper);
        }

        return $this->streamCsv($filename . '.csv', $query, $headers, $mapper);
    }

    private function streamCsv(string $filename, Builder $query, array $headers, callable $mapper): StreamedResponse
    {
        return response()->streamDownload(function () use ($query, $headers, $mapper) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $headers, ';');

            $query->chunk(200, function ($items) use ($handle, $mapper) {
                foreach ($items as $item) {
                    fputcsv($handle, $mapper($item), ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function streamXlsx(string $filename, Builder $query, array $headers, callable $mapper): StreamedResponse
    {
        return response()->streamDownload(function () use ($query, $headers, $mapper) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // En-têtes en gras
            foreach ($headers as $col => $header) {
                $cell = $sheet->getCellByColumnAndRow($col + 1, 1);
                $cell->setValue($header);
                $cell->getStyle()->getFont()->setBold(true);
            }

            $row = 2;
            $query->chunk(200, function ($items) use ($sheet, $mapper, &$row) {
                foreach ($items as $item) {
                    $values = $mapper($item);
                    foreach ($values as $col => $value) {
                        $sheet->setCellValueByColumnAndRow($col + 1, $row, $value);
                    }
                    $row++;
                }
            });

            // Auto-size columns
            foreach (range(1, count($headers)) as $col) {
                $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
