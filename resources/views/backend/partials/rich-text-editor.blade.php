{{--
|--------------------------------------------------------------------------
| Rich text editor — shared admin component
|--------------------------------------------------------------------------
|
| Turns a plain <textarea> into a WYSIWYG editor. Drop it in after the field:
|
|   <textarea id="content" name="content" class="form-control-hm">…</textarea>
|   @include('backend.partials.rich-text-editor', ['selector' => '#content'])
|
| Params: $selector (required), $height (optional, default 520).
|
| TinyMCE 6 from jsdelivr rather than cdn.tiny.cloud: the npm build needs no API
| key and shows no "register this domain" banner, and 6.x is MIT-licensed, which
| the 7.x line is not.
|
| It stays a progressive enhancement — the field is a working HTML textarea
| before the script runs and if the CDN is unreachable, and TinyMCE writes back
| into it on submit, so the form posts the same `content` either way.
--}}
@php
    $editorHeight = $height ?? 520;
@endphp

@once
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
    @endpush
@endonce

@push('scripts')
    <script>
        (function () {
            'use strict';

            // The CDN failed — leave the textarea as the plain HTML field it is.
            if (typeof tinymce === 'undefined') return;

            tinymce.init({
                selector: '{{ $selector }}',
                height: {{ $editorHeight }},
                menubar: false,
                branding: false,
                promotion: false,
                plugins: 'lists link table code autolink charmap searchreplace wordcount',
                toolbar: 'undo redo | blocks | bold italic underline | bullist numlist |'
                       + ' link table | alignleft aligncenter | removeformat | code',
                // Headings only from h2 down: the page's own <h1> is the banner
                // title, and a second one would compete with it.
                block_formats: 'Paragraph=p; Heading=h2; Sub heading=h3; Small heading=h4',
                // Keep the markup close to what was hand-written before, so the
                // frontend's element-based styles still match it.
                forced_root_block: 'p',
                convert_urls: false,
                paste_as_text: false,
                // Word and Google Docs paste in a wall of inline styles and
                // classes; strip them so pasted policy text picks up the site's
                // typography instead of the document's.
                paste_remove_styles_if_webkit: true,
                invalid_styles: { '*': 'font-family font-size color background-color line-height' },
                content_style:
                    'body{font-family:Inter,system-ui,sans-serif;font-size:15px;line-height:1.8;color:#2A1D14;padding:14px}'
                    + 'h2{font-size:21px;font-weight:700;margin:26px 0 10px}'
                    + 'h3{font-size:18px;font-weight:700;margin:22px 0 8px}'
                    + 'h4{font-size:16px;font-weight:700;margin:20px 0 8px}'
                    + 'p{margin:0 0 14px}ul,ol{padding-left:22px;margin:0 0 14px}li{margin-bottom:6px}'
                    + 'a{color:#8C2E13}'
                    + 'table{border-collapse:collapse;width:100%}'
                    + 'th,td{border:1px solid #E6DBD2;padding:8px 10px;text-align:left}'
                    + 'th{background:#FDF9F6}',
            });
        })();
    </script>
@endpush
