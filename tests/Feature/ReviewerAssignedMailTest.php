<?php

use App\Mail\ReviewerAssignedMail;
use App\Models\ReviewerAssignment;
use Illuminate\Support\Facades\Mail;

test('reviewer assignment email can be sent with assignment model data', function () {
    Mail::fake();

    $assignment = new ReviewerAssignment([
        'id' => 42,
        'book_title' => 'Naskah Uji',
        'reviewer_email' => 'reviewer@example.com',
        'reviewer_name' => 'Reviewer Uji',
        'author_email' => 'author@example.com',
        'deadline_review' => now()->addDays(7),
    ]);

    Mail::to('reviewer@example.com')->send(new ReviewerAssignedMail($assignment));

    Mail::assertSent(ReviewerAssignedMail::class, function (ReviewerAssignedMail $mail) use ($assignment) {
        return $mail->hasTo('reviewer@example.com')
            && $mail->viewData['book_title'] === $assignment->book_title
            && $mail->viewData['reviewer_name'] === 'Reviewer Uji';
    });
});

test('reviewer assignment email accepts notification payload array data', function () {
    Mail::fake();

    Mail::to('reviewer@example.com')->send(new ReviewerAssignedMail([
        'reviewer_name' => 'Reviewer Uji',
        'author_name' => 'Penulis Uji',
        'book_title' => 'Naskah Payload',
        'deadline_date' => '12 Juni 2026',
        'review_url' => 'https://example.test/review/1',
    ]));

    Mail::assertSent(ReviewerAssignedMail::class, function (ReviewerAssignedMail $mail) {
        return $mail->viewData['book_title'] === 'Naskah Payload'
            && $mail->viewData['review_url'] === 'https://example.test/review/1';
    });
});
