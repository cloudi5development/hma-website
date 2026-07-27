@extends('emails.layout')

@section('subject', 'Thanks for reaching out to Hire Minds Academy')
@section('preheader', 'We\'ve received your enquiry — our team will get back to you within 24 hours.')

@section('content')
    <h1 style="margin:0 0 6px;font-size:22px;line-height:1.3;color:#17161a;">Thank you, {{ $enquiry->name }}! 🎉</h1>
    <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#4b4038;">
        We've received your enquiry and a member of our team will get back to you
        <strong>within 24 hours</strong>. Here's a copy of what you sent us:
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="margin:0 0 22px;border:1px solid #efe9e3;border-radius:10px;overflow:hidden;">
        @if ($enquiry->looking_for)
            <tr><td style="padding:11px 14px;font-size:13px;color:#8a7d70;width:150px;border-bottom:1px solid #f3efe9;">Looking for</td><td style="padding:11px 14px;font-size:14px;color:#23180f;border-bottom:1px solid #f3efe9;">{{ $enquiry->looking_for }}</td></tr>
        @endif
        @if ($enquiry->interest)
            <tr><td style="padding:11px 14px;font-size:13px;color:#8a7d70;border-bottom:1px solid #f3efe9;">Area of interest</td><td style="padding:11px 14px;font-size:14px;color:#23180f;border-bottom:1px solid #f3efe9;">{{ $enquiry->interest }}</td></tr>
        @endif
        <tr><td style="padding:11px 14px;font-size:13px;color:#8a7d70;{{ $enquiry->message ? 'border-bottom:1px solid #f3efe9;' : '' }}">Phone</td><td style="padding:11px 14px;font-size:14px;color:#23180f;{{ $enquiry->message ? 'border-bottom:1px solid #f3efe9;' : '' }}">{{ $enquiry->phone }}</td></tr>
        @if ($enquiry->message)
            <tr><td style="padding:11px 14px;font-size:13px;color:#8a7d70;vertical-align:top;">Message</td><td style="padding:11px 14px;font-size:14px;color:#23180f;">{{ $enquiry->message }}</td></tr>
        @endif
    </table>

    <p style="margin:0 0 22px;font-size:15px;line-height:1.7;color:#4b4038;">
        In the meantime, feel free to call us at <strong>+91 78240 94044</strong> if it's urgent.
    </p>

    <p style="margin:0;font-size:15px;color:#4b4038;">Warm regards,<br><strong>The Hire Minds Academy Team</strong></p>
@endsection
