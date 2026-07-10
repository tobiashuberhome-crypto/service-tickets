<?php

namespace App\Http\Controllers;

use App\Models\CustomerMachine;
use App\Models\EasyappointmentsBooking;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EasyAppointmentsWebhookController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $token = $request->bearerToken()
            ?? $request->header('X-Integration-Token')
            ?? $request->header('X-Webhook-Token');

        $expected = (string) config('services.easyappointments.webhook_token');

        if ($token === '' || !hash_equals($expected, (string) $token)) {
            Log::warning('EasyAppointments Webhook: invalid token', ['ip' => $request->ip()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Ungueltiges Webhook-Token.',
            ], 401);
        }

        $raw = $request->all();
        $data = isset($raw['payload']) && is_array($raw['payload']) ? $raw['payload'] : $raw;

        $appointmentId = $data['id'] ?? $data['appointment_id'] ?? null;
        if (!$appointmentId) {
            return response()->json([
                'status' => 'error',
                'message' => 'appointment_id fehlt im Payload.',
            ], 422);
        }

        if (EasyappointmentsBooking::where('appointment_id', (string) $appointmentId)->exists()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Bereits verarbeitet.',
            ]);
        }

        $serialNumber = $this->extractSerialNumber($data);
        if (!$serialNumber) {
            return response()->json([
                'status' => 'error',
                'message' => 'Seriennummer fehlt im Payload.',
            ], 422);
        }

        $customerName = $this->extractCustomerName($data);

        $machine = CustomerMachine::firstOrCreate(
            ['serial_number' => $serialNumber],
            [
                'dolibarr_customer_id' => null,
                'customer_name_snapshot' => $customerName,
                'dolibarr_machine_product_id' => 0,
                'manufacturer_snapshot' => null,
                'machine_ref_snapshot' => 'Terminbuchung',
            ]
        );

        // Bei bestehender Maschine Snapshot aktualisieren, falls jetzt ein besserer Name kommt
        if ($customerName !== '' && $machine->customer_name_snapshot !== $customerName) {
            $machine->customer_name_snapshot = $customerName;
            $machine->save();
        }

        $startDate = null;
        if (!empty($data['start_datetime'])) {
            $ts = strtotime((string) $data['start_datetime']);
            $startDate = $ts ? date('Y-m-d', $ts) : null;
        }

        $description = 'EasyAppointments Termin-ID: ' . $appointmentId;
        if (!empty($data['start_datetime'])) {
            $description .= "\nTermin: " . $data['start_datetime'];
        }
        if (!empty($data['notes'])) {
            $description .= "\nNotiz: " . $data['notes'];
        }

        $ticket = Ticket::create([
            'dolibarr_customer_id' => null,
            'customer_name_snapshot' => $customerName,
            'customer_machine_id' => $machine->id,
            'service_enabled' => false,
            'cleaning' => false,
            'repair_enabled' => true,
            'spare_part_order_required' => false,
            'error_description' => $description,
            'acceptance_date' => now()->toDateString(),
            'target_date' => $startDate,
            'status' => Ticket::STATUS_OPEN,
            'sync_status' => Ticket::SYNC_PENDING,
            'sync_message' => 'Dolibarr-Kunde noch nicht zugeordnet (EasyAppointments).',
        ]);

        EasyappointmentsBooking::create([
            'appointment_id' => (string) $appointmentId,
            'ticket_id' => $ticket->id,
        ]);

        Log::info('EasyAppointments Webhook: ticket created', [
            'appointment_id' => $appointmentId,
            'ticket_id' => $ticket->id,
        ]);

        return response()->json([
            'status' => 'ok',
            'ticket_id' => $ticket->id,
            'message' => 'Ticket erfolgreich erstellt.',
        ], 201);
    }

    private function extractCustomerName(array $data): string
    {
        $customer = isset($data['customer']) && is_array($data['customer']) ? $data['customer'] : [];

        $first = trim((string) (
            $data['customer_first_name']
            ?? $data['first_name']
            ?? $customer['first_name']
            ?? ''
        ));

        $last = trim((string) (
            $data['customer_last_name']
            ?? $data['last_name']
            ?? $customer['last_name']
            ?? ''
        ));

        $full = trim($first . ' ' . $last);

        if ($full !== '') {
            return $full;
        }

        $fallback = trim((string) (
            $data['customer_name']
            ?? $data['name']
            ?? $customer['customer_name']
            ?? $customer['name']
            ?? ''
        ));

        return $fallback !== '' ? $fallback : 'Terminbuchung';
    }

    private function extractSerialNumber(array $data): ?string
    {
        $serial = trim((string) ($data['serial_number'] ?? ''));
        if ($serial !== '') {
            return $serial;
        }

        $notes = trim((string) ($data['notes'] ?? ''));
        if ($notes === '') {
            return null;
        }

        if (preg_match('/(?:seriennummer|serial|sn)\s*[:\-]?\s*([A-Z0-9\-]+)/i', $notes, $matches)) {
            return trim($matches[1]);
        }

        return $notes;
    }
}