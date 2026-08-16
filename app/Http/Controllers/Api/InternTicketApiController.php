<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InternesTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternTicketApiController extends Controller
{
<<<<<<< HEAD
=======
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'quelle' => ['nullable', 'in:lager,zeitebuchung'],
            'status' => ['nullable', 'in:offen,in_bearbeitung,erledigt'],
        ]);

        $query = InternesTicket::query()
            ->select([
                'id',
                'ticket_number',
                'quelle',
                'typ',
                'titel',
                'beschreibung',
                'prioritaet',
                'status',
                'ersteller_name',
                'ersteller_email',
                'created_at',
            ])
            ->orderByRaw("case status when 'offen' then 0 when 'in_bearbeitung' then 1 else 2 end")
            ->orderByDesc('created_at');

        if ($request->filled('quelle')) {
            $query->where('quelle', $request->string('quelle'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json([
            'tickets' => $query->limit(50)->get(),
        ]);
    }

>>>>>>> old-ticket-system/main
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'quelle'          => ['required', 'in:lager,zeitebuchung'],
            'typ'             => ['required', 'in:bug,feature,aufgabe'],
            'titel'           => ['required', 'string', 'max:255'],
            'beschreibung'    => ['nullable', 'string'],
            'prioritaet'      => ['required', 'in:niedrig,mittel,hoch'],
            'ersteller_name'  => ['required', 'string', 'max:255'],
            'ersteller_email' => ['required', 'email', 'max:255'],
        ]);

        $ticket = InternesTicket::create($data);

        return response()->json([
            'ticket_number' => $ticket->ticket_number,
            'id'            => $ticket->id,
        ], 201);
    }
}
