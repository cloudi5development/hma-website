{{-- ============================ ADMIN FEEDBACK ============================
     Two shared pieces, included once by the admin layout:

     1. Toasts — every flash message from a controller (`->with('success', …)`)
        pops in as a branded toast instead of an inline banner. Anything can
        raise one at runtime with hmToast('Saved.', 'success').

     2. Confirm dialog — replaces the browser's confirm() on destructive
        actions. Any form or link carrying data-confirm="…" is intercepted.
======================================================================== --}}

<div class="hm-toasts" id="hmToasts" role="status" aria-live="polite" aria-atomic="false"></div>

<div class="hm-dialog" id="hmConfirm" role="dialog" aria-modal="true" aria-labelledby="hmConfirmTitle">
    <div class="hm-dialog__panel">
        <span class="hm-dialog__icon" id="hmConfirmIcon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                <path d="M10 11v6M14 11v6"/>
            </svg>
        </span>
        <h2 class="hm-dialog__title" id="hmConfirmTitle">Are you sure?</h2>
        <p class="hm-dialog__text" id="hmConfirmText">This action cannot be undone.</p>
        <div class="hm-dialog__actions">
            <button type="button" class="btn-ghost" id="hmConfirmCancel">Cancel</button>
            <button type="button" class="btn-danger-solid" id="hmConfirmOk">Delete</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    /* ====================== TOASTS ====================== */

    var stack = document.getElementById('hmToasts');

    var ICONS = {
        success: '<path d="M20 6 9 17l-5-5"/>',
        error:   '<circle cx="12" cy="12" r="10"/><path d="M12 7v6M12 17h.01"/>',
        warning: '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
        info:    '<circle cx="12" cy="12" r="10"/><path d="M12 16v-5M12 8h.01"/>'
    };

    var TITLES = { success: 'Success', error: 'Something went wrong', warning: 'Heads up', info: 'Note' };

    /**
     * Show a toast. `type` is success | error | warning | info.
     * Returns the element so callers can dismiss it early if they want.
     */
    window.hmToast = function (message, type, title) {
        if (!stack || !message) return null;
        type = ICONS[type] ? type : 'info';

        var life = type === 'error' ? 7000 : 4500;

        var el = document.createElement('div');
        el.className = 'hm-toast hm-toast--' + type;
        el.setAttribute('role', type === 'error' ? 'alert' : 'status');

        var icon = document.createElement('span');
        icon.className = 'hm-toast__icon';
        icon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">' + ICONS[type] + '</svg>';

        var body = document.createElement('div');
        body.className = 'hm-toast__body';
        var h = document.createElement('p');
        h.className = 'hm-toast__title';
        h.textContent = title || TITLES[type];
        var p = document.createElement('p');
        p.className = 'hm-toast__text';
        p.textContent = message;
        body.appendChild(h);
        body.appendChild(p);

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'hm-toast__close';
        close.setAttribute('aria-label', 'Dismiss');
        close.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>';

        var timer = document.createElement('span');
        timer.className = 'hm-toast__timer';

        el.appendChild(icon);
        el.appendChild(body);
        el.appendChild(close);
        el.appendChild(timer);
        stack.appendChild(el);

        // Next frame, so the transition has a start value to animate from.
        requestAnimationFrame(function () { el.classList.add('is-in'); });

        // Countdown rail; pauses while the pointer is over the toast.
        timer.animate
            ? (function () {
                  var anim = timer.animate(
                      [{ transform: 'scaleX(1)' }, { transform: 'scaleX(0)' }],
                      { duration: life, easing: 'linear', fill: 'forwards' }
                  );
                  anim.onfinish = dismiss;
                  el.addEventListener('mouseenter', function () { anim.pause(); });
                  el.addEventListener('mouseleave', function () { anim.play(); });
              })()
            : setTimeout(dismiss, life);

        function dismiss() {
            if (!el.parentNode) return;
            el.classList.add('is-out');
            setTimeout(function () { if (el.parentNode) el.remove(); }, 300);
        }

        close.addEventListener('click', dismiss);

        return el;
    };

    /* Flash messages handed over by the server on this request. */
    @if (session('success'))
        hmToast(@json(session('success')), 'success');
    @endif
    @if (session('error'))
        hmToast(@json(session('error')), 'error');
    @endif
    @if (session('warning'))
        hmToast(@json(session('warning')), 'warning');
    @endif
    @if (session('info'))
        hmToast(@json(session('info')), 'info');
    @endif
    @if ($errors->any())
        hmToast(
            @json($errors->count() === 1 ? $errors->first() : $errors->count() . ' fields need attention. Check the highlighted inputs.'),
            'error',
            'Could not save'
        );
    @endif

    /* ====================== CONFIRM DIALOG ====================== */

    var dialog = document.getElementById('hmConfirm');
    if (!dialog) return;

    var titleEl  = document.getElementById('hmConfirmTitle');
    var textEl   = document.getElementById('hmConfirmText');
    var iconEl   = document.getElementById('hmConfirmIcon');
    var okBtn    = document.getElementById('hmConfirmOk');
    var cancelBtn= document.getElementById('hmConfirmCancel');
    var pending  = null;      // the form / link waiting on an answer
    var lastFocus = null;

    // Glyph per kind of confirmation — set with data-confirm-icon on the trigger.
    var DIALOG_ICONS = {
        'delete': '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/>',
        'logout': '<path d="M15 17l5-5-5-5"/><path d="M20 12H9M12 20H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h6"/>',
        'warning': '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
        'question': '<circle cx="12" cy="12" r="9"/><path d="M9.6 9.2a2.5 2.5 0 0 1 4.8.8c0 1.7-2.4 2.3-2.4 3.5M12 17h.01"/>'
    };

    function open(message, opts) {
        var kind = DIALOG_ICONS[opts.icon] ? opts.icon : 'delete';
        var brand = opts.tone === 'brand';

        iconEl.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">' + DIALOG_ICONS[kind] + '</svg>';
        iconEl.className = 'hm-dialog__icon' + (brand ? ' hm-dialog__icon--brand' : '');

        titleEl.textContent = opts.title || 'Are you sure?';
        textEl.textContent  = message;
        okBtn.textContent   = opts.confirmLabel || 'Delete';
        okBtn.className     = brand ? 'btn-brand' : 'btn-danger-solid';

        lastFocus = document.activeElement;
        dialog.classList.add('is-open');
        requestAnimationFrame(function () { dialog.classList.add('is-visible'); });
        okBtn.focus();
    }

    function close() {
        dialog.classList.remove('is-visible');
        setTimeout(function () { dialog.classList.remove('is-open'); }, 200);
        pending = null;
        if (lastFocus) lastFocus.focus();
    }

    // Intercept anything asking for confirmation.
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-confirm]');
        if (!trigger) return;

        // Buttons inside a form confirm the form; links confirm the navigation.
        var target = trigger.tagName === 'FORM' ? trigger : (trigger.closest('form[data-confirm]') || trigger);
        if (target.dataset.confirmed === '1') return;   // already answered

        e.preventDefault();
        pending = target;
        open(target.dataset.confirm, {
            title:        target.dataset.confirmTitle,
            confirmLabel: target.dataset.confirmLabel,
            tone:         target.dataset.confirmTone,
            icon:         target.dataset.confirmIcon
        });
    });

    okBtn.addEventListener('click', function () {
        var target = pending;
        close();
        if (!target) return;

        target.dataset.confirmed = '1';
        if (target.tagName === 'FORM') {
            target.submit();
        } else if (target.tagName === 'A') {
            window.location.href = target.href;
        } else {
            target.click();
        }
    });

    cancelBtn.addEventListener('click', close);

    dialog.addEventListener('click', function (e) {
        if (e.target === dialog) close();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && dialog.classList.contains('is-open')) close();
    });
})();
</script>
@endpush
