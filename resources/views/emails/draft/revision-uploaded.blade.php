@extends('emails.layout')

@section('content')
<p>Halo, <strong>{{ $publisher_name ?? 'Penerbit' }}</strong>,</p>

<p>Penulis telah mengupload <strong>revisi naskah</strong> dan siap untuk diperiksa kembali.</p>

<div class="info-box">
    <p><strong>Detail Revisi:</strong></p>
    <table style="width: 100%; margin: 10px 0;">
        <tr>
            <td style="padding: 5px 0;"><strong>ID Naskah:</strong></td>
            <td style="padding: 5px 0;">#{{ $manuscript_id }}</td>
        </tr>
        <tr>
            <td style="padding: 5px 0;"><strong>Penulis:</strong></td>
            <td style="padding: 5px 0;">{{ $author_name }}</td>
        </tr>
        <tr>
            <td style="padding: 5px 0;"><strong>Judul Buku:</strong></td>
            <td style="padding: 5px 0;">{{ $book_title }}</td>
        </tr>
        <tr>
            <td style="padding: 5px 0;"><strong>Diupload Pada:</strong></td>
            <td style="padding: 5px 0;">{{ \Carbon\Carbon::parse($uploaded_at)->setTimezone('Asia/Jakarta')->format('d F Y, H:i') }} WIB</td>
        </tr>
    </table>
</div>

<p>Silakan login ke sistem untuk memeriksa revisi yang telah dikirimkan.</p>

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ $review_url }}" class="btn">
        Periksa Revisi
    </a>
</div>

<hr class="divider">

<p style="font-size:13px; color:#666;">Email ini dikirim kepada semua penerbit sistem.</p>
@endsection
