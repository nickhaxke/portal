<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class BackupController extends Controller
{
    public function index(): View
    {
        $backupDir = database_path('backups');
        if (! File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $files = File::files($backupDir);
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'name' => $file->getFilename(),
                'size_bytes' => $file->getSize(),
                'size_formatted' => $this->formatBytes($file->getSize()),
                'modified_at' => date('Y-m-d H:i:s', $file->getMTime()),
            ];
        }

        // Sort latest backups first
        usort($backups, fn ($a, $b) => strcmp($b['modified_at'], $a['modified_at']));

        return view('admin.backups.index', compact('backups'));
    }

    public function createBackup(): RedirectResponse
    {
        $backupDir = database_path('backups');
        if (! File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $filename = 'portal_backup_'.date('Y_m_d_His').'.sql';
        $targetPath = $backupDir.DIRECTORY_SEPARATOR.$filename;

        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        $dbPort = config('database.connections.mysql.port', '3306');
        $dbName = config('database.connections.mysql.database', 'portal_db');
        $dbUser = config('database.connections.mysql.username', 'root');
        $dbPass = config('database.connections.mysql.password', '');

        // Use mysqldump if available, or generate SQL dump
        $mysqldump = 'c:\\wamp64\\bin\\mysql\\mysql9.1.0\\bin\\mysqldump.exe';
        if (! file_exists($mysqldump)) {
            $mysqldump = 'mysqldump';
        }

        $passArg = $dbPass !== '' ? "-p\"{$dbPass}\"" : '';
        $cmd = "\"{$mysqldump}\" -h {$dbHost} -P {$dbPort} -u {$dbUser} {$passArg} {$dbName} > \"{$targetPath}\"";

        @exec($cmd, $output, $returnVar);

        if ($returnVar === 0 && file_exists($targetPath) && filesize($targetPath) > 0) {
            AuditLogService::log('system.backup_create', null, null, ['filename' => $filename, 'size' => filesize($targetPath)]);

            return redirect()->route('admin.backups.index')
                ->with('success', "Database backup created successfully: {$filename} (".$this->formatBytes(filesize($targetPath)).')');
        }

        return redirect()->route('admin.backups.index')
            ->with('error', 'Backup creation failed. Please verify mysqldump path and permissions.');
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}
