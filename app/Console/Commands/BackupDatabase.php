<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackupDatabase extends Command
{
    protected $signature = 'sistema:backup {--dir= : Directorio de destino (por defecto storage/app/backup)}';
    protected $description = 'Genera un backup manual de la base de datos (mysqldump)';

    public function handle(): int
    {
        $db = config('database.connections.mysql');
        $host = $db['host'];
        $port = $db['port'];
        $database = $db['database'];
        $username = $db['username'];
        $password = $db['password'];

        $dir = $this->option('dir') ?: storage_path('app/backup');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filename = 'realcomputer_' . now()->format('Ymd_His') . '.sql';
        $path = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . $filename;

        $this->info('Generando backup de la base de datos: ' . $database);
        $this->line('Destino: ' . $path);

        $mysqldump = $this->findMysqldump();
        $command = sprintf(
            '"%s" -h %s -P %s -u %s %s %s > "%s"',
            $mysqldump,
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            $password ? '-p' . escapeshellarg($password) : '',
            escapeshellarg($database),
            $path
        );

        $output = [];
        $returnCode = null;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Error al generar el backup. Código: ' . $returnCode);
            return 1;
        }

        if (!file_exists($path)) {
            $this->error('No se generó el archivo de backup.');
            return 1;
        }

        $size = round(filesize($path) / 1024 / 1024, 2);
        $this->info('Backup generado correctamente: ' . $path . ' (' . $size . ' MB)');

        return 0;
    }

    private function findMysqldump(): string
    {
        $possiblePaths = [
            'mysqldump',
            'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe',
            'C:\\laragon\\bin\\mysql\\mariadb-10.6.27-winx64\\bin\\mysqldump.exe',
        ];

        foreach ($possiblePaths as $p) {
            if (file_exists($p) || $p === 'mysqldump') {
                return $p;
            }
        }

        return 'mysqldump';
    }
}