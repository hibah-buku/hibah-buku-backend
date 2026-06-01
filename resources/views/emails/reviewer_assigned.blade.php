<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tugas Review Naskah Baru</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 30px;">
        <h2 style="color: #1e40af; margin-top: 0;">Halo, {{ $assignment->reviewer_name }}</h2>
        
        <p>Anda telah ditugaskan untuk memberikan penilaian (review) terhadap sebuah naskah buku baru dengan rincian sebagai berikut:</p>
        
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <tr>
                <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; width: 30%; font-weight: bold;">Judul Buku</td>
                <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">{{ $assignment->book_title }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; font-weight: bold;">ID Naskah</td>
                <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">#{{ $assignment->manuscript_id }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; font-weight: bold;">Tenggat Waktu</td>
                <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; color: #b91c1c; font-weight: bold;">{{ \Carbon\Carbon::parse($assignment->deadline_review)->format('d F Y H:i') }}</td>
            </tr>
        </table>
        
        <p>Silakan login ke dashboard sistem untuk melihat naskah secara lengkap dan memberikan nilai beserta komentar Anda.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="http://localhost:5173" style="background-color: #1e40af; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">Login ke Dashboard</a>
        </div>
        
        <p style="font-size: 13px; color: #64748b; margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
            Email ini dikirimkan secara otomatis oleh Sistem Hibah Buku. Mohon jangan membalas email ini secara langsung.
        </p>
    </div>
</body>
</html>
