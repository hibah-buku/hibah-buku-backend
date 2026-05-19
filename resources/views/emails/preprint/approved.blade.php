@extends('emails.layout')

@section('content')
<p>Halo, <strong>{{ $name }}</strong>!</p>

<p>🎉 Selamat! Naskah buku Anda telah <span class="badge badge-success">DISETUJUI</span> oleh penerbit dan masuk ke status <strong>Siap Cetak</strong>.</p>

<div class="info-box">
    <p>📖 <strong>Judul Buku:</strong> {{ $book_title }}</p>
    <p style="margin-top:8px">✅ <strong>Status:</strong> Siap Cetak</p>
</div>

@if(!empty($notes))
<p><strong>Catatan dari Penerbit:</strong></p>
<div class="info-box">
    <p>{{ $notes }}</p>
</div>
@endif

<a href="{{ $action_url }}" class="btn">Lihat Status Naskah</a>

<hr class="divider">

<p style="font-size:13px; color:#666;">Terima kasih atas kontribusi Anda dalam program hibah buku AEP UNY.</p>
@endsection
