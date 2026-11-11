<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        date_default_timezone_set(config('eoffice.timezone'));
        Carbon::setLocale('id');
        Paginator::useBootstrapFive();

        // @tgl($tanggal) -> 05 Nov 2026
        Blade::directive('tgl', fn ($e) => "<?php echo ($e) ? \\Carbon\\Carbon::parse($e)->translatedFormat('d M Y') : '-'; ?>");
    }
}
