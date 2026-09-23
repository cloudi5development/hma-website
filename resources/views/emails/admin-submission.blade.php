{{--
| The admin's copy of a contact enquiry, course enquiry or event registration.
|
| The rows are whatever the record holds (SummarisesForAdminEmail), so a field
| added to an enquiry later appears here on its own. Styled like the other
| emails in this folder — inline CSS and a table, because mail clients have no
| stylesheet.
--}}
@extends('emails.layout')

@section('subject', $subjectLine)
@section('footer_note', 'Replying to this email answers the person who submitted it.')
@section('preheader', $heading . ($submission->name ? ' from ' . $submission->name : ''))

@section('content')
    <h1 style="margin:0 0 6px;font-size:22px;line-height:1.3;color:#17161a;">{{ $heading }}</h1>
    <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#4b4038;">
        Somebody has just submitted the form on the website. Here is what they sent:
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="margin:0 0 22px;border:1px solid #efe9e3;border-radius:10px;overflow:hidden;">
        @foreach ($submission->adminEmailRows() as $row)
            <tr>
                <td style="padding:11px 14px;font-size:13px;color:#8a7d70;width:170px;vertical-align:top;border-bottom:1px solid #f3efe9;">{{ $row['label'] }}</td>
                <td style="padding:11px 14px;font-size:14px;color:#23180f;border-bottom:1px solid #f3efe9;white-space:pre-wrap;">{{ $row['value'] }}</td>
            </tr>
        @endforeach
        <tr>
            <td style="padding:11px 14px;font-size:13px;color:#8a7d70;">Received</td>
            <td style="padding:11px 14px;font-size:14px;color:#23180f;">{{ $submission->adminEmailReceivedAt() }}</td>
        </tr>
    </table>

    <p style="margin:0 0 22px;font-size:15px;line-height:1.7;color:#4b4038;">
        <a href="{{ $panelUrl }}" style="color:#A85A2E;font-weight:600;">Open it in the admin panel</a>
        to reply and change its status.
    </p>

    <p style="margin:0;font-size:15px;color:#4b4038;">— Hire Minds Academy</p>
@endsection
