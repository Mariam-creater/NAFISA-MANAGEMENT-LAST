<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BackupController extends Controller
{
    /**
     * Display backup status, history, and system settings
     */
    public function index(): Response
    {
        $backupDir = storage_path('app/backups');
        $backups = [];

        if (File::isDirectory($backupDir)) {
            $files = File::files($backupDir);
            usort($files, fn($a, $b) => $b->getMTime() - $a->getMTime());

            foreach ($files as $file) {
                if ($file->getExtension() === 'zip') {
                    $backups[] = [
                        'filename' => $file->getFilename(),
                        'size' => $file->getSize(),
                        'formatted_size' => number_format($file->getSize() / 1024, 1) . ' KB',
                        'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
                        'download_url' => route('backups.download', ['filename' => $file->getFilename()]),
                    ];
                }
            }
        }

        // SQLite diagnostics
        $integrityCheck = DB::select('PRAGMA integrity_check;');
        $integrityStatus = (!empty($integrityCheck) && ($integrityCheck[0]->integrity_check ?? '') === 'ok') ? 'Optimal / Clean' : 'Error Detected';
        $sqliteSize = File::exists(database_path('database.sqlite')) ? File::size(database_path('database.sqlite')) : 0;

        return Inertia::render('Settings/Index', [
            'backups' => $backups,
            'system' => [
                'sqlite_size' => number_format($sqliteSize / 1024, 1) . ' KB',
                'integrity_status' => $integrityStatus,
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'backup_recipient' => env('BACKUP_MAIL_TO', env('BACKUP_GMAIL_RECIPIENT', config('mail.from.address'))),
                'smtp_host' => config('mail.mailers.smtp.host'),
            ],
        ]);
    }

    /**
     * Manually trigger Cloud Backup to Gmail
     */
    public function triggerManualBackup(Request $request)
    {
        try {
            $exitCode = Artisan::call('backup:email');

            if ($exitCode === 0) {
                $recipient = env('BACKUP_MAIL_TO', env('BACKUP_GMAIL_RECIPIENT', config('mail.from.address', 'nafisa.hostel.system@gmail.com')));
                $title = 'Backup Dispatched Successfully';
                $description = "Database and assets packaged and sent to {$recipient}.";

                return back()
                    ->with('success', $description)
                    ->with('flash', [
                        'type' => 'success',
                        'title' => $title,
                        'description' => $description,
                        'message' => $description,
                    ]);
            }

            return back()->with('flash', [
                'type' => 'error',
                'title' => 'Backup Failed',
                'description' => 'An error occurred while creating or sending the cloud backup.',
                'message' => 'An error occurred while creating or sending the cloud backup.'
            ])->with('error', 'An error occurred while creating or sending the cloud backup.');
        } catch (\Throwable $e) {
            return back()->with('flash', [
                'type' => 'error',
                'title' => 'Backup Failed',
                'description' => 'An error occurred while creating or sending the cloud backup: ' . $e->getMessage(),
                'message' => 'An error occurred while creating or sending the cloud backup.'
            ])->with('error', 'An error occurred while creating or sending the cloud backup.');
        }
    }

    /**
     * Alias for backward compatibility
     */
    public function trigger(Request $request)
    {
        return $this->triggerManualBackup($request);
    }

    /**
     * Download backup ZIP
     */
    public function download(string $filename)
    {
        $cleanFilename = basename($filename);
        $path = storage_path("app/backups/{$cleanFilename}");

        if (!File::exists($path)) {
            abort(404, 'Backup archive not found.');
        }

        return response()->download($path);
    }
}
