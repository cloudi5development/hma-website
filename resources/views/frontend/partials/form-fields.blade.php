{{--
|--------------------------------------------------------------------------
| Dynamic form renderer
|--------------------------------------------------------------------------
|
| Draws the controls for any admin-built form. Given a form's fields, it reads
| each one's type off App\Support\FormFieldType and renders the matching control
| — there is no markup here for any particular question, and no branch that
| knows what a form is "usually" for.
|
| Used by both the public page and the admin preview, so what an admin previews
| is the same HTML a visitor gets.
|
|   @include('frontend.partials.form-fields', ['fields' => $form->fields])
|
| The wrapping <form>, its CSRF token and its submit button belong to the page.
| Its stylesheet is assets/css/frontend/dynamic-form.css.
|
| On a quiz, form-structure also passes:
|   $quiz     true — every question takes its own row, its options are listed
|             one under another, and no description is shown under it
|   $numbers  field id => question number, counted across the whole form
--}}
@php
    $quiz    ??= false;
    $numbers ??= [];
@endphp

@foreach ($fields as $field)
    @php
        $key      = $field->field_key;
        $id       = 'hmf_' . $key;
        $invalid  = $errors->has($key) || $errors->has($key . '.*');
        // old() first so a failed submission gives the visitor their answers
        // back; the admin's default value is only the starting state.
        //
        // What comes back is whatever was POSTED, and a stale page or a forged
        // post can send a list where one answer belongs, or one answer where a
        // list belongs. It is coerced to the shape this control draws — printed
        // as-is, an array here turned the page that shows the visitor their
        // mistakes into a server error.
        $raw   = old($key, $field->default_value);
        $value = match ($field->control()) {
            'mc_grid', 'tick_grid' => is_array($raw) ? $raw : [],
            'checkbox'             => is_array($raw) ? array_values(array_filter($raw, 'is_scalar')) : $raw,
            default                => is_array($raw) ? '' : $raw,
        };
        $selected = is_array($value) ? array_map('strval', $value) : [(string) $value];

        // Every message for this question, flattened. A wildcard get() returns
        // them grouped under the key each belongs to ("rate.1" => [...]) — a
        // grid row, one of several files, one ticked box — and a group is not
        // text. unique() because a grid can raise the same message per row.
        $messages = collect($errors->get($key))
            ->merge(collect($errors->get($key . '.*'))->flatten())
            ->unique()
            ->values();
        $options  = $field->options;
        $cond     = $field->condition();

        // The browser's own `required` is left off a conditional field on
        // purpose. Such a field starts hidden, and a hidden required control
        // makes a form unsubmittable in a way the visitor cannot see or fix if
        // the script that reveals it has not run. The server still enforces it
        // for anyone the condition actually applies to — see
        // FormField::isVisibleFor().
        $clientRequired = $field->is_required && ! $cond;

        // The form lays out two questions to a row. These controls cannot live
        // in half a row and stay usable — a grid of four columns, a paragraph
        // box, a 0-10 scale — so they take the full width and the next question
        // starts a new row. Decided from the CONTROL rather than a list of type
        // keys, so a new type of the same shape gets the right width for free.
        //
        // A quiz is read question by question, down the page, so every question
        // is full width: two to a row, "1  2 / 3  4" reads as nonsense once the
        // questions are numbered.
        $wide = $quiz || in_array($field->control(), ['textarea', 'file', 'scale', 'mc_grid', 'tick_grid'], true);

        // A quiz shows no description line. Its questions are the question and
        // the choices; an instruction such as "Choose one" only repeats what a
        // row of radio buttons already says. Every aria-describedby below reads
        // this too, so none of them points at a line that was not drawn.
        $help = $quiz ? null : $field->help_text;

        $number = $numbers[$field->id] ?? null;
    @endphp

    {{-- A hidden field is not drawn at all: no wrapper, no label, no space on
         the page. It carries a value the page sets (a campaign source, say),
         and it is the one field type a visitor is never meant to see. --}}
    @if ($field->control() === 'hidden')
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @continue
    @endif

    {{-- data-cond-* is read by the conditional-logic script at the foot of the
         public page. A field with no condition carries none of these and is
         always visible. --}}
    <div class="hmf-field hmf-field--{{ $field->field_type }} @if ($wide) hmf-field--wide @endif @if ($invalid) is-invalid @endif"
         data-hmf-field="{{ $key }}"
         @if ($cond)
             data-cond-field="{{ $cond['field_key'] }}"
             data-cond-op="{{ $cond['operator'] }}"
             data-cond-value="{{ $cond['value'] }}"
             hidden
         @endif>

        {{-- Anything made of several inputs — a radio group, a scale, a rating,
             a grid — is labelled by a legend-like heading rather than a
             <label for>, which may only ever point at one input. --}}
        @if (in_array($field->control(), ['radio', 'checkbox', 'scale', 'rating', 'mc_grid', 'tick_grid'], true))
            <span class="hmf-label" id="{{ $id }}_label">
                @if ($number) <span class="hmf-num">{{ $number }}.</span> @endif
                {{ $field->label }}
                @if ($field->is_required) <span class="hmf-req" aria-hidden="true">*</span> @endif
            </span>
        @else
            <label class="hmf-label" for="{{ $id }}">
                @if ($number) <span class="hmf-num">{{ $number }}.</span> @endif
                {{ $field->label }}
                @if ($field->is_required) <span class="hmf-req" aria-hidden="true">*</span> @endif
            </label>
        @endif

        @switch ($field->control())

            @case ('textarea')
                <textarea class="hmf-control" id="{{ $id }}" name="{{ $key }}"
                          rows="{{ $field->setting('rows') ?: 5 }}"
                          placeholder="{{ $field->placeholder }}"
                          @if ($clientRequired) required @endif
                          @if ($invalid) aria-invalid="true" @endif
                          @if ($help) aria-describedby="{{ $id }}_help" @endif>{{ $value }}</textarea>
                @break

            @case ('select')
                <select class="hmf-control hmf-select" id="{{ $id }}" name="{{ $key }}"
                        @if ($clientRequired) required @endif
                        @if ($invalid) aria-invalid="true" @endif
                        @if ($help) aria-describedby="{{ $id }}_help" @endif>
                    <option value="">{{ $field->placeholder ?: '— Select —' }}</option>
                    @foreach ($options as $option)
                        <option value="{{ $option->value }}" @selected(in_array((string) $option->value, $selected, true))>{{ $option->label }}</option>
                    @endforeach
                </select>
                @break

            @case ('radio')
                <div class="hmf-choices @if ($quiz) hmf-choices--stacked @endif" role="radiogroup" aria-labelledby="{{ $id }}_label">
                    @foreach ($options as $i => $option)
                        <label class="hmf-choice" for="{{ $id }}_{{ $i }}">
                            <input type="radio" id="{{ $id }}_{{ $i }}" name="{{ $key }}"
                                   value="{{ $option->value }}"
                                   @checked(in_array((string) $option->value, $selected, true))
                                   @if ($clientRequired) required @endif>
                            <span>{{ $option->label }}</span>
                        </label>
                    @endforeach
                </div>
                @break

            @case ('checkbox')
                {{-- The [] suffix is what makes this post a list; the field's
                     rules validate it as an array. --}}
                <div class="hmf-choices @if ($quiz) hmf-choices--stacked @endif" role="group" aria-labelledby="{{ $id }}_label">
                    @foreach ($options as $i => $option)
                        <label class="hmf-choice" for="{{ $id }}_{{ $i }}">
                            <input type="checkbox" id="{{ $id }}_{{ $i }}" name="{{ $key }}[]"
                                   value="{{ $option->value }}"
                                   @checked(in_array((string) $option->value, $selected, true))>
                            <span>{{ $option->label }}</span>
                        </label>
                    @endforeach
                </div>
                @break

            @case ('file')
                <input class="hmf-control hmf-file" type="file"
                       id="{{ $id }}" name="{{ $key }}@if ($field->allowsMultipleFiles())[]@endif"
                       accept="{{ $field->acceptAttribute() }}"
                       @if ($field->allowsMultipleFiles()) multiple @endif
                       @if ($clientRequired) required @endif
                       @if ($invalid) aria-invalid="true" @endif
                       aria-describedby="{{ $id }}_help">
                {{-- The limits are stated rather than left to be discovered by a
                     rejected upload. `accept` is a convenience for the picker;
                     the real check is server-side in FormField::validationRules. --}}
                <p class="hmf-help" id="{{ $id }}_help">
                    @if ($help) {{ $help }} · @endif
                    {{ strtoupper(implode(', ', $field->allowedExtensions())) }}
                    · up to {{ \App\Support\UploadLimit::label($field->maxFileKb()) }}
                    @if ($field->allowsMultipleFiles()) · several files allowed @endif
                </p>
                @break

            @case ('scale')
                {{-- A linear scale is a radio group wearing different clothes:
                     one choice out of a configured range, with the ends named.
                     The range is the admin's — nothing here assumes 1 to 5. --}}
                <div class="hmf-scale" role="radiogroup" aria-labelledby="{{ $id }}_label">
                    @if ($field->scaleMinLabel())
                        <span class="hmf-scale__end">{{ $field->scaleMinLabel() }}</span>
                    @endif

                    <div class="hmf-scale__steps">
                        @foreach ($field->scaleSteps() as $step)
                            <label class="hmf-scale__step" for="{{ $id }}_{{ $step }}">
                                <span class="hmf-scale__num">{{ $step }}</span>
                                <input type="radio" id="{{ $id }}_{{ $step }}" name="{{ $key }}"
                                       value="{{ $step }}"
                                       @checked((string) $value === (string) $step)
                                       @if ($clientRequired) required @endif>
                            </label>
                        @endforeach
                    </div>

                    @if ($field->scaleMaxLabel())
                        <span class="hmf-scale__end">{{ $field->scaleMaxLabel() }}</span>
                    @endif
                </div>
                @break

            @case ('rating')
                {{-- Radio inputs behind the icons, so a rating is keyboard
                     operable and submits without JavaScript. The fill on hover
                     and on selection is CSS only — see dynamic-form.css. --}}
                @php
                    $shape = $field->ratingIcon();
                    $glyph = [
                        'star'   => 'm12 3.6 2.6 5.3 5.8.85-4.2 4.1 1 5.75L12 16.9l-5.2 2.7 1-5.75-4.2-4.1 5.8-.85Z',
                        'heart'  => 'M12 20.3s-7.5-4.6-7.5-9.4A4.4 4.4 0 0 1 12 8.2a4.4 4.4 0 0 1 7.5 2.7c0 4.8-7.5 9.4-7.5 9.4Z',
                        'circle' => 'M12 4.2a7.8 7.8 0 1 1 0 15.6 7.8 7.8 0 0 1 0-15.6Z',
                    ][$shape];
                @endphp
                <div class="hmf-rating hmf-rating--{{ $shape }}" role="radiogroup" aria-labelledby="{{ $id }}_label">
                    @for ($n = 1; $n <= $field->ratingCount(); $n++)
                        <label class="hmf-rating__item" for="{{ $id }}_{{ $n }}"
                               title="{{ $n }} of {{ $field->ratingCount() }}">
                            <input type="radio" id="{{ $id }}_{{ $n }}" name="{{ $key }}" value="{{ $n }}"
                                   @checked((string) $value === (string) $n)
                                   @if ($clientRequired) required @endif>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $glyph }}"/></svg>
                            <span class="visually-hidden">{{ $n }} of {{ $field->ratingCount() }}</span>
                        </label>
                    @endfor
                </div>
                @break

            @case ('mc_grid')
            @case ('tick_grid')
                {{-- Rows down the side, columns across the top, both the admin's.
                     Inputs are named by the row's POSITION rather than its text:
                     a row labelled "Support / Service" cannot be a validation
                     key, and the position is mapped back to the row on the way
                     into storage (FormSubmissionService::gridValue).

                     A real <table> so a screen reader announces which row and
                     column a control belongs to; under 768px the CSS restacks it
                     into one block per row. --}}
                @php
                    $isTick   = $field->control() === 'tick_grid';
                    $gridOld  = is_array($value) ? $value : [];
                @endphp
                <div class="hmf-grid__scroll">
                    <table class="hmf-grid">
                        <thead>
                            <tr>
                                <td class="hmf-grid__corner"><span class="visually-hidden">{{ $field->label }}</span></td>
                                @foreach ($field->columns as $column)
                                    <th scope="col">{{ $column->label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($field->rows as $r => $row)
                                @php $picked = array_map('strval', array_filter((array) ($gridOld[$r] ?? []), 'is_scalar')); @endphp
                                <tr>
                                    <th scope="row">{{ $row->label }}</th>
                                    @foreach ($field->columns as $column)
                                        <td data-label="{{ $column->label }}">
                                            <label class="hmf-grid__cell">
                                                <input type="{{ $isTick ? 'checkbox' : 'radio' }}"
                                                       name="{{ $key }}[{{ $r }}]@if ($isTick)[]@endif"
                                                       value="{{ $column->value }}"
                                                       @checked(in_array((string) $column->value, $picked, true))
                                                       aria-label="{{ $row->label }} — {{ $column->label }}">
                                                <span class="hmf-grid__cell-label">{{ $column->label }}</span>
                                            </label>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- Row errors are printed with the rest, below — once each. --}}
                @break

            @default
                @php
                    $spec     = \App\Support\FormFieldType::spec($field->field_type);
                    // A mobile number is ten digits, and the keyboard, the
                    // length and the pattern say so before the server does.
                    // form-scripts keeps it to digits and cleans a pasted
                    // "+91 98765 43210"; the server checks again regardless.
                    $isMobile = $field->field_type === \App\Support\FormFieldType::MOBILE;
                @endphp
                <input class="hmf-control" type="{{ $spec['input'] ?? 'text' }}"
                       id="{{ $id }}" name="{{ $key }}" value="{{ $value }}"
                       placeholder="{{ $field->placeholder ?: ($isMobile ? 'e.g. 9876543210' : '') }}"
                       @if ($isMobile)
                           inputmode="numeric" maxlength="10" pattern="[0-9]{10}" autocomplete="tel-national"
                           title="Enter a 10-digit mobile number" data-hmf-mobile
                       @endif
                       @if ($field->rule('min_value') !== null) min="{{ $field->rule('min_value') }}" @endif
                       @if ($field->rule('max_value') !== null) max="{{ $field->rule('max_value') }}" @endif
                       {{-- The date/time window the admin set. Enforced server
                            side too; these only stop the picker offering a day
                            or an hour that would be refused. --}}
                       @if ($field->rule('min_date')) min="{{ $field->rule('min_date') }}" @endif
                       @if ($field->rule('max_date')) max="{{ $field->rule('max_date') }}" @endif
                       @if ($field->rule('min_time')) min="{{ $field->rule('min_time') }}" @endif
                       @if ($field->rule('max_time')) max="{{ $field->rule('max_time') }}" @endif
                       @if ($field->rule('max_length') && ! $isMobile) maxlength="{{ $field->rule('max_length') }}" @endif
                       @if ($clientRequired) required @endif
                       @if ($invalid) aria-invalid="true" @endif
                       @if ($help) aria-describedby="{{ $id }}_help" @endif>
        @endswitch

        @if ($help && $field->control() !== 'file')
            <p class="hmf-help" id="{{ $id }}_help">{{ $help }}</p>
        @endif

        {{-- Server-side errors. The browser's own `required` catches the easy
             ones first, but it is never what the submission is judged on. --}}
        @foreach ($messages as $message)
            <p class="hmf-error">{{ $message }}</p>
        @endforeach
    </div>
@endforeach
