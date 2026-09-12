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

        function currentValue(key) {
            var inputs = form.querySelectorAll('[name="' + key + '"], [name="' + key + '[]"]');
            var values = [];

            Array.prototype.forEach.call(inputs, function (input) {
                if (input.type === 'checkbox' || input.type === 'radio') {
                    if (input.checked) values.push(input.value);
                } else if (input.value !== '') {
                    values.push(input.value);
                }
            });

            return values;
        }

        function refresh() {
            conditional.forEach(function (field) {
                var values  = currentValue(field.getAttribute('data-cond-field')),
                    wanted  = field.getAttribute('data-cond-value'),
                    matches = values.indexOf(wanted) !== -1,
                    show    = field.getAttribute('data-cond-op') === 'not_equals' ? !matches : matches;

                field.hidden = !show;

                // A hidden control must not block submission on its own
                // `required`, and must not post a value the server would
                // then have to ignore.
                Array.prototype.forEach.call(field.querySelectorAll('input, select, textarea'), function (input) {
                    input.disabled = !show;
                });
            });
        }

        form.addEventListener('change', refresh);
        form.addEventListener('input', refresh);
        refresh();
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

        /* The browser's own validation, one step at a time. Without this a
           visitor fills in four pages, presses Submit, and is told that
           something on page one is wrong. */
        function complete(step) {
            var controls = step.querySelectorAll('input, select, textarea');

            for (var i = 0; i < controls.length; i++) {
                var control = controls[i];

                // A conditional field that is not currently shown is disabled by
                // the script above, and is not this visitor's to answer.
                if (control.disabled || control.type === 'hidden') continue;

                if (!control.checkValidity()) {
                    control.reportValidity();

                    return false;
                }
            }

            return true;
        }

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
        // visitor is only part-way through.
        form.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' || at === steps.length - 1) return;
            if (e.target.tagName === 'TEXTAREA' || e.target.type === 'submit') return;

            e.preventDefault();
            next.click();
        });

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
</script>
