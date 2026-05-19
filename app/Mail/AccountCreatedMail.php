<?php

namespace App\Mail;

/**
 * Dikirim ke penulis setelah akun berhasil di-generate otomatis.
 * Vars: name, email, password, login_url
 */
class AccountCreatedMail extends BaseNotificationMail
{
    protected string $templateCode = 'account_created';

    protected function defaultSubject(): string
    {
        return 'Akun Sistem Hibah Buku Anda Telah Dibuat';
    }

    protected function defaultView(): string
    {
        return 'emails.account.created';
    }
}
