<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Analytics\DTOs\AnalyticsFilters;
use App\Analytics\Services\ProjectAnalyticsService;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

$service = $app->make(ProjectAnalyticsService::class);

foreach (User::orderBy('id')->get() as $u) {
    $visible = Project::query()->visibleTo($u)->orderBy('name')->pluck('id')
        ->map(fn ($id) => (int) $id)->all();

    $request = Request::create('/api/dashboard', 'GET', ['timezone' => 'UTC']);
    $request->setUserResolver(fn () => $u);

    $filters = AnalyticsFilters::fromRequest($request, $visible);
    $payload = $service->dashboard($filters);

    echo sprintf(
        "%s visible=%d period=%s total=%d open=%d created=%d resolved=%d status_rows=%d\n",
        $u->email,
        count($visible),
        $payload['period'] ? $payload['period']['start'] . '..' . $payload['period']['end'] : 'NULL',
        $payload['summary']['total_issues'],
        $payload['summary']['open_issues'],
        $payload['summary']['created_in_period'],
        $payload['summary']['resolved_in_period'],
        count($payload['status_distribution']),
    );
}
