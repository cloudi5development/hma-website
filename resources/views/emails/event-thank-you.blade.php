@extends('emails.layout')

@section('subject', 'You are registered for ' . $registration->event_title)
@section('preheader', 'Your seat at ' . $registration->event_title . ' is confirmed — here are the details.')

@section('content')
    @php
        // The registration keeps a title snapshot, so the mail still reads
        // correctly if the event was renamed or removed afterwards; the relation
        // is only used for the extra detail lines, which are simply left out
        // when it is gone.
        $event = $registration->event;
    @endphp

    <h1 style="margin:0 0 6px;font-size:22px;line-height:1.3;color:#17161a;">Thank you, {{ $registration->name }}! 🎟️</h1>
    <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#4b4038;">
        We've received your registration. Your place is reserved and our team will
        be in touch with the joining details shortly.
    </p>

    {{-- Event highlight card --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="margin:0 0 22px;background:#fff8ec;border:1px solid #f3e4c6;border-radius:12px;">
        <tr>
            <td style="padding:18px 20px;">
                <div style="font-size:12px;letter-spacing:.4px;text-transform:uppercase;color:#b07d2e;font-weight:700;">Event</div>
                <div style="font-size:18px;font-weight:700;color:#23180f;margin-top:4px;">{{ $registration->event_title }}</div>

                @if ($event?->formatted_date)
                    <div style="font-size:14px;color:#4b4038;margin-top:10px;">📅 &nbsp;{{ $event->formatted_date }}@if ($event->weekday), {{ $event->weekday }}@endif</div>
                @endif
                @if ($event?->time_range)
                    <div style="font-size:14px;color:#4b4038;margin-top:4px;">🕒 &nbsp;{{ $event->time_range }}</div>
                @endif
                @if ($event?->full_address)
                    <div style="font-size:14px;color:#4b4038;margin-top:4px;">📍 &nbsp;{{ $event->full_address }}</div>
                @endif
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px;font-size:15px;font-weight:700;color:#23180f;">What happens next?</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px;">
        <tr><td style="padding:4px 0;font-size:14px;line-height:1.7;color:#4b4038;">1️⃣ &nbsp;Our team confirms your seat and adds you to the attendee list.</td></tr>
        <tr><td style="padding:4px 0;font-size:14px;line-height:1.7;color:#4b4038;">2️⃣ &nbsp;You'll get the venue directions and the agenda before the day.</td></tr>
        <tr><td style="padding:4px 0;font-size:14px;line-height:1.7;color:#4b4038;">3️⃣ &nbsp;Turn up a few minutes early — registration opens at the door.</td></tr>
    </table>

    <p style="margin:0 0 22px;font-size:14px;line-height:1.7;color:#4b4038;background:#f5f1ec;border-radius:8px;padding:12px 16px;">
        ℹ️ &nbsp;Can't make it after all? Just reply to this email and we'll free up your seat.
        Questions before the day? Call <strong>+91 78240 94044</strong>.
    </p>

    <p style="margin:0;font-size:15px;color:#4b4038;">See you there,<br><strong>The Hire Minds Academy Team</strong></p>
@endsection
