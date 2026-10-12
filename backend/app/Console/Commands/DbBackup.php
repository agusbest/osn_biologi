<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class DbBackup extends Command
{
    protected $signature = 'app:db-backup {--keep=14 : Jumlah backup terakhir yang disimpan}';

    protected $description = 'Backup database (mysqldump MySQL, pg_dump PostgreSQL, salin berkas SQLite)';

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);
        $stamp = now()->format('Ymd_His');
        $conn = config('database.default');
        $cfg = config("database.connections.$conn");

        if ($conn === 'sqlite') {
            $target = "$dir/db_$stamp.sqlite";
            File::copy($cfg['database'], $target);
        } elseif ($conn === 'pgsql') {
            $target = "$dir/db_$stamp.sql";
            $p = new Process([
                'pg_dump', '--no-owner', '--no-privileges',
                '-h', (string) $cfg['host'], '-p', (string) $cfg['port'], '-U', (string) $cfg['username'],
                (string) $cfg['database'],
            ], null, ['PGPASSWORD' => (string) $cfg['password'], 'PGSSLMODE' => (string) ($cfg['sslmode'] ?? 'prefer')]);
            $p->setTimeout(300);
            $p->run();
            if (! $p->isSuccessful()) {
                $this->error('pg_dump gagal: ' . $p->getErrorOutput());

                return self::FAILURE;
            }
            File::put($target, $p->getOutput());
        } else {
            $target = "$dir/db_$stamp.sql";
            $p = new Process([
                'mysqldump', '--single-transaction', '--routines',
                '-h', (string) $cfg['host'], '-P', (string) $cfg['port'], '-u', (string) $cfg['username'],
                (string) $cfg['database'],
            ], null, ['MYSQL_PWD' => (string) $cfg['password']]);
            $p->setTimeout(300);
            $p->run();
            if (! $p->isSuccessful()) {
                $this->error('mysqldump gagal: ' . $p->getErrorOutput());

                return self::FAILURE;
            }
            File::put($target, $p->getOutput());
        }

        $files = collect(File::files($dir))->sortByDesc(fn ($f) => $f->getMTime())->values();
        $files->slice((int) $this->option('keep'))->each(fn ($f) => File::delete($f->getPathname()));

        $this->info("Backup tersimpan: $target");

        return self::SUCCESS;
    }
}
