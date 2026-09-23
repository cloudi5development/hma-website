@extends('emails.layout')

@section('subject', $form->notification_subject)
@section('preheader', 'A new response has been submitted through ' . $form->title . '.')

@section('content')
    <h1 style="margin:0 0 6px;font-size:22px;line-height:1.3;color:#17161a;">New response received</h1>
    <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#4b4038;">
        Somebody has completed <strong>{{ $form->title }}</strong>. Here is what they sent:
    </p>

    {{-- Rows come from the response's own snapshot, so this reads correctly even
         if the form is edited before the email is opened. --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="margin:0 0 22px;border:1px solid #efe9e3;border-radius:10px;overflow:hidden;">
        @foreach ($response->values as $value)
            <tr>
                <td style="padding:11px 14px;font-size:13px;color:#8a7d70;width:170px;vertical-align:top;border-bottom:1px solid #f3efe9;">{{ $value->field_label }}</td>
                <td style="padding:11px 14px;font-size:14px;color:#23180f;border-bottom:1px solid #f3efe9;white-space:pre-wrap;">{{ $value->display ?: '—' }}</td>
            </tr>
        @endforeach
        <tr>
            <td style="padding:11px 14px;font-size:13px;color:#8a7d70;">Submitted</td>
            <td style="padding:11px 14px;font-size:14px;color:#23180f;">{{ $response->submitted_label }}</td>
        </tr>
    </table>

    {{-- Uploads are not attached: they are held on the private disk and are only
         reachable by a signed-in admin. --}}
    @php
        // Worked out here, not inline: "status@if (...)" is a directive glued to
        // a word, which Blade does not compile - it printed the @if and @endif
        // into the email as text. Nothing may sit tight against a directive.
        $filesLine = $response->values->contains(fn ($v) => $v->isFile())
            ? ' or download the files attached to it'
            : '';
    @endphp
    <p style="margin:0 0 22px;font-size:15px;line-height:1.7;color:#4b4038;">
        <a href="{{ route('backend.forms.responses.show', [$form, $response]) }}"
           style="color:#A85A2E;font-weight:600;">Open this response in the admin panel</a>
        to change its status{{ $filesLine }}.
    </p>

    <p style="margin:0;font-size:15px;color:#4b4038;">— Hire Minds Academy</p>
@endsection
