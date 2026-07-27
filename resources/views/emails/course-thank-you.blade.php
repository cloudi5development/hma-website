@extends('emails.layout')

@section('subject', 'Your enquiry about ' . $enquiry->course_name)
@section('preheader', 'Thanks for your interest in ' . $enquiry->course_name . ' — our team will reach out shortly.')

@section('content')
    <h1 style="margin:0 0 6px;font-size:22px;line-height:1.3;color:#17161a;">Thank you, {{ $enquiry->name }}! 🎓</h1>
    <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#4b4038;">
        Thanks for your interest in our program. We've received your enquiry and a
        course advisor will contact you shortly.
    </p>

    {{-- Course highlight card --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="margin:0 0 22px;background:#fff8ec;border:1px solid #f3e4c6;border-radius:12px;">
        <tr>
            <td style="padding:18px 20px;">
                <div style="font-size:12px;letter-spacing:.4px;text-transform:uppercase;color:#b07d2e;font-weight:700;">Course</div>
                <div style="font-size:18px;font-weight:700;color:#23180f;margin-top:4px;">{{ $enquiry->course_name }}</div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px;font-size:15px;font-weight:700;color:#23180f;">What happens next?</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px;">
        <tr><td style="padding:4px 0;font-size:14px;line-height:1.7;color:#4b4038;">1️⃣ &nbsp;Our advisor reviews your enquiry.</td></tr>
        <tr><td style="padding:4px 0;font-size:14px;line-height:1.7;color:#4b4038;">2️⃣ &nbsp;We call/email you with the batch details, fees and curriculum.</td></tr>
        <tr><td style="padding:4px 0;font-size:14px;line-height:1.7;color:#4b4038;">3️⃣ &nbsp;You pick a start date and reserve your seat.</td></tr>
    </table>

    <p style="margin:0 0 22px;font-size:14px;line-height:1.7;color:#4b4038;background:#f5f1ec;border-radius:8px;padding:12px 16px;">
        ⏱️ <strong>Expected response time:</strong> within 24 hours (Mon–Sat). Need it sooner? Call <strong>+91 78240 94044</strong>.
    </p>

    <p style="margin:0;font-size:15px;color:#4b4038;">Warm regards,<br><strong>The Hire Minds Academy Team</strong></p>
@endsection
