<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TicketResource;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Ticket::with('user')->latest();

        if ($user->role === 'client') {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $paginator = $query->paginate(min((int) $request->input('per_page', 15), 100));

        return ApiResponse::paginated($paginator, TicketResource::collection($paginator->items())->resolve(), 'Daftar tiket.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'in:low,medium,high'],
            'message' => ['required', 'string'],
        ]);

        $ticket = Ticket::create([
            'tenant_id' => $request->user()->tenant_id,
            'user_id' => $request->user()->id,
            'subject' => $data['subject'],
            'priority' => $data['priority'],
            'status' => 'open',
        ]);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $data['message'],
        ]);

        return ApiResponse::success(new TicketResource($ticket->load(['user', 'replies.user'])), 'Tiket berhasil dibuat.', 201);
    }

    public function show(Request $request, Ticket $ticket)
    {
        if ($request->user()->role === 'client' && $ticket->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden.', 403, 'FORBIDDEN');
        }

        return ApiResponse::success(new TicketResource($ticket->load(['user', 'replies.user'])), 'Detail tiket.');
    }

    public function reply(Request $request, Ticket $ticket)
    {
        if ($request->user()->role === 'client' && $ticket->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden.', 403, 'FORBIDDEN');
        }

        $data = $request->validate(['message' => ['required', 'string']]);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $data['message'],
        ]);

        if ($request->user()->role === 'client' && in_array($ticket->status, ['resolved', 'closed'])) {
            $ticket->update(['status' => 'open']);
        }

        return ApiResponse::success(new TicketResource($ticket->refresh()->load(['user', 'replies.user'])), 'Balasan berhasil dikirim.');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        if ($request->user()->role === 'client') {
            return ApiResponse::error('Forbidden.', 403, 'FORBIDDEN');
        }

        $data = $request->validate(['status' => ['required', 'in:open,in_progress,resolved,closed']]);
        $ticket->update(['status' => $data['status']]);

        return ApiResponse::success(new TicketResource($ticket->load(['user', 'replies.user'])), 'Status tiket berhasil diperbarui.');
    }
}
