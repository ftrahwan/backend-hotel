<!DOCTYPE html>
<html>
<head>
    <title>Nota Pemesanan</title>
    <style>
        body { font-family: Arial, sans-serif; }
        h1 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table, th, td { border: 1px solid black; }
        th, td { padding: 8px; text-align: left; }
    </style>
</head>
<body>
    <h1>Gimme Hotel</h1>
    <p><strong>Nama :</strong> {{ $nama_pemesan }}</p>
    <p><strong>Email :</strong> {{ $email_pemesan }}</p>
    <br>

    <h2>Detail Pemesanan</h2>
    <p><strong>Nomor Pemesanan:</strong> {{ $nomor_pemesanan }}</p>
    <p><strong>Tanggal Pemesanan:</strong> {{ $tgl_pemesanan ?? 'N/A' }}</p>
    <p><strong>Check In:</strong> {{ $tgl_check_in }}</p>
    <p><strong>Check Out:</strong> {{ $tgl_check_out }}</p>
    <p><strong>Jumlah Kamar:</strong> {{ $jumlah_kamar }}</p>
    <p><strong>Status :</strong> {{ $status_pemesanan }}</p>
    <br>

    <h2>Detail Kamar</h2>
    <table>
        <thead>
            <tr>
                <th>Kamar</th>
                <th>Harga</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($detail as $d)
            <tr>
                <td>{{ $d->id_kamar }}</td>
                <td>Rp {{ number_format($d->harga, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr>
                <td><strong>Total Harga</strong></td>
                <td><strong>Rp {{ number_format($total_harga, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
