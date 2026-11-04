Hi {{ $ticket->name }},

{{ $body }}

--
Aadi Support
Reference: {{ $ticket->reference }}
Your original message: "{{ \Illuminate\Support\Str::limit($ticket->message, 280) }}"

To follow up, submit a new request from the Support section on our home page and quote the reference above.
