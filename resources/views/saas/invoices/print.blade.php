<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; margin: 0; padding: 0; background-color: #f9f9f9; }
        .invoice-box { max-width: 800px; margin: 40px auto; padding: 40px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); font-size: 16px; background-color: #fff; }
        .invoice-box table { width: 100%; text-align: left; border-collapse: collapse; }
        .invoice-box table td { padding: 10px; vertical-align: top; }
        .invoice-box table tr td:nth-child(2) { text-align: right; }
        .invoice-box table tr.top table td { padding-bottom: 20px; }
        .invoice-box table tr.top table td.title { font-size: 28px; font-weight: bold; color: #4F46E5; }
        .invoice-box table tr.information table td { padding-bottom: 40px; }
        .invoice-box table tr.heading td { background: #f3f4f6; border-bottom: 1px solid #ddd; font-weight: bold; }
        .invoice-box table tr.details td { padding-bottom: 20px; }
        .invoice-box table tr.item td { border-bottom: 1px solid #eee; }
        .invoice-box table tr.item.last td { border-bottom: none; }
        .invoice-box table tr.total td:nth-child(2) { border-top: 2px solid #eee; font-weight: bold; font-size: 1.2em; }
        .status { font-weight: bold; padding: 5px 15px; border-radius: 20px; font-size: 14px; text-transform: uppercase; }
        .status.paid { background-color: #dcfce7; color: #166534; }
        .status.unpaid { background-color: #fee2e2; color: #991b1b; }
        @media only screen and (max-width: 600px) {
            .invoice-box table tr.top table td { width: 100%; display: block; text-align: center; }
            .invoice-box table tr.information table td { width: 100%; display: block; text-align: center; }
        }
        @media print {
            body { background-color: #fff; }
            .invoice-box { box-shadow: none; margin: 0; border: none; padding: 20px; }
            .no-print { display: none; }
        }
        .btn-print { background-color: #4F46E5; color: white; border: none; padding: 10px 20px; font-size: 16px; border-radius: 5px; cursor: pointer; display: block; margin: 20px auto; max-width: 200px; text-align: center; text-decoration: none; font-weight: bold; }
        .btn-print:hover { background-color: #4338ca; }
    </style>
</head>
<body>

    <button onclick="window.print()" class="btn-print no-print">🖨️ Cetak Invoice</button>

    <div class="invoice-box">
        <table>
            <tr class="top">
                <td colspan="2">
                    <table>
                        <tr>
                            <td class="title">
                                Billing RT/RW Net
                            </td>
                            <td>
                                <strong>Invoice #:</strong> {{ $invoice->invoice_number }}<br>
                                <strong>Tanggal Diterbitkan:</strong> {{ $invoice->created_at->format('d M Y') }}<br>
                                <strong>Jatuh Tempo:</strong> {{ $invoice->due_date->format('d M Y') }}<br>
                                <strong>Status:</strong> 
                                @if($invoice->status == 'paid')
                                    <span class="status paid">LUNAS</span>
                                @else
                                    <span class="status unpaid">BELUM DIBAYAR</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="information">
                <td colspan="2">
                    <table>
                        <tr>
                            <td>
                                <strong>Penerbit:</strong><br>
                                Billing RT/RW Net Pusat<br>
                                Admin SaaS<br>
                            </td>
                            <td>
                                <strong>Ditagihkan Kepada (Penyewa):</strong><br>
                                {{ $invoice->tenant->name }}<br>
                                {{ $invoice->tenant->domain }}<br>
                                @if($invoice->tenant->users->first())
                                {{ $invoice->tenant->users->first()->name }} ({{ $invoice->tenant->users->first()->phone }})
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="heading">
                <td>Deskripsi</td>
                <td>Harga</td>
            </tr>

            <tr class="item last">
                <td>
                    Biaya Sewa Aplikasi Bulan {{ $invoice->due_date->format('F Y') }}<br>
                    <small style="color: #666;">(Paket: {{ strtoupper($invoice->tenant->subscription_plan) }})</small>
                </td>
                <td>Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
            </tr>

            <tr class="total">
                <td></td>
                <td>Total: Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
            </tr>
        </table>
        
        <div style="margin-top: 50px; text-align: center; color: #666; font-size: 14px;">
            <p>Terima kasih telah berlangganan aplikasi kami.</p>
            @if($invoice->status == 'paid' && $invoice->payment_method)
                <p>Dibayar melalui: <strong>{{ strtoupper($invoice->payment_method) }}</strong> pada {{ $invoice->paid_at->format('d M Y H:i') }}</p>
            @endif
        </div>
    </div>
</body>
</html>
