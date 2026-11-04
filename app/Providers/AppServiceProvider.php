<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Http::globalOptions(function () {
            $caBundle = config('http.ca_bundle') ?: resource_path('certs/cacert.pem');

            if (! is_string($caBundle) || ! is_file($caBundle) || ! is_readable($caBundle)) {
                throw new RuntimeException('Không đọc được bộ chứng chỉ HTTPS. Hãy kiểm tra resources/certs/cacert.pem hoặc cấu hình HTTP_CA_BUNDLE.');
            }

            return ['verify' => $caBundle];
        });
    }
}
