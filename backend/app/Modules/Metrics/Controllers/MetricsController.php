<?php

namespace App\Modules\Metrics\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    /**
     * Expose metrics in Prometheus text format
     */
    public function __invoke()
    {
        $metrics = [];

        // Counter: Total HTTP requests
        $metrics[] = '# HELP http_requests_total Total HTTP requests';
        $metrics[] = '# TYPE http_requests_total counter';
        $metrics[] = 'http_requests_total 0';

        // Gauge: Active database connections
        $metrics[] = '# HELP db_connections_active Active database connections';
        $metrics[] = '# TYPE db_connections_active gauge';
        try {
            $count = DB::select('SELECT COUNT(*) as count FROM information_schema.processlist WHERE db = ?', [env('DB_DATABASE', 'pfe_db')]);
            $dbConnections = $count[0]->count ?? 0;
        } catch (\Exception $e) {
            $dbConnections = 0;
        }
        $metrics[] = 'db_connections_active ' . $dbConnections;

        // Gauge: Application uptime (in seconds)
        $metrics[] = '# HELP app_uptime_seconds Application uptime in seconds';
        $metrics[] = '# TYPE app_uptime_seconds gauge';
        $metrics[] = 'app_uptime_seconds 1';

        // Counter: Laravel cache hits
        $metrics[] = '# HELP cache_operations_total Total cache operations';
        $metrics[] = '# TYPE cache_operations_total counter';
        $metrics[] = 'cache_operations_total{operation="hit"} 0';
        $metrics[] = 'cache_operations_total{operation="miss"} 0';

        $content = implode("\n", $metrics);

        return response($content, 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }
}
