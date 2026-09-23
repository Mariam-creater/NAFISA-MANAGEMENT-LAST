<?php

namespace App\Services;

use App\Mail\DatabaseBackupMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class BackupService
{
    /**
     * Run full backup and dispatch to Gmail
     */
    public function createAndEmailBackup(?string $recipientEmail = null): array
    {
        // 1. Check SQLite Integrity
        $integrityCheck = DB::select('PRAGMA integrity_check;');
        $integrityOk = !empty($integrityCheck) && ($integrityCheck[0]->integrity_check ?? '') === 'ok';

        if (!$integrityOk) {
            throw new \RuntimeException('SQLite database integrity check failed. Aborting backup.');
        }

        // 2. Prepare directories
        $backupDir = storage_path('app/backups');
        if (!File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = date('Y-m-d_His');
        $zipFilename = "NafisaHostel_Backup_{$timestamp}.zip";
        $zipFilePath = "{$backupDir}/{$zipFilename}";

        // 3. Create ZIP Archive
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Unable to create zip file at {$zipFilePath}");
        }

        // Add SQLite database file
        $sqlitePath = database_path('database.sqlite');
        if (File::exists($sqlitePath)) {
            $zip->addFile($sqlitePath, 'database/database.sqlite');
        }

        // Add uploaded media files from storage/app/public (avatars, id documents, receipts)
        $publicStorage = storage_path('app/public');
        if (File::isDirectory($publicStorage)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($publicStorage, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = 'storage/' . substr($filePath, strlen($publicStorage) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }

        // Add system manifest
        $manifest = [
            'app' => 'Nafisa Hostel Management System',
            'version' => '1.0.0',
            'created_at' => date('Y-m-d H:i:s'),
            'integrity_status' => 'OK',
            'sqlite_size' => File::exists($sqlitePath) ? File::size($sqlitePath) : 0,
        ];
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
        $zip->close();

        $filesize = File::size($zipFilePath);

        // 4. Dispatch Email to Gmail
        $recipient = $recipientEmail ?? config('mail.backup_recipient', env('BACKUP_GMAIL_RECIPIENT', config('mail.from.address')));
        $emailSent = false;

        if ($recipient) {
            try {
                Mail::to($recipient)->send(new DatabaseBackupMail(
                    $zipFilePath,
                    $zipFilename,
                    $filesize,
                    date('d M Y, H:i')
                ));
                $emailSent = true;
            } catch (\Throwable $e) {
                Log::error("Failed to email backup to {$recipient}: {$e->getMessage()}");
            }
        }

        // Retain only the 5 most recent backups locally to save disk space
        $this->pruneOldBackups($backupDir, 5);

        return [
            'success' => true,
            'filename' => $zipFilename,
            'path' => $zipFilePath,
            'size' => $filesize,
            'recipient' => $recipient,
            'email_sent' => $emailSent,
            'timestamp' => $timestamp,
        ];
    }

    /**
     * Prune older backups keeping only the latest $keep
     */
    protected function pruneOldBackups(string $dir, int $keep = 5): void
    {
        $files = File::files($dir);
        if (count($files) > $keep) {
            usort($files, fn($a, $b) => $b->getMTime() - $a->getMTime());
            $toDelete = array_slice($files, $keep);
            foreach ($toDelete as $file) {
                File::delete($file->getRealPath());
            }
        }
    }
}
