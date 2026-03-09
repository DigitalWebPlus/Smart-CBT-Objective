<?php

declare(strict_types=1);

namespace App\Http\Controllers\Candidate;

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
        $tickets = SupportTicket::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return view('candidate.support-tickets.index', compact('tickets'));
    }

    public function create(): View
    {
        return view('candidate.support-tickets.create', [
            'ticket' => new SupportTicket(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'in:' . implode(',', SupportTicket::PRIORITIES)],
            'message' => ['required', 'string', 'min:5'],
        ]);

        $ticket = SupportTicket::query()->create([
            'user_id' => $request->user()->id,
            'subject' => $data['subject'],
            'priority' => $data['priority'],
            'status' => SupportTicket::STATUS_OPEN,
            'last_message_at' => now(),
            'last_reply_by' => 'candidate',
        ]);

        SupportMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'candidate',
            'sender_id' => $request->user()->id,
            'message' => $data['message'],
        ]);

        NotificationService::CREATED('Support ticket created successfully.');

        return redirect()->route('candidate.support-tickets.show', $ticket);
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        abort_if($ticket->user_id !== $request->user()->id, 403);

        $ticket->load(['messages']);

        return view('candidate.support-tickets.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_if($ticket->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'message' => ['required', 'string', 'min:2'],
        ]);

        SupportMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'candidate',
            'sender_id' => $request->user()->id,
            'message' => $data['message'],
        ]);

        $ticket->update([
            'status' => SupportTicket::STATUS_OPEN,
            'last_message_at' => now(),
            'last_reply_by' => 'candidate',
        ]);

        NotificationService::SUCCESS('Reply sent successfully.');

        return back();
    }

    public function close(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_if($ticket->user_id !== $request->user()->id, 403);

        $ticket->update([
            'status' => SupportTicket::STATUS_CLOSED,
            'closed_at' => now(),
            'last_reply_by' => 'candidate',
        ]);

        NotificationService::UPDATED('Support ticket closed.');

        return back();
    }
}
