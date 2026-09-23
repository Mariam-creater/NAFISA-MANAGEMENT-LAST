<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;

class SendWeeklyBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:email {--recipient= : Target Gmail recipient}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a compressed ZIP archive of SQLite database and media files, and emails it to the administrator';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $this->info('Starting automated SQLite & media cloud backup...');

        $recipient = $this->option('recipient') ?: env('BACKUP_MAIL_TO', env('BACKUP_GMAIL_RECIPIENT', config('mail.from.address')));

        try {
            $result = $backupService->createAndEmailBackup($recipient);

            $this->info("Backup archive created: {$result['filename']}");
            $this->line("Size: " . number_format($result['size'] / 1024, 1) . " KB");

            if ($result['email_sent']) {
                $this->info("Dispatched to Gmail recipient: {$result['recipient']}");
            } else {
                $this->warn("Backup archive processed, but email could not be sent (check SMTP settings).");
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Backup failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
