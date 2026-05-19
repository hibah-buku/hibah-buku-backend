@extends('emails.layout')

@section('content')
<p>Halo, <strong>{{ $name }}</strong>!</p>

<p>Review naskah buku Anda telah <strong>selesai</strong> dilakukan oleh para reviewer. Silakan pelajari catatan dan penilaian yang diberikan, lalu lakukan revisi sesuai masukan.</p>

<div class="info-box">
    <p>📖 <strong>Judul Buku:</strong> {{ $book_title }}</p>
    <p style="margin-top:8px">📅 <strong>Deadline Unggah Revisi:</strong> {{ $deadline_revision }}</p>
</div>

<p>Anda dapat melihat detail hasil review dan mengunggah revisi naskah melalui tombol di bawah ini.</p>

<a href="{{ $review_url }}" class="btn">Lihat Hasil Review</a>

<hr class="divider">

<p style="font-size:13px; color:#666;">Pastikan revisi diunggah sebelum batas waktu agar proses penerbitan tidak terhambat.</p>
@endsection
