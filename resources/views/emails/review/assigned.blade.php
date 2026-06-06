@extends('emails.layout')

@section('content')
<p>Halo, <strong>{{ $reviewer_name }}</strong>!</p>

<p>Anda telah ditugaskan untuk mereview naskah buku berikut:</p>

<div class="info-box">
    <p>📖 <strong>Judul Buku:</strong> {{ $book_title }}</p>
    <p style="margin-top:8px">✍️ <strong>Penulis:</strong> {{ $author_name }}</p>
    <p style="margin-top:8px">📅 <strong>Deadline Review:</strong> {{ $deadline_date }}</p>
</div>

<p>Silakan login ke sistem untuk mengunduh naskah dan mengisi formulir penilaian berdasarkan rubrik yang telah disediakan.</p>

<a href="{{ $review_url }}" class="btn">Mulai Review</a>

<hr class="divider">

<p style="font-size:13px; color:#666;">Terima kasih atas kontribusi Anda dalam proses penerbitan buku hibah AEP UNY.</p>
@endsection
