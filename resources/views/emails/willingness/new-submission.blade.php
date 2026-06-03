@extends('emails.layout')

@section('content')
<p>Halo, <strong>{{ $admin_name ?? 'Admin' }}</strong>,</p>

<p>Ada formulir kesediaan penulis baru yang masuk ke sistem dan memerlukan validasi Anda.</p>

<div class="info-box">
    <p><strong>Detail Pengajuan:</strong></p>
    <table style="width: 100%; margin: 10px 0;">
        <tr>
            <td style="padding: 5px 0;"><strong>ID Formulir:</strong></td>
            <td style="padding: 5px 0;">#{{ $form_id }}</td>
        </tr>
        <tr>
            <td style="padding: 5px 0;"><strong>Penulis Utama:</strong></td>
            <td style="padding: 5px 0;">{{ $author_name }}</td>
        </tr>
        <tr>
            <td style="padding: 5px 0;"><strong>Judul Buku:</strong></td>
            <td style="padding: 5px 0;">{{ $book_title }}</td>
        </tr>
        <tr>
            <td style="padding: 5px 0;"><strong>Dikirim Pada:</strong></td>
            <td style="padding: 5px 0;">{{ \Carbon\Carbon::parse($submitted_at)->setTimezone('Asia/Jakarta')->format('d F Y, H:i') }} WIB</td>
        </tr>
    </table>
</div>

<p>Silakan segera lakukan validasi terhadap formulir tersebut untuk memastikan kelayakan penulis dan judul buku yang diajukan.</p>

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ $review_url }}"
       style="display: inline-block; background-color: #2563eb; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
        Review Formulir
    </a>
</div>

<hr class="divider">

<p style="font-size:13px; color:#666;">Email ini dikirim secara otomatis kepada semua administrator sistem. Mohon untuk tidak membalas email ini.</p>
@endsection
