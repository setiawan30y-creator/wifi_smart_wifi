<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Ticket::with(['user', 'customer.package', 'assignee'])->latest();

        if ($user->role === 'client') {
            $tickets = $query->where('user_id', $user->id)->paginate(10);

            return view('tickets.client_index', compact('tickets'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tickets = $query->paginate(15);

        return view('tickets.index', compact('tickets'));
    }

    public function create()
    {
        $user = auth()->user();
        $customers = collect();

        if ($user->role !== 'client') {
            $customers = Customer::query()
                ->where('tenant_id', $user->tenant_id)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'phone', 'address', 'package_id', 'status']);
        } else {
            $customers = Customer::query()
                ->where('tenant_id', $user->tenant_id)
                ->where(function ($query) use ($user) {
                    $query->where('email', $user->email);

                    if ($user->phone) {
                        $query->orWhere('phone', $user->phone);
                    }
                })
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'phone', 'address', 'package_id', 'status']);
        }

        return view('tickets.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $rules = [
            'subject' => ['required', 'string', 'max:255'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'message' => ['required', 'string'],
        ];

        if ($user->role !== 'client') {
            $rules['customer_id'] = [
                'required',
                Rule::exists('customers', 'id')->where(function ($query) use ($user) {
                    $query->where('tenant_id', $user->tenant_id);
                }),
            ];
        } else {
            $rules['customer_id'] = [
                'nullable',
                Rule::exists('customers', 'id')->where(function ($query) use ($user) {
                    $query->where('tenant_id', $user->tenant_id)
                        ->where(function ($subQuery) use ($user) {
                            $subQuery->where('email', $user->email);

                            if ($user->phone) {
                                $subQuery->orWhere('phone', $user->phone);
                            }
                        });
                }),
            ];
        }

        $validated = $request->validate($rules);
        $customer = !empty($validated['customer_id'])
            ? Customer::where('tenant_id', $user->tenant_id)->findOrFail($validated['customer_id'])
            : null;

        $ticket = Ticket::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'customer_id' => $customer?->id,
            'subject' => $validated['subject'],
            'priority' => $validated['priority'],
            'status' => 'open',
        ]);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $validated['message'],
        ]);

        if ($user->role === 'client') {
            $this->notifyAdmin("*TIKET BARU MASUK*\n\nDari: {$user->name}\nJudul: {$ticket->subject}\nPrioritas: " . strtoupper($ticket->priority) . "\nPesan: {$validated['message']}");
        }

        return redirect()->route('tickets.show', $ticket)->with('success', 'Tiket gangguan berhasil dibuat.');
    }

    public function show(Ticket $ticket)
    {
        $user = auth()->user();

        if ($user->role === 'client' && $ticket->user_id !== $user->id) {
            abort(403);
        }

        $ticket->load(['user', 'customer.package', 'customer.odp', 'assignee', 'replies.user']);

        if ($user->role === 'client') {
            return view('tickets.client_show', compact('ticket'));
        }

        return view('tickets.show', compact('ticket'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $user = auth()->user();

        if ($user->role === 'client' && $ticket->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $validated['message'],
        ]);

        if ($user->role === 'client' && in_array($ticket->status, ['resolved', 'closed'], true)) {
            $ticket->update(['status' => 'open']);
        }

        if ($user->role !== 'client') {
            $this->notifyClient($ticket->user, "*BALASAN TIKET BANTUAN*\n\nTeknisi telah membalas tiket Anda:\n*{$ticket->subject}*\n\nPesan: {$validated['message']}\n\nSilakan cek portal pelanggan Anda.");
        } else {
            $this->notifyAdmin("*PELANGGAN MEMBALAS TIKET*\n\nDari: {$user->name}\nTiket: {$ticket->subject}\nPesan: {$validated['message']}");
        }

        return back()->with('success', 'Pesan berhasil dikirim.');
    }

    public function take(Ticket $ticket)
    {
        $user = auth()->user();

        if ($user->role === 'client') {
            abort(403);
        }

        $ticket->update([
            'assigned_to' => $user->id,
            'status' => 'in_progress',
            'taken_at' => now(),
            'resolved_at' => null,
        ]);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => "Tiket diambil oleh {$user->name} dan sedang dalam penanganan.",
        ]);

        return back()->with('success', 'Tiket berhasil diambil dan masuk status dalam penanganan.');
    }

    public function complete(Request $request, Ticket $ticket)
    {
        $user = auth()->user();

        if ($user->role === 'client') {
            abort(403);
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string'],
        ]);

        $ticket->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'assigned_to' => $ticket->assigned_to ?: $user->id,
            'taken_at' => $ticket->taken_at ?: now(),
        ]);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $validated['message'] ?: "Tiket ditandai selesai oleh {$user->name}.",
        ]);

        if ($ticket->user?->role === 'client') {
            $this->notifyClient($ticket->user, "*TIKET DISELESAIKAN*\n\nLaporan Anda mengenai:\n*{$ticket->subject}*\nTelah ditandai selesai oleh teknisi. Terima kasih.");
        }

        return back()->with('success', 'Tiket berhasil ditandai selesai.');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        if (auth()->user()->role === 'client') {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['open', 'in_progress', 'resolved', 'closed'])],
        ]);

        $updates = ['status' => $validated['status']];

        if ($validated['status'] === 'in_progress') {
            $updates['assigned_to'] = $ticket->assigned_to ?: auth()->id();
            $updates['taken_at'] = $ticket->taken_at ?: now();
            $updates['resolved_at'] = null;
        }

        if (in_array($validated['status'], ['resolved', 'closed'], true)) {
            $updates['assigned_to'] = $ticket->assigned_to ?: auth()->id();
            $updates['taken_at'] = $ticket->taken_at ?: now();
            $updates['resolved_at'] = $ticket->resolved_at ?: now();
        }

        if ($validated['status'] === 'open') {
            $updates['assigned_to'] = null;
            $updates['taken_at'] = null;
            $updates['resolved_at'] = null;
        }

        $ticket->update($updates);

        if ($validated['status'] === 'resolved') {
            $this->notifyClient($ticket->user, "*TIKET DISELESAIKAN*\n\nLaporan Anda mengenai:\n*{$ticket->subject}*\nTelah ditandai selesai oleh teknisi. Terima kasih.");
        }

        return back()->with('success', 'Status tiket berhasil diperbarui.');
    }

    private function notifyAdmin(string $message): void
    {
        $adminWa = Setting::get('admin_whatsapp');
        $fonnteToken = Setting::get('fonnte_token');

        if ($adminWa && $fonnteToken) {
            $this->sendFonnte($adminWa, $message, $fonnteToken);
        }
    }

    private function notifyClient(?User $client, string $message): void
    {
        $phone = $client?->phone;
        $fonnteToken = Setting::get('fonnte_token');

        if ($phone && $fonnteToken) {
            $this->sendFonnte($phone, $message, $fonnteToken);
        }
    }

    private function sendFonnte(string $target, string $message, string $token): void
    {
        try {
            Http::withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
                'delay' => '1',
            ]);
        } catch (\Exception $e) {
            Log::error('Fonnte Error in Ticketing: ' . $e->getMessage());
        }
    }
}
