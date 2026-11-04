<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Public support form shown on the home page. Guests and signed-in users can both submit. */
class SupportTicketController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:120',
            'email' => 'required|email:rfc|max:190',
            'category' => ['required', Rule::in(array_keys(SupportTicket::CATEGORIES))],
            'subject' => 'required|string|min:5|max:160',
            'message' => 'required|string|min:20|max:4000',
            'website' => 'nullable|string|max:0', // honeypot: real people never see or fill this field
        ]);

        $user = $request->user();
        $duplicate = SupportTicket::where('email', $data['email'])->where('subject', $data['subject'])->where('created_at', '>=', now()->subMinutes(10))->first();
        $ticket = $duplicate ?? SupportTicket::create([
            'user_id' => $user?->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'category' => $data['category'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'priority' => $data['category'] === 'report' ? 'high' : 'normal',
            'source' => 'home',
        ]);

        if (!$duplicate) app(\App\Services\Notifier::class)->ticketCreated($ticket);

        return back()->with('success', 'Thanks, we got your message and will reply by email.')->with('ticket', $ticket->reference);
    }
}
