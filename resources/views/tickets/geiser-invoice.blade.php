<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Il Coccolino Rechnung {{ $ticket->ticket_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #111827; }
        h1 { margin: 0 0 6px; font-size: 16px; }
        h2 { margin: 14px 0 6px; font-size: 12px; }
        p { margin: 0 0 4px; }
        .muted { color: #6b7280; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .grid td { width: 50%; vertical-align: top; padding: 2px 8px 2px 0; }
        .table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .table th, .table td { border: 1px solid #d1d5db; padding: 4px 5px; text-align: left; vertical-align: top; }
        .table th { background: #f3f4f6; }
        .copy-block { white-space: pre-wrap; font-size: 8px; line-height: 1.25; }
        .amount { text-align: right; white-space: nowrap; }
        .section { margin-top: 12px; }
    </style>
</head>
<body>
    <h1>Rechnung</h1>
    <p class="muted">Il Coccolino Beleg | erstellt am {{ $createdAt->format('d.m.Y H:i') }}</p>

    <table class="grid">
        <tr>
            <td>
                <h2>Absender</h2>
                <p><strong>{{ $sender['company_name'] ?? '' }}</strong></p>
                <p>{{ $sender['address_line_1'] ?? '' }}</p>
                @if (!empty($sender['address_line_2']))
                    <p>{{ $sender['address_line_2'] }}</p>
                @endif
                @if (!empty($sender['email']))
                    <p>E-Mail: {{ $sender['email'] }}</p>
                @endif
                @if (!empty($sender['phone']))
                    <p>Telefon: {{ $sender['phone'] }}</p>
                @endif
                @if (!empty($sender['tax_number']))
                    <p>Steuernummer: {{ $sender['tax_number'] }}</p>
                @endif
            </td>
            <td>
                <h2>Rechnung an</h2>
                <p><strong>{{ $invoiceRecipient['name'] ?: $ticket->customer_name_snapshot }}</strong></p>
                <p>{{ $ticket->customerMachineProfile?->contact_name ?: $ticket->customer_contact_name_snapshot ?: '-' }}</p>
                @if (!empty($invoiceRecipient['address']))
                    <p>{!! nl2br(e($invoiceRecipient['address'])) !!}</p>
                @endif
                @if (!empty($invoiceRecipient['zip']) || !empty($invoiceRecipient['town']))
                    <p>{{ trim(($invoiceRecipient['zip'] ?? '').' '.($invoiceRecipient['town'] ?? '')) }}</p>
                @endif
                @if (!empty($invoiceRecipient['state']) || !empty($invoiceRecipient['country']))
                    <p>{{ collect([$invoiceRecipient['state'] ?? null, $invoiceRecipient['country'] ?? null])->filter()->implode(', ') }}</p>
                @endif
                <p>E-Mail: {{ $invoiceRecipient['email'] ?: ($ticket->customerMachineProfile?->email ?: $ticket->customer_email_snapshot ?: '-') }}</p>
                @if (!empty($invoiceRecipient['phone']) || !empty($invoiceRecipient['phone_mobile']))
                    <p>Telefon: {{ $invoiceRecipient['phone'] ?: $invoiceRecipient['phone_mobile'] }}</p>
                @endif
                @if (!empty($invoiceRecipient['code_client']))
                    <p>Kundennummer: {{ $invoiceRecipient['code_client'] }}</p>
                @endif
                @if (!empty($invoiceRecipient['vat_number']))
                    <p>USt-IdNr.: {{ $invoiceRecipient['vat_number'] }}</p>
                @endif
            </td>
        </tr>
    </table>

    <div class="section">
        <h2>Belegdaten</h2>
        <table class="grid">
            <tr>
                <td>Rechnungsnummer (intern): <strong>ILC-{{ $ticket->ticket_number }}</strong></td>
                <td>Ticket: <strong>{{ $ticket->ticket_number }}</strong></td>
            </tr>
            <tr>
                <td>Maschine:
                    <strong>
                        {{ trim(($ticket->customerMachine?->manufacturer_snapshot ?: $ticket->customerMachineProfile?->manufacturer_snapshot ?: '').' '.($ticket->customerMachine?->machine_ref_snapshot ?: $ticket->customerMachineProfile?->machine_ref_snapshot ?: '')) ?: '-' }}
                    </strong>
                </td>
                <td>Seriennummer: <strong>{{ $ticket->customerMachine?->serial_number ?: $ticket->customerMachineProfile?->serial_number ?: '-' }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Leistungspositionen (aus Admin)</h2>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 12%;">Typ</th>
                    <th style="width: 12%;">Ref</th>
                    <th>Leistung / Artikel</th>
                    <th style="width: 9%;" class="amount">Menge</th>
                    <th style="width: 12%;" class="amount">VK</th>
                    <th style="width: 10%;" class="amount">Rabatt</th>
                    <th style="width: 12%;" class="amount">Preis</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoiceLines as $line)
                    <tr>
                        <td>{{ $line['type'] }}</td>
                        <td>{{ $line['reference'] }}</td>
                        <td>{{ $line['description'] }}</td>
                        <td class="amount">{{ number_format((float) $line['quantity'], 2, ',', '.') }}</td>
                        <td class="amount">{{ number_format((float) $line['unit_price'], 2, ',', '.') }} EUR</td>
                        <td class="amount">
                            @if (($line['discount_rate'] ?? 0) > 0)
                                {{ number_format((float) (($line['discount_rate'] ?? 0) * 100), 0, ',', '.') }} %
                            @else
                                -
                            @endif
                        </td>
                        <td class="amount"><strong>{{ number_format((float) $line['discounted_total'], 2, ',', '.') }} EUR</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Keine Positionen aus der Admin vorhanden.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="amount"><strong>Summe VK</strong></td>
                    <td class="amount">{{ number_format($totalOriginalNet, 2, ',', '.') }} EUR</td>
                </tr>
                <tr>
                    <td colspan="6" class="amount"><strong>Rabatt gesamt</strong></td>
                    <td class="amount">- {{ number_format($totalDiscountAmount, 2, ',', '.') }} EUR</td>
                </tr>
                <tr>
                    <td colspan="6" class="amount"><strong>Gesamt netto</strong></td>
                    <td class="amount"><strong>{{ number_format($totalNet, 2, ',', '.') }} EUR</strong></td>
                </tr>
            </tfoot>
        </table>
        <p class="muted" style="margin-top: 6px;">Auf Ersatzteile wird automatisch 20 % Rabatt gewaehrt. Arbeitsleistungen bleiben unveraendert.</p>
    </div>

    <div class="section">
        <h2>Copy-&-Paste-Textblaecke je Position</h2>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 8%;">Pos.</th>
                    <th>Textblock</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoiceLines as $index => $line)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><div class="copy-block">{{ $line['copy_text'] }}</div></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">Keine Textblöcke vorhanden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Kontoverbindung</h2>
        <p><strong>Kontoinhaber:</strong> {{ $bank['account_holder'] ?? '' }}</p>
        <p><strong>Bank:</strong> {{ $bank['bank_name'] ?? '' }}</p>
        <p><strong>IBAN:</strong> {{ $bank['iban'] ?? '' }}</p>
        <p><strong>BIC:</strong> {{ $bank['bic'] ?? '' }}</p>
        @if (!empty($bank['payment_note']))
            <p><strong>Zahlungshinweis:</strong> {{ $bank['payment_note'] }}</p>
        @endif
    </div>

    @if ($footerNote !== '')
        <div class="section">
            <p class="muted">{{ $footerNote }}</p>
        </div>
    @endif
</body>
</html>
