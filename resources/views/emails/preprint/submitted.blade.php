@extends('emails.layout')

@section('content')
<p>Halo, Tim Penerbit!</p>

<p>Naskah berikut telah masuk ke tahap <strong>Pra-Cetak</strong> dan memerlukan pengecekan kelengkapan administratif dari Anda.</p>

<div class="info-box">
    <p>📖 <strong>Judul Buku:</strong> {{ $book_title }}</p>
    <p style="margin-top:8px">✍️ <strong>Penulis:</strong> {{ $author_name }}</p>
</div>

<p>Silakan periksa kesesuaian desain sampul, jumlah halaman, kelengkapan dokumen administrasi, dan aspek non-substansi lainnya.</p>

<a href="{{ $preprint_url }}" class="btn">Periksa Naskah</a>

<hr class="divider">

<p style="font-size:13px; color:#666;">Setelah pengecekan, berikan keputusan <em>Approved</em> atau <em>Revised</em> melalui sistem.</p>
@endsection
