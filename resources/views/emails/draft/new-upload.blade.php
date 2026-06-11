@extends('emails.layout')

@section('content')
<p>Halo, <strong>{{ $admin_name ?? 'Admin' }}</strong>,</p>

<p>Seorang penulis telah mengunggah draft naskah baru ke sistem. Anda perlu melakukan plotting/penunjukan reviewer untuk naskah ini.</p>

<div class="info-box">
    <p><strong>Detail Naskah:</strong></p>
    <table style="width: 100%; margin: 10px 0;">
        <tr>
            <td style="padding: 5px 0; width: 35%;"><strong>ID Naskah:</strong></td>
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
            <td style="padding: 5px 0;"><strong>Jenis Buku:</strong></td>
            <td style="padding: 5px 0;">
                @if(($book_type ?? '') === 'bukuajar')
                    Buku Ajar
                @elseif(($book_type ?? '') === 'bukureferensi')
                    Buku Referensi
                @else
                    {{ $book_type ?? 'N/A' }}
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding: 5px 0;"><strong>Diupload Pada:</strong></td>
            <td style="padding: 5px 0;">{{ \Carbon\Carbon::parse($uploaded_at)->setTimezone('Asia/Jakarta')->format('d F Y, H:i') }} WIB</td>
        </tr>
    </table>
</div>

<p>Silakan login ke panel admin untuk melihat detail naskah dan memilih reviewer yang sesuai.</p>

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ $review_url }}"
        class="btn">
        Plotting Reviewer
    </a>
</div>

<hr class="divider">

<p style="font-size:13px; color:#666;">Email ini dikirim secara otomatis kepada semua administrator sistem. Mohon untuk tidak membalas email ini.</p>
@endsection
