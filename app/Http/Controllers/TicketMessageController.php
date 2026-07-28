<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\CustomerPortalAccount;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketMessageAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TicketMessageController extends Controller
{
    public function storeAdmin(Request $request, Ticket $ticket): RedirectResponse
    {
        $data = $this->validateMessagePayload($request);
        $this->ensureMessageHasContent($data, $request);

        $admin = AdminUser::query()->find((int) $request->session()->get('admin_user_id'));
        $senderLabel = $admin?->username ?: 'Service';

        $message = TicketMessage::query()->create([
            'ticket_id' => $ticket->id,
            'sender_type' => TicketMessage::SENDER_ADMIN,
            'sender_label' => $senderLabel,
            'body' => trim((string) ($data['body'] ?? '')) ?: null,
        ]);

        $this->storeAttachments($request, $ticket, $message);

        $customerAccount = $ticket->customerPortalAccount;
        $mailSent = false;
        if (
            $customerAccount
            && $customerAccount->is_active
            && $customerAccount->isGeiserPortal()
            && filled($customerAccount->email)
        ) {
            $mailSent = $this->sendCustomerNotificationMail($ticket, $message, $customerAccount);
        }

        if (! $customerAccount || ! $customerAccount->isGeiserPortal() || ! filled($customerAccount->email)) {
            return back()->with('warning', 'Nachricht gespeichert, aber kein aktiver Geiser-Empfaenger mit E-Mail am Ticket hinterlegt.');
        }

        if (! $mailSent) {
            return back()->with('warning', 'Nachricht gespeichert, aber E-Mail an den Kunden konnte nicht versendet werden.');
        }

        return back()->with('status', 'Nachricht wurde gespeichert und an den Kunden gesendet.');
    }

    public function storeGeiser(Request $request, Ticket $ticket): RedirectResponse
    {
        $account = $this->requireGeiserAccount($request);
        if (! $this->canGeiserViewTicket($account, $ticket)) {
            abort(403);
        }

        $data = $this->validateMessagePayload($request);
        $this->ensureMessageHasContent($data, $request);

        $message = TicketMessage::query()->create([
            'ticket_id' => $ticket->id,
            'customer_portal_account_id' => $account->id,
            'sender_type' => TicketMessage::SENDER_CUSTOMER,
            'sender_label' => $account->contact_name ?: $account->company_name,
            'body' => trim((string) ($data['body'] ?? '')) ?: null,
        ]);

        $this->storeAttachments($request, $ticket, $message);

        return back()->with('status', 'Antwort wurde gespeichert.');
    }

    public function downloadAdminAttachment(Ticket $ticket, TicketMessage $message, TicketMessageAttachment $attachment): StreamedResponse
    {
        $this->assertAttachmentOwnership($ticket, $message, $attachment);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    public function downloadGeiserAttachment(Request $request, Ticket $ticket, TicketMessage $message, TicketMessageAttachment $attachment): StreamedResponse
    {
        $account = $this->requireGeiserAccount($request);
        if (! $this->canGeiserViewTicket($account, $ticket)) {
            abort(403);
        }
        $this->assertAttachmentOwnership($ticket, $message, $attachment);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    private function validateMessagePayload(Request $request): array
    {
        return $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimetypes:image/jpeg,image/png,image/webp,application/pdf'],
        ]);
    }

    private function ensureMessageHasContent(array $data, Request $request): void
    {
        $body = trim((string) ($data['body'] ?? ''));
        $hasFiles = $request->hasFile('attachments');

        if ($body === '' && ! $hasFiles) {
            throw ValidationException::withMessages([
                'body' => 'Bitte Nachrichtentext eingeben oder mindestens einen Anhang hochladen.',
            ]);
        }
    }

    private function storeAttachments(Request $request, Ticket $ticket, TicketMessage $message): void
    {
        foreach ((array) $request->file('attachments', []) as $file) {
            $path = $file->store('ticket-messages/'.$ticket->id, 'local');
            $message->attachments()->create([
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
            ]);
        }
    }

    private function sendCustomerNotificationMail(Ticket $ticket, TicketMessage $message, CustomerPortalAccount $customerAccount): bool
    {
        $ticketUrl = route('geiser-portal.tickets.show', $ticket);
        $mailBody = implode("\n", [
            'Guten Tag,',
            '',
            'es gibt eine neue Nachricht zu Ihrem Ticket '.$ticket->ticket_number.'.',
            '',
            'Nachricht:',
            trim((string) $message->body) !== '' ? trim((string) $message->body) : '(Nur Anhang ohne Text)',
            '',
            'Direktlink zum Ticket:',
            $ticketUrl,
            '',
            'Viele Gruesse',
            'Service-Team',
        ]);

        try {
            Mail::raw($mailBody, function ($mail) use ($customerAccount, $ticket): void {
                $mail->to($customerAccount->email)
                    ->subject('Neue Nachricht zu Ticket '.$ticket->ticket_number);
            });
            $message->forceFill(['sent_to_customer_at' => now()])->save();
            return true;
        } catch (Throwable $exception) {
            Log::warning('Ticket-Nachricht konnte nicht per E-Mail an Geiser gesendet werden.', [
                'ticket_id' => $ticket->id,
                'message_id' => $message->id,
                'account_id' => $customerAccount->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return false;
    }

    private function requireGeiserAccount(Request $request): CustomerPortalAccount
    {
        return CustomerPortalAccount::query()
            ->whereKey((int) $request->session()->get('geiser_customer_portal_account_id'))
            ->where('portal_scope', CustomerPortalAccount::PORTAL_SCOPE_GEISER)
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function canGeiserViewTicket(CustomerPortalAccount $account, Ticket $ticket): bool
    {
        if ((int) $ticket->dolibarr_customer_id !== (int) $account->dolibarr_thirdparty_id) {
            return false;
        }

        if ($ticket->customer_portal_account_id === null) {
            return true;
        }

        return (int) $ticket->customer_portal_account_id === (int) $account->id;
    }

    private function assertAttachmentOwnership(Ticket $ticket, TicketMessage $message, TicketMessageAttachment $attachment): void
    {
        if ((int) $message->ticket_id !== (int) $ticket->id || (int) $attachment->ticket_message_id !== (int) $message->id) {
            abort(404);
        }
    }
}
