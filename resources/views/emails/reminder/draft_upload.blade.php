@extends('emails.layout')

@section('content')
<p>Halo, <strong>{{ $name }}</strong>!</p>

@if($days_remaining > 0)
    <p>Ini adalah pengingat bahwa Anda memiliki <strong>{{ $days_remaining }} hari lagi</strong> untuk mengunggah draft awal naskah buku Anda.</p>
@else
    <p>⚠️ <strong>Ini adalah hari terakhir</strong> untuk mengunggah draft awal naskah buku Anda!</p>
@endif

<div class="info-box">
    <p>📅 <strong>Deadline:</strong> {{ $deadline_date }}</p>
    @if($days_remaining > 0)
        <p style="margin-top:8px">⏳ <strong>Sisa waktu:</strong> {{ $days_remaining }} hari</p>
    @endif
</div>

<p>Segera unggah draft awal buku Anda agar proses review dapat segera dimulai.</p>

<a href="{{ $upload_url }}" class="btn">Unggah Sekarang</a>

<hr class="divider">

<p style="font-size:13px; color:#666;">Jika Anda telah mengunggah, abaikan email ini.</p>
@endsection
