<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Pengaman: tes memakai RefreshDatabase yang MENGHAPUS seluruh isi database.
     * Tes menolak berjalan bila nama database tidak berakhiran "_test".
     */
    protected function setUp(): void
    {
        $db = getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? '');

        if (! str_ends_with((string) $db, '_test')) {
            throw new \RuntimeException(
                'Tes dihentikan: DB_DATABASE harus berakhiran "_test" (sekarang: "'.$db.'"). '.
                'Periksa phpunit.xml agar database asli tidak terhapus.'
            );
        }

        parent::setUp();
    }
}
