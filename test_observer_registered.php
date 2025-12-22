<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Contract;
use Illuminate\Support\Facades\Event;

echo "🔍 Kiểm tra observers đã đăng ký cho Contract model:\n\n";

// Lấy tất cả listeners cho Contract events
$events = [
    'eloquent.creating: App\Models\Contract',
    'eloquent.created: App\Models\Contract',
    'eloquent.saving: App\Models\Contract',
    'eloquent.saved: App\Models\Contract',
    'eloquent.updating: App\Models\Contract',
    'eloquent.updated: App\Models\Contract',
];

foreach ($events as $event) {
    $listeners = Event::getRawListeners()[$event] ?? [];
    echo "Event: {$event}\n";
    echo "Listeners: " . count($listeners) . "\n";
    if (count($listeners) > 0) {
        foreach ($listeners as $listener) {
            if (is_array($listener)) {
                echo "  - " . json_encode($listener) . "\n";
            } else {
                echo "  - " . gettype($listener) . "\n";
            }
        }
    }
    echo "\n";
}

// Kiểm tra xem Contract model có observers không
$reflection = new ReflectionClass(Contract::class);
echo "\n🔍 Contract model class: " . $reflection->getName() . "\n";
echo "File: " . $reflection->getFileName() . "\n";
