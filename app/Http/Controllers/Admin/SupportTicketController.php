<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $priority = $request->string('priority')->toString();

        $query = SupportTicket::query()->with(['candidate', 'admin'])->latest();

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($priority !== '') {
            $query->where('priority', $priority);
        }

        $tickets = $query->paginate(20)->withQueryString();

        return view('admin.support-tickets.index', compact('tickets', 'status', 'priority'));
    }

    public function show(SupportTicket $ticket): View
    {
        $ticket->load(['candidate', 'admin', 'messages']);

        return view('admin.support-tickets.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:2'],
        ]);

        $admin = $request->user('admin');

        SupportMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'admin',
            'sender_id' => $admin?->id,
            'message' => $data['message'],
        ]);

        $ticket->update([
            'admin_id' => $admin?->id,
            'status' => SupportTicket::STATUS_PENDING,
            'last_message_at' => now(),
            'last_reply_by' => 'admin',
        ]);

        NotificationService::SUCCESS('Reply sent successfully.');

        return back();
    }

    public function close(SupportTicket $ticket): RedirectResponse
    {
        $ticket->update([
            'status' => SupportTicket::STATUS_CLOSED,
            'closed_at' => now(),
            'last_reply_by' => 'admin',
        ]);

        NotificationService::UPDATED('Ticket closed.');

        return back();
    }

    public function reopen(SupportTicket $ticket): RedirectResponse
    {
        $ticket->update([
            'status' => SupportTicket::STATUS_OPEN,
            'closed_at' => null,
            'last_reply_by' => 'admin',
        ]);

        NotificationService::UPDATED('Ticket reopened.');

        return back();
    }
}
