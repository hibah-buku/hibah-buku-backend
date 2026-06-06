@extends('emails.layout')

@section('content')
<p>Halo, <strong>{{ $reviewer_name }}</strong>!</p>

@if($days_remaining > 0)
    <p>Pengingat: Anda memiliki <strong>{{ $days_remaining }} hari lagi</strong> untuk menyelesaikan review naskah berikut:</p>
@else
    <p>⚠️ <strong>Hari ini adalah deadline</strong> untuk menyelesaikan review naskah berikut:</p>
@endif

<div class="info-box">
    <p>📖 <strong>Judul Buku:</strong> {{ $book_title }}</p>
    <p style="margin-top:8px">📅 <strong>Deadline:</strong> {{ $deadline_date }}</p>
    @if($days_remaining > 0)
        <p style="margin-top:8px">⏳ <strong>Sisa waktu:</strong> {{ $days_remaining }} hari</p>
    @endif
</div>

<p>Mohon segera login dan selesaikan penilaian serta catatan review Anda.</p>

<a href="{{ $review_url }}" class="btn">Lanjutkan Review</a>

<hr class="divider">

<p style="font-size:13px; color:#666;">Jika Anda telah mengirimkan review, abaikan email ini.</p>
@endsection
