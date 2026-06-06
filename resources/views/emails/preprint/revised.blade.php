@extends('emails.layout')

@section('content')
<p>Halo, <strong>{{ $name }}</strong>!</p>

<p>Naskah buku Anda telah diperiksa oleh penerbit dan memerlukan <span class="badge badge-warning">REVISI</span> sebelum dapat dilanjutkan ke tahap cetak.</p>

<div class="info-box">
    <p>📖 <strong>Judul Buku:</strong> {{ $book_title }}</p>
</div>

@if(!empty($notes))
<p><strong>Catatan Revisi dari Penerbit:</strong></p>
<div class="info-box" style="border-left-color: #e6a817;">
    <p>{{ $notes }}</p>
</div>
@endif

<p>Silakan perbaiki kelengkapan yang diminta dan perbarui data Anda melalui sistem.</p>

<a href="{{ $action_url }}" class="btn">Perbaiki Kelengkapan</a>

<hr class="divider">

<p style="font-size:13px; color:#666;">Setelah Anda memperbarui, penerbit akan otomatis mendapat notifikasi untuk memeriksa kembali.</p>
@endsection
