@extends('emails.layout')

@section('content')
<p>Halo, <strong>{{ $admin_name ?? 'Admin' }}</strong>,</p>

<p>Seorang penulis telah mengupload kontrak hibah buku baru ke sistem dan memerlukan validasi Anda.</p>

<div class="info-box">
    <p><strong>Detail Kontrak:</strong></p>
    <table style="width: 100%; margin: 10px 0;">
        <tr>
            <td style="padding: 5px 0;"><strong>ID Kontrak:</strong></td>
            <td style="padding: 5px 0;">#{{ $contract_id }}</td>
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
            <td style="padding: 5px 0;"><strong>File:</strong></td>
            <td style="padding: 5px 0;">{{ $file_name }}</td>
        </tr>
        <tr>
            <td style="padding: 5px 0;"><strong>Diupload Pada:</strong></td>
            <td style="padding: 5px 0;">{{ \Carbon\Carbon::parse($uploaded_at)->setTimezone('Asia/Jakarta')->format('d F Y, H:i') }} WIB</td>
        </tr>
    </table>
</div>

<p>Silakan segera lakukan validasi terhadap kontrak tersebut untuk memastikan kesesuaian dengan standar hibah buku.</p>

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ $review_url }}"
        class="btn">
        Review Kontrak
    </a>
</div>

<hr class="divider">

<p style="font-size:13px; color:#666;">Email ini dikirim kepada semua administrator sistem.</p>
@endsection
