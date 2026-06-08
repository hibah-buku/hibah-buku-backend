@extends('emails.layout')

@section('content')
<p>Halo, <strong>Penerbit</strong>,</p>

<p>Seorang penulis telah mengunggah dokumen kelengkapan administrasi baru untuk penandatanganan kontrak hibah dan pencairan dana.</p>

<div class="info-box">
    <p><strong>Detail Pengunggahan:</strong></p>
    <table style="width: 100%; margin: 10px 0;">
        <tr>
            <td style="padding: 5px 0; width: 35%;"><strong>Penulis:</strong></td>
            <td style="padding: 5px 0;">{{ $author_name }}</td>
        </tr>
        <tr>
            <td style="padding: 5px 0;"><strong>Naskah Buku:</strong></td>
            <td style="padding: 5px 0;">{{ $book_title }}</td>
        </tr>
        <tr>
            <td style="padding: 5px 0;"><strong>Jenis Dokumen:</strong></td>
            <td style="padding: 5px 0;">{{ $document_type_label }}</td>
        </tr>
        <tr>
            <td style="padding: 5px 0;"><strong>Diupload Pada:</strong></td>
            <td style="padding: 5px 0;">{{ \Carbon\Carbon::parse($uploaded_at)->setTimezone('Asia/Jakarta')->format('d F Y, H:i') }} WIB</td>
        </tr>
    </table>
</div>

<p>Silakan login ke panel penerbit untuk meninjau berkas administrasi dan melengkapi proses berikutnya.</p>

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ $review_url }}"
        class="btn">
        Lihat Dashboard Penerbit
    </a>
</div>

<hr class="divider">

<p style="font-size:13px; color:#666;">Email ini dikirim secara otomatis kepada semua penerbit sistem. Mohon untuk tidak membalas email ini.</p>
@endsection
