<?php

// namespace App\Services;

// // use App\Mail\ContactMail;
// use App\Mail\DefaultMail;
// // use App\Mail\NewsletterMail;
// use Illuminate\Support\Facades\Mail;

// class MailSenderService
// {
//     public static function sendMail(string $name, string $subject, string $content, string $toMail) {
//         Mail::send( new DefaultMail(
//             name: $name,
//             mailSubject: $subject,
//             content: $content,
//             toMail: $toMail
//         ));
//     }


//     // public static function sendBulkNewsletterMail(string $subject, string $content, array $emails) {
//     //     Mail::to($emails)->send(new NewsletterMail(mailSubject: $subject, content: $content));
//     // }


//     // public static function sendContactMail(string $name, string $subject, string $content, string $formMail, string $toMail) {
//     //     Mail::send( new ContactMail(
//     //         name: $name,
//     //         mailSubject: $subject,
//     //         content: $content,
//     //         formMail: $formMail,
//     //         toMail: $toMail
//     //     ));
//     // }

// }
