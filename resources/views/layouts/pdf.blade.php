{{-- Tata letak PDF terbitan (DocumentIssuer). dompdf tidak membaca @theme Tailwind: nilai di bawah = salinan token docs/26. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 28mm 18mm 24mm 18mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; line-height: 1.45; color: #1B2430; }
        header { position: fixed; top: -20mm; left: 0; right: 0; border-bottom: 2px solid #0F2A4A; padding-bottom: 4px; }
        header .org { font-size: 8pt; color: #5B6675; }
        header .app { font-weight: bold; color: #0F2A4A; }
        footer { position: fixed; bottom: -16mm; left: 0; right: 0; border-top: 1px solid #E2E6EC; padding-top: 4px; font-size: 7.5pt; color: #5B6675; }
        footer table td { vertical-align: middle; }
        h1 { font-size: 15pt; color: #0F2A4A; margin: 0 0 4px; }
        h2 { font-size: 11.5pt; color: #0F2A4A; margin: 16px 0 6px; border-bottom: 1px solid #E2E6EC; padding-bottom: 2px; }
        h3 { font-size: 10.5pt; margin: 10px 0 4px; }
        table.grid { width: 100%; border-collapse: collapse; margin: 4px 0 8px; }
        table.grid th, table.grid td { border: 1px solid #E2E6EC; padding: 4px 6px; text-align: left; vertical-align: top; }
        table.grid th { background: #F7F8FA; font-weight: bold; }
        .meta td { padding: 1px 8px 1px 0; vertical-align: top; }
        .muted { color: #5B6675; }
        .box { border: 1px solid #E2E6EC; padding: 6px 8px; background: #F7F8FA; }
        .flag { color: #8F5600; font-weight: bold; }
    </style>
</head>
<body>
<header>
    <span class="app">SEMANGAT CORPU</span>
    <span class="org"> · {{ __('idp.pdf.org') }}</span>
</header>
<footer>
    <table width="100%"><tr>
        <td width="62"><img src="{{ $qr }}" width="56" height="56" alt="QR"></td>
        <td>{{ __('idp.pdf.verify') }}<br><strong>{{ $verifyUrl }}</strong></td>
    </tr></table>
</footer>
<main>
    @yield('content')
</main>
</body>
</html>
