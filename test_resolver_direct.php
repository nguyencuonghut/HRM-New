<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$contract = App\Models\Contract::latest()->first();
$resolver = new App\Services\EmploymentResolver();

echo "Contract: {$contract->contract_number}\n";
echo "Status: {$contract->status}\n";
echo "Source: {$contract->source}\n";
echo "Start date: {$contract->start_date}\n";
echo "\n";

$shouldCreate = $resolver->shouldCreateEmployment($contract);
echo "shouldCreateEmployment(): " . ($shouldCreate ? 'YES' : 'NO') . "\n";

if ($shouldCreate) {
    echo "\nCalling attachEmploymentForContract()...\n";
    $employment = $resolver->attachEmploymentForContract($contract);
    
    if ($employment) {
        echo "SUCCESS! Employment ID: {$employment->id}\n";
        $contract->refresh();
        echo "Contract employment_id: {$contract->employment_id}\n";
    } else {
        echo "NO employment created (returned null)\n";
    }
}
