{{--
| The behaviour of a rendered form: conditional fields, and stepping through a
| multi-page one.
|
| Shared by the public page and the admin preview. Both draw the same markup, so
| both need the same script — and the preview is only worth having if it behaves
| the way the live form does.
--}}
<script>
    /* Conditional visibility.
       A field carrying data-cond-* is shown only while the field it names
       holds the value it names. Every such field starts `hidden` in the
       markup, so the rule holds even before this runs and a field is never
       flashed on screen before being taken away.

       This is presentation only: FormField::isVisibleFor() applies the same
       rule on the server, so a hidden field is not held to its "required"
       rule and a forged value for one is not stored. */
    (function () {
        'use strict';

        var form = document.querySelector('.hmf__form');
        if (!form) return;

        var conditional = Array.prototype.slice.call(form.querySelectorAll('[data-cond-field]'));
        if (!conditional.length) return;

        var quote = window.CSS && CSS.escape ? CSS.escape : function (s) { return String(s).replace(/["\\]/g, '\\$&'); };

        // Every answer the controlling question will POST — so only enabled
        // controls (a disabled one is not sent, and the server judges on what
        // is sent), and every input of a grid ("key[0]", "key[0][]") as well
        // as a plain or multi-choice one.
        function currentValue(key) {
            var k      = quote(key),
                inputs = form.querySelectorAll('[name="' + k + '"], [name^="' + k + '["]'),
                values = [];

            Array.prototype.forEach.call(inputs, function (input) {
                if (input.disabled) return;

                if (input.type === 'checkbox' || input.type === 'radio') {
                    if (input.checked) values.push(input.value);
                } else if (input.value !== '') {
                    values.push(input.value);
                }
            });

            return values;
        }

        function apply(field) {
            var values  = currentValue(field.getAttribute('data-cond-field')),
                wanted  = field.getAttribute('data-cond-value'),
                matches = values.indexOf(wanted) !== -1,
                show    = field.getAttribute('data-cond-op') === 'not_equals' ? !matches : matches,
                changed = field.hidden === show;

            field.hidden = !show;

            // A hidden control must not block submission on its own
            // `required`, and must not post a value the server would
            // then have to ignore.
            Array.prototype.forEach.call(field.querySelectorAll('input, select, textarea'), function (input) {
                input.disabled = !show;
            });

            return changed;
        }

        // Repeated until nothing changes: when C depends on B and B on A,
        // hiding B must go on to hide C in the same pass, whatever order the
        // three sit in on the page. Bounded, so a loop of conditions cannot
        // spin forever.
        function refresh() {
            for (var pass = 0; pass <= conditional.length; pass++) {
                var changed = false;
                conditional.forEach(function (field) { if (apply(field)) changed = true; });
                if (!changed) break;
            }
        }

        form.addEventListener('change', refresh);
        form.addEventListener('input', refresh);
        refresh();
    })();

    /* Is every required question answered?
       ---------------------------------------------------------------------
       The browser's own `required` covers a text box, a drop-down, a radio
       group and a file — but not "at least one of these checkboxes", not
       "every row of this grid", and not a question that only appears through
       a condition (it carries no `required`, or it would block the form while
       hidden). Next used to open the following page with any of those left
       empty. This checks them all, the same way, for:

         - Next, on a multi-page form: the page must be complete first;
         - Submit, on any form: whatever the browser cannot see.

       A question hidden by its condition is never checked. The server checks
       everything again regardless (FormSubmissionService::validator). */
    (function () {
        'use strict';

        var form = document.querySelector('.hmf__form');
        if (!form) return;

        function live(field) {
            return Array.prototype.filter.call(field.querySelectorAll('input, select, textarea'), function (input) {
                return !input.disabled && input.type !== 'hidden';
            });
        }

        // What a required question is missing, or null.
        function missing(field) {
            if (field.hidden || !field.hasAttribute('data-hmf-required')) return null;

            var inputs = live(field);
            if (!inputs.length) return null;

            switch (field.getAttribute('data-hmf-control')) {
                case 'mc_grid':
                case 'tick_grid':
                    var rows = field.querySelectorAll('tbody tr');
                    for (var r = 0; r < rows.length; r++) {
                        if (!rows[r].querySelector('input:checked')) {
                            return { el: rows[r].querySelector('input'), message: 'Please answer every row.' };
                        }
                    }
                    return null;
                case 'checkbox':
                    return field.querySelector('input:checked') ? null : { el: inputs[0], message: 'Please choose at least one option.' };
                case 'radio':
                case 'scale':
                case 'rating':
                    return field.querySelector('input:checked') ? null : { el: inputs[0], message: 'Please choose an option.' };
                case 'file':
                    return inputs[0].files && inputs[0].files.length ? null : { el: inputs[0], message: 'Please choose a file.' };
                default:
                    return inputs.some(function (input) { return input.value.trim() !== ''; })
                        ? null
                        : { el: inputs[0], message: 'Please fill in this field.' };
            }
        }

        function clear(field) {
            Array.prototype.forEach.call(field.querySelectorAll('[data-hmf-client-error]'), function (note) {
                note.parentNode.removeChild(note);
            });
            if (!field.querySelector('.hmf-error')) field.classList.remove('is-invalid');
        }

        // Bring the question's page on screen (the stepper listens for this),
        // say what is missing under it, and put the visitor there.
        function point(field, problem) {
            form.dispatchEvent(new CustomEvent('hmf:reveal', { detail: field }));

            if (problem.message) {
                clear(field);
                field.classList.add('is-invalid');

                var note = document.createElement('p');
                note.className = 'hmf-error';
                note.setAttribute('data-hmf-client-error', '');
                note.setAttribute('role', 'alert');
                note.textContent = problem.message;
                field.appendChild(note);

                field.scrollIntoView({ block: 'center', behavior: 'smooth' });
                if (problem.el && problem.el.focus) problem.el.focus({ preventScroll: true });
            } else {
                problem.el.reportValidity();
            }
        }

        /* The first question among these that is not complete — unanswered, or
           answered in a shape its rules refuse (an email, a 10-digit mobile, a
           number range) — is pointed at, and false comes back. */
        function complete(fields) {
            for (var i = 0; i < fields.length; i++) {
                var field = fields[i];
                if (field.hidden) continue;

                var problem = missing(field);
                if (problem) { point(field, problem); return false; }

                var controls = live(field);
                for (var c = 0; c < controls.length; c++) {
                    if (!controls[c].checkValidity()) { point(field, { el: controls[c] }); return false; }
                }
            }

            return true;
        }

        // An answer given clears the note it answered.
        ['input', 'change'].forEach(function (type) {
            form.addEventListener(type, function (e) {
                var field = e.target.closest ? e.target.closest('.hmf-field') : null;
                if (field && field.querySelector('[data-hmf-client-error]') && !missing(field)) clear(field);
            });
        });

        // Submit: the browser has already checked what it can; this is the rest.
        form.addEventListener('submit', function (e) {
            var fields = form.querySelectorAll('.hmf-field');

            for (var i = 0; i < fields.length; i++) {
                var problem = missing(fields[i]);
                if (problem) {
                    e.preventDefault();
                    point(fields[i], problem);
                    return;
                }
            }
        });

        window.hmfComplete = complete;
    })();

    /* Stepping through a multi-page form.
       ---------------------------------------------------------------------
       Every step is already in the page; this only decides which one is on
       screen. That is the whole design: the form posts once, carrying all of
       it, so a step is a way of reading the form and never a checkpoint the
       server knows about. Nothing here can lose an answer given on step one,
       because that answer never left the document.

       With this script absent the form is one long page that still submits —
       see the noscript block in form-structure.blade.php. */
    (function () {
        'use strict';

        var form = document.querySelector('.hmf__form');
        if (!form) return;

        var wrap = form.querySelector('[data-steps].is-paged');
        if (!wrap) return;

        var steps = Array.prototype.slice.call(wrap.querySelectorAll('[data-step]'));
        if (steps.length < 2) return;

        var nav       = form.querySelector('[data-step-nav]'),
            back      = form.querySelector('[data-step-back]'),
            next      = form.querySelector('[data-step-next]'),
            submitRow = form.querySelector('[data-submit-row]'),
            crumbs    = Array.prototype.slice.call(form.querySelectorAll('[data-step-crumb]')),
            segs      = Array.prototype.slice.call(form.querySelectorAll('[data-step-seg]')),
            counter   = form.querySelector('[data-step-now]'),
            at        = 0;

        if (!nav || !back || !next) return;

        function render(scroll) {
            steps.forEach(function (step, i) { step.hidden = i !== at; });

            crumbs.forEach(function (crumb, i) {
                crumb.classList.toggle('is-current', i === at);
                crumb.classList.toggle('is-done', i < at);
            });

            // Every segment up to and including this one is lit.
            segs.forEach(function (seg, i) { seg.classList.toggle('is-on', i <= at); });

            if (counter) counter.textContent = at + 1;

            nav.hidden  = false;
            back.hidden = at === 0;
            next.hidden = at === steps.length - 1;

            // The submit button belongs to the last step only. It starts
            // visible in the markup so that a form with no JavaScript can be
            // sent; from here on it is this script's to place.
            if (submitRow) submitRow.hidden = at !== steps.length - 1;

            var current = crumbs[at];
            if (current && current.scrollIntoView) {
                current.scrollIntoView({ block: 'nearest', inline: 'center' });
            }

            if (scroll) {
                var top  = form.getBoundingClientRect().top + window.pageYOffset - 90,
                    easy = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                window.scrollTo({ top: top < 0 ? 0 : top, behavior: easy ? 'auto' : 'smooth' });
            }
        }

        /* A page must be complete before the next one opens: every required
           question on it answered, every answer in a shape its rules accept.
           Without this a visitor fills in four pages, presses Submit, and is
           told that something on page one is wrong. The checks themselves are
           the ones above (window.hmfComplete), so Next and Submit agree. */
        function complete(step) {
            return window.hmfComplete
                ? window.hmfComplete(step.querySelectorAll('.hmf-field'))
                : true;
        }

        // A question pointed at on another page (from Submit) brings that page
        // on screen first.
        form.addEventListener('hmf:reveal', function (e) {
            var owner = e.detail && e.detail.closest ? e.detail.closest('[data-step]') : null,
                index = steps.indexOf(owner);

            if (index > -1 && index !== at) {
                at = index;
                render(false);
            }
        });

        next.addEventListener('click', function () {
            if (!complete(steps[at])) return;

            at = Math.min(at + 1, steps.length - 1);
            render(true);
        });

        back.addEventListener('click', function () {
            at = Math.max(at - 1, 0);
            render(true);
        });

        // Enter inside a text box should move on rather than submit a form the
        // visitor is only part-way through. Not on a button: Enter on Back is
        // a keyboard user pressing Back.
        form.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' || at === steps.length - 1) return;
            if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'BUTTON' || e.target.type === 'submit') return;

            e.preventDefault();
            next.click();
        });

        /* A problem on a step that is not on screen.
           The browser checks every control when Submit is pressed, and a
           control on a hidden step cannot be focused to show its message — so
           the submission just did not happen, with nothing on screen to say
           why. The usual cause: a required file on step one after the page
           came back with an error from a later step (a file input cannot be
           refilled). Open the step it is on, then let the browser explain. */
        var reopening = false;

        form.addEventListener('invalid', function (e) {
            var owner = e.target.closest ? e.target.closest('[data-step]') : null,
                index = steps.indexOf(owner);

            if (index === -1 || !owner.hidden) return;

            e.preventDefault();
            if (reopening) return;   // the first bad control wins; the rest of this check is quiet

            reopening = true;
            at = index;
            render(true);

            setTimeout(function () {
                reopening = false;
                e.target.reportValidity();
            }, 0);
        }, true);

        /* Coming back from a rejected submission: open the step the problem is
           on rather than step one, which is the difference between a fixable
           error and an invisible one. */
        var failed = wrap.querySelector('.hmf-field.is-invalid');

        if (failed) {
            var owner = failed.closest('[data-step]'),
                index = steps.indexOf(owner);

            if (index > -1) at = index;
        }

        /* Back and Submit belong on the same line.
           The submit button is the page's, written after the steps so a form
           with no JavaScript still has one; the Back/Next bar is this partial's.
           Left apart, the last step drew two separated rows with Back stranded
           alone above Submit. Moved in here they are one action bar on every
           step: Back on the left, and whichever of Next or Submit applies on
           the right.

           Done LAST, immediately before the bar is revealed. The bar starts
           hidden, so anything that threw between moving the submit button into
           it and unhiding it would leave a form that cannot be submitted at
           all — a worse bug than the one being fixed. */
        if (submitRow) nav.appendChild(submitRow);

        render(false);
    })();

    /* Mobile numbers: ten digits.
       Letters, spaces and symbols never get into the box, and maxlength stops
       it at ten digits. A number PASTED in the usual shapes — "+91 98765 43210",
       "91 9876543210", "09876543210" — arrives as its ten digits. A pasted
       number that is still too long is left as it is, NOT cut down to ten: the
       browser then flags it, rather than the form quietly saving a different
       number. The server applies the same 10-digit rule either way. */
    (function () {
        'use strict';

        var inputs = document.querySelectorAll('.hmf__form [data-hmf-mobile]');
        if (!inputs.length) return;

        function fromPaste(text) {
            var digits = String(text).replace(/\D/g, '');

            if (digits.length === 12 && digits.indexOf('91') === 0) return digits.slice(2);
            if (digits.length === 11 && digits.charAt(0) === '0') return digits.slice(1);

            return digits;
        }

        // The browser's own wording for a pattern miss is "Please match the
        // requested format", which tells a visitor nothing. Say what it is.
        function check(input) {
            input.setCustomValidity(input.value && !/^\d{10}$/.test(input.value) ? 'Enter a 10-digit mobile number.' : '');
        }

        Array.prototype.forEach.call(inputs, function (input) {
            // An answer coming back after a refused submission is shown clean.
            if (input.value) input.value = fromPaste(input.value);
            check(input);

            input.addEventListener('input', function () {
                var digits = input.value.replace(/\D/g, '');
                if (digits !== input.value) input.value = digits;
                check(input);
            });

            input.addEventListener('paste', function (e) {
                var text = (e.clipboardData || window.clipboardData);
                text = text ? text.getData('text') : '';
                if (!text) return;

                e.preventDefault();
                input.value = fromPaste(text);
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });
    })();

    /* One submission per press.
       A double-click, or Enter pressed twice, posted the form twice — two
       responses, two notification emails. Once a submission is on its way the
       button says so and further submits are ignored. Restored if the browser
       brings this page back from its cache (the Back button), so the form is
       never left unsendable. */
    (function () {
        'use strict';

        var form = document.querySelector('.hmf__form');
        if (!form) return;

        var sending = false,
            buttons = Array.prototype.slice.call(form.querySelectorAll('button[type="submit"], input[type="submit"]'));

        form.addEventListener('submit', function (e) {
            // Stopped by the completeness check above: nothing is on its way,
            // so the buttons must stay usable.
            if (e.defaultPrevented) return;

            if (sending) {
                e.preventDefault();
                return;
            }

            sending = true;

            // After this event, not during it: a disabled submitter is left out
            // of the posted data.
            setTimeout(function () {
                buttons.forEach(function (button) {
                    button.disabled = true;
                    button.setAttribute('aria-busy', 'true');
                });
            }, 0);
        });

        window.addEventListener('pageshow', function (e) {
            if (!e.persisted) return;

            sending = false;
            buttons.forEach(function (button) {
                button.disabled = false;
                button.removeAttribute('aria-busy');
            });
        });
    })();
</script>
