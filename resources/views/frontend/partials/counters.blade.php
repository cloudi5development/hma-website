{{--
|--------------------------------------------------------------------------
| Statistics counters — shared section
|--------------------------------------------------------------------------
|
| Used by the home page (inside the About section) and the about page.
| Renders a Bootstrap row, so include it inside a .container:
|
|   @include('frontend.partials.counters')
|
| and push its stylesheet from the page:
|
|   <link rel="stylesheet" href="{{ asset('assets/css/frontend/counters.css') }}">
|
| The count-up + reveal JS is pushed by this partial itself (see the bottom),
| so the component is self-contained wherever it is dropped in.
--}}
@php
    $counters = [
        ['target' => '2.5', 'decimals' => 1, 'suffix' => 'K+', 'label' => 'Students Trained'],
        ['target' => '150', 'decimals' => 0, 'suffix' => '+',  'label' => 'Industry-Focused Courses'],
        ['target' => '95',  'decimals' => 0, 'suffix' => '%',  'label' => 'Learner Satisfaction'],
        ['target' => '50',  'decimals' => 0, 'suffix' => '+',  'label' => 'Hiring & Training Partners'],
    ];
@endphp

<div class="row hm-counters" role="list" data-hm-counters>
    @foreach ($counters as $i => $counter)
        <div class="col-6 col-lg-3 hm-stat hm-stat--d{{ $i + 1 }}" role="listitem">
            {{-- The rendered value is the final figure, so it still reads correctly
                 with JS off; the counter animates up to it from 0. --}}
            <div class="hm-stat__num"
                 data-target="{{ $counter['target'] }}"
                 data-decimals="{{ $counter['decimals'] }}"
                 data-suffix="{{ $counter['suffix'] }}">{{ $counter['target'] }}{{ $counter['suffix'] }}</div>
            <div class="hm-stat__label">{{ $counter['label'] }}</div>
        </div>
    @endforeach
</div>

@push('scripts')
    {{-- Counters — reveal + one-time count-up, runs once then holds the value --}}
    <script>
        (function () {
            'use strict';

            var root = document.querySelector('[data-hm-counters]');
            if (!root) return;

            var nums   = root.querySelectorAll('.hm-stat__num');
            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var hasIO  = 'IntersectionObserver' in window;

            function format(value, decimals, suffix) {
                return value.toFixed(decimals) + suffix;
            }

            function run() {
                root.classList.add('is-in');

                nums.forEach(function (el) {
                    var target   = parseFloat(el.getAttribute('data-target')) || 0;
                    var decimals = parseInt(el.getAttribute('data-decimals') || '0', 10);
                    var suffix   = el.getAttribute('data-suffix') || '';

                    if (reduce) { el.textContent = format(target, decimals, suffix); return; }

                    var duration = 1800, startTime = null;
                    function tick(now) {
                        if (startTime === null) startTime = now;
                        var p = Math.min((now - startTime) / duration, 1);
                        var eased = 1 - Math.pow(1 - p, 3);            // easeOutCubic
                        el.textContent = format(target * eased, decimals, suffix);
                        if (p < 1) requestAnimationFrame(tick);
                        else el.textContent = format(target, decimals, suffix);  // snap to exact
                    }
                    requestAnimationFrame(tick);
                });
            }

            if (reduce || !hasIO) { run(); return; }

            new IntersectionObserver(function (entries, obs) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) { run(); obs.disconnect(); }   // once only
                });
            }, { threshold: 0.4 }).observe(root);
        })();
    </script>
@endpush
