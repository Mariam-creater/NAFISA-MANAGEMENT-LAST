<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\File;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $backupDir = storage_path('app/backups');
        $lastBackupTime = \Illuminate\Support\Facades\Cache::get('last_backup_formatted');

        if (!$lastBackupTime && File::isDirectory($backupDir)) {
            $files = File::files($backupDir);
            if (!empty($files)) {
                usort($files, fn($a, $b) => $b->getMTime() - $a->getMTime());
                $lastBackupTime = date('M d, Y H:i', $files[0]->getMTime());
            }
        }

        return [
            ...parent::share($request),
            'appName' => 'Nafisa Hostel Management System',
            'flash' => [
                'success' => fn () => $request->session()->get('success') ?? ($request->session()->get('flash.type') === 'success' ? $request->session()->get('flash.message') : null),
                'error' => fn () => $request->session()->get('error') ?? ($request->session()->get('flash.type') === 'error' ? $request->session()->get('flash.message') : null),
                'type' => fn () => $request->session()->get('flash.type') ?? ($request->session()->get('success') ? 'success' : ($request->session()->get('error') ? 'error' : null)),
                'message' => fn () => $request->session()->get('flash.message') ?? ($request->session()->get('success') ?? $request->session()->get('error')),
                'newPayment' => fn () => $request->session()->get('new_payment'),
            ],
            'system' => [
                'database' => 'SQLite 3 (Healthy)',
                'lastBackup' => $lastBackupTime ?? 'None yet',
                'environment' => config('app.env'),
            ],
        ];
    }
}
