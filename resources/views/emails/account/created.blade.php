@extends('emails.layout')

@section('content')
<p>Halo, <strong>{{ $name }}</strong>!</p>

<p>Selamat datang di Sistem Penulisan Hibah Buku AEP UNY. Akun Anda telah berhasil dibuat. Berikut informasi login Anda:</p>

<div class="info-box">
    <p><strong>Email:</strong> {{ $email }}</p>
    <p style="margin-top:8px"><strong>Password:</strong> {{ $password }}</p>
</div>

<p>Silakan login ke sistem menggunakan informasi di atas dan segera ganti password Anda setelah login pertama.</p>

<a href="{{ $login_url }}" class="btn">Login ke Sistem</a>

<hr class="divider">

<p style="font-size:13px; color:#666;">Jika Anda tidak merasa mendaftar di sistem ini, abaikan email ini atau hubungi administrator.</p>
@endsection
