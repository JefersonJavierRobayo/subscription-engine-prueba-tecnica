<?php
use Illuminate\Support\Facades\Artisan;

Artisan::command('billing:run', function () {
    $this->info(json_encode(
        app(\App\Services\BillingEngineService::class)->run(),
        JSON_PRETTY_PRINT
    ));
})->purpose('Ejecuta el motor de cobro');
