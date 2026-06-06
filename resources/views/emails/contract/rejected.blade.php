@extends('emails.layout')

@section('content')
<p>Halo, <strong>{{ $name }}</strong>,</p>

<p>Kami mohon maaf, kontrak hibah buku untuk judul <strong>"{{ $book_title }}"</strong> belum dapat kami terima.</p>

@if(!empty($rejection_reason))
<div class="info-box">
    <p><strong>Alasan penolakan:</strong></p>
    <p>{{ $rejection_reason }}</p>
</div>
@endif

<p>Silakan periksa kembali kontrak yang Anda upload dan lakukan perbaikan sesuai dengan catatan di atas. Setelah perbaikan selesai, Anda dapat mengupload ulang kontrak yang telah diperbaiki.</p>

@if(!empty($resubmit_url))
<div style="text-align: center; margin: 30px 0;">
    <a href="{{ $resubmit_url }}"
       class="btn">
        Upload Ulang Kontrak
    </a>
</div>
@endif

<hr class="divider">

<p style="font-size:13px; color:#666;">Jika Anda memerlukan bantuan atau klarifikasi, silakan hubungi administrator sistem.</p>
@endsection
