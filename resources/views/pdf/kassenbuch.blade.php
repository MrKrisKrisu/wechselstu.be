<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Kassenbuch</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; margin: 24px 32px; }
        h1 { font-size: 16px; font-weight: bold; margin-bottom: 4px; }
        .subtitle { font-size: 10px; color: #64748b; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th { background-color: #1e293b; color: #fff; text-align: left; padding: 6px 8px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        tr:nth-child(even) td { background-color: #f8fafc; }
        .amount-positive { color: #16a34a; font-weight: bold; }
        .amount-negative { color: #dc2626; font-weight: bold; }
        .reversed { color: #94a3b8; text-decoration: line-through; }
        .reversal-badge { background: #fee2e2; color: #dc2626; padding: 1px 4px; border-radius: 3px; font-size: 8px; }
        .section-title { font-size: 12px; font-weight: bold; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 2px solid #1e293b; }
        .balance-box { background: #1e293b; color: #fff; padding: 12px 16px; margin-bottom: 24px; width: 220px; }
        .balance-box .label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 4px; }
        .balance-box .value { font-size: 18px; font-weight: bold; }
        .footer { font-size: 8px; color: #94a3b8; text-align: right; margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body>
    <h1>Kassenbuch Hauptkasse</h1>
    <p class="subtitle">Exportiert am {{ $generated_at->format('d.m.Y') }} um {{ $generated_at->format('H:i:s') }} Uhr</p>

    <div class="balance-box">
        <div class="label">Aktueller Kassenbestand</div>
        <div class="value">{{ number_format($balance_cents / 100, 2, ',', '.') }} &euro;</div>
    </div>

    <div class="section-title">Buchungen</div>
    <table>
        <thead>
            <tr>
                <th>Datum</th>
                <th>Typ</th>
                <th>Gegenkonto</th>
                <th>Beschreibung</th>
                <th>Betrag</th>
                <th>Saldo</th>
                <th>Erfasst von</th>
            </tr>
        </thead>
        <tbody>
            @php $running = 0; @endphp
            @foreach($entries as $entry)
                @php $running += $entry->amount_cents; @endphp
                <tr>
                    <td {{ $entry->isReversed() ? 'class=reversed' : '' }}>
                        {{ $entry->created_at->format('d.m.Y') }}<br>
                        <span style="color:#94a3b8">{{ $entry->created_at->format('H:i') }}</span>
                    </td>
                    <td>
                        {{ $entry->type->label() }}
                        @if($entry->type->value === 'reversal')
                            <br><span class="reversal-badge">Storno</span>
                        @endif
                    </td>
                    <td {{ $entry->isReversed() ? 'class=reversed' : '' }}>{{ $entry->counterpartStation?->name ?? '' }}</td>
                    <td {{ $entry->isReversed() ? 'class=reversed' : '' }}>{{ $entry->description ?? '' }}</td>
                    <td class="{{ $entry->amount_cents >= 0 ? 'amount-positive' : 'amount-negative' }} {{ $entry->isReversed() ? 'reversed' : '' }}">
                        {{ $entry->amount_cents >= 0 ? '+' : '' }}{{ number_format($entry->amount_cents / 100, 2, ',', '.') }} &euro;
                    </td>
                    <td>{{ number_format($running / 100, 2, ',', '.') }} &euro;</td>
                    <td {{ $entry->isReversed() ? 'class=reversed' : '' }}>{{ $entry->creator->name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($closings->count() > 0)
    <div class="section-title">Tagesabschl&uuml;sse</div>
    <table>
        <thead>
            <tr>
                <th>Bezeichnung</th>
                <th>Datum</th>
                <th>Gesperrt bis</th>
                <th>Saldo bei Abschluss</th>
                <th>Erstellt von</th>
            </tr>
        </thead>
        <tbody>
            @foreach($closings as $closing)
                <tr>
                    <td>{{ $closing->label }}</td>
                    <td>{{ $closing->closing_date->format('d.m.Y') }}</td>
                    <td>{{ $closing->locked_until->format('d.m.Y H:i') }}</td>
                    <td>{{ number_format($closing->balance_cents / 100, 2, ',', '.') }} &euro;</td>
                    <td>{{ $closing->creator->name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        wechselstu.be
    </div>
</body>
</html>
