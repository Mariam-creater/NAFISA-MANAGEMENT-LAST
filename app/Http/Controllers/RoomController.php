<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Block;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    /**
     * Display the dynamic room inventory manager (6 Floors x 2 Blocks)
     */
    public function index(Request $request): Response
    {
        $blocks = Block::with([
            'rooms' => function ($query) {
                $query->with(['activeTenant'])
                      ->orderBy('floor_number')
                      ->orderBy('room_number');
            }
        ])->get();

        // Calculate summary stats
        $stats = [
            'total' => Room::count(),
            'vacant' => Room::where('status', 'vacant')->count(),
            'occupied' => Room::where('status', 'occupied')->count(),
            'maintenance' => Room::where('status', 'maintenance')->count(),
        ];

        return Inertia::render('Rooms/Index', [
            'blocks' => $blocks,
            'stats' => $stats,
        ]);
    }

    /**
     * Update room status, custom_price, or notes
     */
    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'status' => 'required|in:vacant,occupied,maintenance',
            'custom_price' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $room->update($validated);

        return redirect()->back()->with('success', "Room {$room->room_number} updated successfully.");
    }
}
