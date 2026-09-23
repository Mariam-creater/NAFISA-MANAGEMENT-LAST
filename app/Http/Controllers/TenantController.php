<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Room;
use App\Models\Block;
use App\Services\NotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class TenantController extends Controller
{
    /**
     * Display a listing of active and archived tenants with filters.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $blockId = $request->input('block_id');
        $floorNumber = $request->input('floor_number');
        $occupation = $request->input('occupation');
        $dueFilter = $request->input('due_status'); // 'overdue', 'due_soon', 'upcoming'

        // Active tenants query
        $activeQuery = Tenant::with(['room.block', 'payments'])
            ->where('status', 'active');

        if ($search) {
            $activeQuery->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('whatsapp_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($blockId) {
            $activeQuery->whereHas('room', fn($q) => $q->where('block_id', $blockId));
        }

        if ($floorNumber) {
            $activeQuery->whereHas('room', fn($q) => $q->where('floor_number', $floorNumber));
        }

        if ($occupation) {
            $activeQuery->where('occupation', $occupation);
        }

        $activeTenants = $activeQuery->orderByDesc('id')->get();

        // Apply in-memory due filter if specified
        if ($dueFilter) {
            $activeTenants = $activeTenants->filter(fn ($t) => $t->due_status === $dueFilter)->values();
        }

        // Archived (soft-deleted) tenants for 1-Year Retention tab
        $archivedTenants = Tenant::onlyTrashed()
            ->with(['payments'])
            ->orderByDesc('deleted_at')
            ->get();

        // Vacant rooms available for assignment (grouped by block & floor)
        $vacantRooms = Room::with('block')
            ->where('status', 'vacant')
            ->orderBy('block_id')
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get();

        $blocks = Block::all();

        return Inertia::render('Tenants/Index', [
            'activeTenants' => $activeTenants,
            'archivedTenants' => $archivedTenants,
            'vacantRooms' => $vacantRooms,
            'blocks' => $blocks,
            'filters' => [
                'search' => $search,
                'block_id' => $blockId,
                'floor_number' => $floorNumber,
                'occupation' => $occupation,
                'due_status' => $dueFilter,
            ],
        ]);
    }

    /**
     * Store a newly registered tenant.
     */
    public function store(Request $request)
    {
        // Default rent_due_date to check_in_date + 30 days if not set
        if (empty($request->input('rent_due_date')) && !empty($request->input('check_in_date'))) {
            $request->merge([
                'rent_due_date' => Carbon::parse($request->input('check_in_date'))->addDays(30)->toDateString()
            ]);
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'age' => 'required|numeric|min:16',
            'email' => 'required|email|max:255',
            'whatsapp_number' => 'required|string|max:50',
            'occupation' => 'required|string',
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date',
            'rent_due_date' => 'required|date',
            'emergency_name' => 'required|string|max:255',
            'emergency_phone' => 'required|string|max:50',
            'emergency_relationship' => 'required|string|max:100',
            'avatar' => 'nullable|image|max:5120',
            'id_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        DB::transaction(function () use ($request, $validated) {
            $avatarPath = $request->hasFile('avatar') 
                ? $request->file('avatar')->store('tenants/avatars', 'public') 
                : null;

            $idDocPath = $request->hasFile('id_document') 
                ? $request->file('id_document')->store('tenants/documents', 'public') 
                : null;

            Tenant::create([
                'room_id' => $validated['room_id'],
                'full_name' => $validated['full_name'],
                'age' => $validated['age'],
                'email' => $validated['email'],
                'whatsapp_number' => $validated['whatsapp_number'],
                'occupation' => $validated['occupation'],
                'emergency_name' => $validated['emergency_name'],
                'emergency_phone' => $validated['emergency_phone'],
                'emergency_relationship' => $validated['emergency_relationship'],
                'check_in_date' => $validated['check_in_date'],
                'rent_due_date' => $validated['rent_due_date'],
                'status' => 'active',
                'current_balance' => 0,
                'avatar_path' => $avatarPath,
                'id_document_path' => $idDocPath,
            ]);

            Room::where('id', $validated['room_id'])->update([
                'status' => 'occupied'
            ]);
        });

        return redirect()->back()
            ->with('success', 'New resident record created and room assigned.')
            ->with('flash', [
                'type' => 'success',
                'title' => 'Resident Registered Successfully',
                'message' => 'New resident record created and room assigned.'
            ]);
    }

    /**
     * Update existing tenant profile.
     */
    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'age' => 'required|numeric|min:16',
            'email' => 'required|email|max:255',
            'whatsapp_number' => 'required|string|max:50',
            'occupation' => 'required|string',
            'emergency_name' => 'required|string|max:255',
            'emergency_phone' => 'required|string|max:50',
            'emergency_relationship' => 'required|string|max:100',
            'room_id' => 'nullable|exists:rooms,id',
            'check_in_date' => 'required|date',
            'rent_due_date' => 'required|date',
            'status' => 'required|in:active,inactive',
            'avatar' => 'nullable|image|max:5120',
            'id_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // Handle room changes
        $oldRoomId = $tenant->room_id;
        $newRoomId = $validated['room_id'] ?? null;

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('tenants/avatars', 'public');
            $validated['avatar_path'] = $path;
        }

        if ($request->hasFile('id_document')) {
            $path = $request->file('id_document')->store('tenants/documents', 'public');
            $validated['id_document_path'] = $path;
        }

        DB::transaction(function () use ($tenant, $validated, $oldRoomId, $newRoomId) {
            if ($oldRoomId != $newRoomId) {
                if ($oldRoomId) {
                    Room::where('id', $oldRoomId)->update(['status' => 'vacant']);
                }
                if ($newRoomId) {
                    Room::where('id', $newRoomId)->update(['status' => 'occupied']);
                }
            }
            $tenant->update($validated);
        });

        $desc = 'Resident information has been saved successfully.';
        return redirect()->back()->with([
            'success' => $desc,
            'flash' => [
                'type' => 'success',
                'title' => 'Resident Details Updated',
                'description' => $desc,
                'message' => $desc,
            ],
        ]);
    }

    /**
     * Checkout lease (Soft Deletes tenant record into 1-Year Retention Archive).
     */
    public function destroy(Tenant $tenant)
    {
        $tenant->loadMissing('room');
        $name = $tenant->full_name;
        $roomNumber = $tenant->room ? "Room {$tenant->room->room_number}" : 'Assigned Unit';

        DB::transaction(function () use ($tenant) {
            // Release room to vacant
            if ($tenant->room_id) {
                Room::where('id', $tenant->room_id)->update(['status' => 'vacant']);
            }

            $tenant->update([
                'status' => 'inactive',
                'room_id' => null,
            ]);

            $tenant->delete(); // Soft delete into 1-year archive
        });

        $desc = "{$name} has been archived. {$roomNumber} is now vacant.";
        return redirect()->back()->with([
            'success' => $desc,
            'flash' => [
                'type' => 'success',
                'title' => 'Resident Checked Out',
                'description' => $desc,
                'message' => $desc,
            ],
        ]);
    }

    /**
     * Restore soft-deleted tenant.
     */
    public function restore($id)
    {
        $tenant = Tenant::onlyTrashed()->findOrFail($id);
        
        DB::transaction(function () use ($tenant) {
            $tenant->restore();
            $tenant->update(['status' => 'active']);
        });

        $desc = "{$tenant->full_name} has been restored to active residents.";
        return redirect()->back()->with([
            'success' => $desc,
            'flash' => [
                'type' => 'success',
                'title' => 'Resident Restored',
                'description' => $desc,
                'message' => $desc,
            ],
        ]);
    }

    /**
     * Permanently prune tenant record.
     */
    public function forceDelete($id)
    {
        $tenant = Tenant::onlyTrashed()->findOrFail($id);
        
        // Remove uploaded files
        if ($tenant->avatar_path) Storage::disk('public')->delete($tenant->avatar_path);
        if ($tenant->id_document_path) Storage::disk('public')->delete($tenant->id_document_path);

        $tenant->forceDelete();

        return redirect()->back()->with('success', 'Tenant record permanently deleted.');
    }

    /**
     * Trigger instant rent alert reminder for a tenant.
     */
    public function sendReminder(Tenant $tenant, NotificationService $notificationService)
    {
        $notificationService->dispatchRentDueReminder($tenant, $tenant->remaining_due_days, ['email', 'whatsapp', 'sms']);

        return redirect()->back()->with('success', "Rent reminders dispatched to {$tenant->full_name} via Email, WhatsApp & SMS.");
    }

    /**
     * Export Historical Dossier PDF for a tenant.
     */
    public function dossier($id)
    {
        $tenant = Tenant::withTrashed()->with(['payments.room.block'])->findOrFail($id);

        $pdf = Pdf::loadView('tenants.dossier', [
            'tenant' => $tenant,
            'payments' => $tenant->payments,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("Nafisa_Tenant_Dossier_{$tenant->id}.pdf");
    }
}
