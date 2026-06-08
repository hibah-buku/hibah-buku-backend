<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reminder Tugas Review Naskah</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 30px;">
        <h2 style="color: #b45309; margin-top: 0;">Peringatan Tenggat Waktu Penilaian</h2>
        
        <p>Halo <strong>{{ $assignment->reviewer_name }}</strong>,</p>
        
        <p>Ini adalah email pengingat (reminder) bahwa tenggat waktu untuk tugas penilaian naskah Anda akan segera berakhir dalam waktu kurang lebih <strong>3 hari</strong>.</p>
        
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; background-color: white; border-radius: 6px; padding: 15px;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #fef3c7; width: 30%; font-weight: bold;">Judul Buku</td>
                <td style="padding: 8px; border-bottom: 1px solid #fef3c7;">{{ $assignment->book_title }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #fef3c7; font-weight: bold;">Tenggat Waktu</td>
                <td style="padding: 8px; border-bottom: 1px solid #fef3c7; color: #b91c1c; font-weight: bold;">{{ \Carbon\Carbon::parse($assignment->deadline_review)->format('d F Y H:i') }}</td>
            </tr>
        </table>
        
        <p>Mohon segera login ke dashboard dan menyelesaikan penilaian kelayakan naskah tersebut agar proses penerbitan buku dapat segera dilanjutkan.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="http://localhost:5173" style="background-color: #b45309; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">Selesaikan Penilaian Sekarang</a>
        </div>
        
        <p style="font-size: 13px; color: #92400e; margin-top: 40px; border-top: 1px solid #fde68a; padding-top: 20px;">
            Abaikan email ini jika Anda telah menyelesaikan penilaian di sistem. Terima kasih atas kerja samanya.
        </p>
    </div>
</body>
</html>
