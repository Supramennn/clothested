<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

/**
 * Non-functional requirement: daily PostgreSQL dumps kept 14 days. The dump
 * lands in the local disk as a custom-format archive that pg_restore consumes.
 */
class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--keep-days=14 : Delete dumps older than this many days}';

    protected $description = 'Dump the PostgreSQL database into storage/app/private/backups and prune old dumps';

    public function handle(): int
    {
        $connection = (string) config('database.default');

        if ($connection !== 'pgsql') {
            $this->components->warn("The database dump assumes PostgreSQL; the current connection is [{$connection}].");

            return self::FAILURE;
        }

        $config = (array) config('database.connections.pgsql');
        $filename = 'backups/backup-'.now()->format('Ymd_His').'.dump';

        Storage::disk('local')->makeDirectory('backups');

        $result = Process::timeout(600)
            ->env(['PGPASSWORD' => (string) $config['password']])
            ->run(array_values([
                'pg_dump',
                '-h', (string) $config['host'],
                '-p', (string) $config['port'],
                '-U', (string) $config['username'],
                '-d', (string) $config['database'],
                '-Fc',
                '-f', Storage::disk('local')->path($filename),
                '--no-owner',
            ]));

        if (! $result->successful()) {
            Log::error('The scheduled database backup failed.', [
                'exit_code' => $result->exitCode(),
                'stderr' => $result->errorOutput(),
            ]);

            $this->components->error($result->errorOutput() !== '' ? $result->errorOutput() : 'The pg_dump run exited with a failure.');

            return self::FAILURE;
        }

        $pruned = $this->pruneOldDumps((int) $this->option('keep-days'));

        Log::info('A database backup was stored.', [
            'file' => $filename,
            'pruned' => $pruned,
        ]);

        $this->components->info("Stored [{$filename}]".($pruned > 0 ? " and pruned {$pruned} older dump(s)." : '.'));

        return self::SUCCESS;
    }

    /**
     * Remove dumps older than the configured retention window.
     */
    private function pruneOldDumps(int $keepDays): int
    {
        $cutoff = now()->subDays(max($keepDays, 1))->startOfDay();

        $stale = collect(Storage::disk('local')->files('backups'))
            ->filter(fn (string $file): bool => str_ends_with($file, '.dump'))
            ->filter(fn (string $file): bool => Storage::disk('local')->lastModified($file) < $cutoff->getTimestamp());

        $stale->each(fn (string $file) => Storage::disk('local')->delete($file));

        return $stale->count();
    }
}
