@extends('emails.layout')

@section('content')
<p>Halo, <strong>{{ $name }}</strong>,</p>

<p>Terima kasih telah mengirimkan formulir kesediaan penulis. Kami mohon maaf, pengajuan Anda belum dapat kami terima.</p>

@if(!empty($rejection_reason))
<div class="info-box">
    <p><strong>Alasan penolakan:</strong></p>
    <p>{{ $rejection_reason }}</p>
</div>
@endif

<p>Silakan periksa kembali persyaratan dan data yang Anda kirimkan. Jika Anda ingin mengajukan kembali di kemudian hari, mohon pastikan semua informasi telah lengkap dan sesuai.</p>

<hr class="divider">

<p style="font-size:13px; color:#666;">Jika Anda memerlukan bantuan atau klarifikasi, silakan hubungi administrator sistem.</p>
@endsection
