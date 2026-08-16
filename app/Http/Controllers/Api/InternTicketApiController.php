<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InternesTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternTicketApiController extends Controller
{
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
