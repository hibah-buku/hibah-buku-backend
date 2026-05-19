@extends('emails.layout')

@section('content')
<p>Halo, <strong>{{ $name }}</strong>!</p>

<p>Kabar baik! Kontrak hibah buku Anda telah <strong>divalidasi</strong> oleh admin. Langkah selanjutnya adalah mengunggah draft awal naskah buku Anda.</p>

<div class="info-box">
    <p>⏰ <strong>Deadline Upload Draft Awal:</strong> {{ $deadline_upload }}</p>
</div>

<p>Pastikan Anda mengunggah draft awal sebelum batas waktu tersebut. Sistem akan mengirimkan pengingat jika mendekati tenggat waktu.</p>

<a href="{{ $upload_url }}" class="btn">Unggah Draft Awal</a>

<hr class="divider">

<p style="font-size:13px; color:#666;">Butuh bantuan? Hubungi tim admin AEP UNY.</p>
@endsection
