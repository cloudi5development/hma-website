{{--
|--------------------------------------------------------------------------
| Testimonials — shared section
|--------------------------------------------------------------------------
|
| Used by the home page and the about page:
|   @include('frontend.partials.testimonials')
|
| Push its stylesheet from the page:
|   <link rel="stylesheet" href="{{ asset('assets/css/frontend/testimonials.css') }}?v={{ filemtime(public_path('assets/css/frontend/testimonials.css')) }}">
|
| Its JS is pushed by this partial itself, so the component is self-contained.
--}}
    @php
        // Fed by AppServiceProvider's view composer. Mapped to the exact array
        // shape the markup + JSON payload already expect (img/name/role/review),
        // so both the server render and the JS stage behave identically. The six
        // review photos still repeat because the seeded rows reuse customer-1..6.
        $pool = collect($testimonials ?? [])->map(fn ($t) => [
            'img'    => $t->photo_url,
            'name'   => $t->name,
            'role'   => $t->role,
            'review' => $t->review,
        ])->values()->all();
    @endphp
    @if (count($pool))
    <section class="hm-tst" id="testimonials" data-io aria-labelledby="hmTstTitle">

        {{-- Same slow-rotating hero background (shows faintly through the panel) --}}
        <div class="hm-tst__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/Hero-section/hero-bg.webp') }}" alt="" role="presentation" loading="lazy">
        </div>

        <div class="container">
            <div class="hm-tst__panel">

                {{-- Decorative striped / dotted graphics --}}
                <span class="hm-tst__deco hm-tst__deco--stripe-tl" aria-hidden="true"></span>
                <span class="hm-tst__deco hm-tst__deco--dots-tr"  aria-hidden="true"></span>
                <span class="hm-tst__deco hm-tst__deco--dots-bl"  aria-hidden="true"></span>
                <span class="hm-tst__deco hm-tst__deco--stripe-br" aria-hidden="true"></span>
                <span class="hm-tst__deco hm-tst__deco--stripe-c" aria-hidden="true"></span>

                {{-- Header --}}
                <div class="hm-tst__head">
                    <span class="hm-tst__label hm-anim hm-anim--up">
                     <span class="hm-cats__label-icon" aria-hidden="true"></span>
                        <span class="hm-tst__label-text">Testimonials</span>
                    </span>
                    <h2 class="hm-tst__title hm-anim hm-anim--up hm-anim--d1" id="hmTstTitle">Voices of Career Transformation</h2>

                    <div class="hm-tst__badge hm-anim hm-anim--up hm-anim--d2">
                        <span class="hm-tst__badge-avatars">
                            @foreach (array_slice($pool, 0, 4) as $t)
                                <img src="{{ $t['img'] }}" alt="" aria-hidden="true">
                            @endforeach
                        </span>
                        <span class="hm-tst__badge-text">2k+ Learner</span>
                        <span class="hm-tst__badge-sep" aria-hidden="true"></span>
                        <span class="hm-tst__badge-rating"><i class="fa-solid fa-star" aria-hidden="true"></i> 4.8/5</span>
                        <span class="hm-tst__badge-sep" aria-hidden="true"></span>
                        <span class="hm-tst__badge-rating"><img class="hm-tst__badge-google" src="{{ asset('assets/images/Hero-section/google.png') }}" alt="Google"> 4.8/5</span>
                    </div>
                </div>

                {{-- Stage: 9 scattered profiles (positions in CSS) + the pop-up review card --}}
                <div class="hm-tst__stage" id="hmTstStage">
                    @for ($i = 0; $i < 9; $i++)
                        @php $t = $pool[$i % count($pool)]; @endphp
                        <button type="button"
                                class="hm-tst__profile hm-tst__profile--{{ $i + 1 }} hm-anim"
                                data-slot="{{ $i }}"
                                data-name="{{ $t['name'] }}"
                                data-role="{{ $t['role'] }}"
                                data-review="{{ $t['review'] }}"
                                aria-label="Show review from {{ $t['name'] }}">
                            <span class="hm-tst__profile-img">
                                <img src="{{ $t['img'] }}" alt="{{ $t['name'] }}" loading="lazy">
                            </span>
                        </button>
                    @endfor

                    {{-- Review card — pops up next to the active / hovered profile --}}
                    <div class="hm-tst__card-wrap" id="hmTstCardWrap">
                        <div class="hm-tst__card is-swap" id="hmTstCard">
                            <div class="hm-tst__card-top">
                                <img class="hm-tst__card-avatar" id="hmTstAvatar" src="{{ $pool[0]['img'] }}" alt="{{ $pool[0]['name'] }}">
                                <div>
                                    <div class="hm-tst__card-name" id="hmTstName">{{ $pool[0]['name'] }}</div>
                                    <div class="hm-tst__card-role" id="hmTstRole">{{ $pool[0]['role'] }}</div>
                                </div>
                            </div>
                            <p class="hm-tst__card-text" id="hmTstText">{{ $pool[0]['review'] }}</p>
                            <div class="hm-tst__card-stars" aria-label="Rated 5 out of 5">
                                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Full pool the arrows page through (data only) --}}
                    <script type="application/json" id="hmTstData">@json($pool)</script>
                </div>

                {{-- Navigation --}}
                <div class="hm-tst__nav hm-anim hm-anim--up hm-anim--d3">
                    <button type="button" class="hm-tst__navbtn hm-tst__navbtn--prev" id="hmTstPrev" aria-label="Previous review">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="hm-tst__navbtn hm-tst__navbtn--next" id="hmTstNext" aria-label="Next review">
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>
    @endif

@push('scripts')
    {{-- Testimonials: hover a profile → review card pops up beside it;
         arrows page 9 new people in from the pool --}}
    <script>
        (function () {
            'use strict';
            var stage = document.getElementById('hmTstStage');
            var cardWrap = document.getElementById('hmTstCardWrap');
            var card = document.getElementById('hmTstCard');
            if (!stage || !cardWrap || !card) return;

            var pool = [];
            var dataEl = document.getElementById('hmTstData');
            try { pool = JSON.parse(dataEl.textContent); } catch (e) { pool = []; }

            var slots = Array.prototype.slice.call(stage.querySelectorAll('.hm-tst__profile'));
            var avatar = document.getElementById('hmTstAvatar');
            var nameEl = document.getElementById('hmTstName');
            var roleEl = document.getElementById('hmTstRole');
            var textEl = document.getElementById('hmTstText');
            var prevBtn = document.getElementById('hmTstPrev');
            var nextBtn = document.getElementById('hmTstNext');
            var offset = 0, active = 0;

            function poolAt(i) { return pool.length ? pool[(offset + i) % pool.length] : null; }

            // Repopulate the 9 slots from the current pool window (used by the arrows)
            function renderSlots() {
                slots.forEach(function (btn, i) {
                    var t = poolAt(i);
                    if (!t) return;
                    var img = btn.querySelector('img');
                    if (img) { img.src = t.img; img.alt = t.name; }
                    btn.dataset.name = t.name;
                    btn.dataset.role = t.role;
                    btn.dataset.review = t.review;
                    btn.setAttribute('aria-label', 'Show review from ' + t.name);
                });
            }

            // Position the card right next to the given profile (clamped to the stage)
            function placeCard(btn) {
                var sw = stage.clientWidth, sh = stage.clientHeight;
                var cw = cardWrap.offsetWidth, ch = cardWrap.offsetHeight;
                var left = btn.offsetLeft + btn.offsetWidth / 2 - cw * 0.16;
                var top = btn.offsetTop + btn.offsetHeight * 0.55;
                left = Math.max(0, Math.min(left, Math.max(0, sw - cw)));
                top = Math.max(0, Math.min(top, Math.max(0, sh - ch)));
                cardWrap.style.left = left + 'px';
                cardWrap.style.top = top + 'px';
            }

            function activate(i) {
                var btn = slots[i];
                if (!btn) return;
                active = i;
                var img = btn.querySelector('img');
                card.classList.remove('is-swap'); void card.offsetWidth; card.classList.add('is-swap');
                if (img && avatar) { avatar.src = img.src; avatar.alt = btn.dataset.name || ''; }
                if (nameEl) nameEl.textContent = btn.dataset.name || '';
                if (roleEl) roleEl.textContent = btn.dataset.role || '';
                if (textEl) textEl.textContent = btn.dataset.review || '';
                slots.forEach(function (b, idx) { b.classList.toggle('is-active', idx === i); });
                placeCard(btn);
                cardWrap.classList.add('is-shown');
            }

            function paginate(dir) {
                if (!pool.length) return;
                offset = ((offset + dir * slots.length) % pool.length + pool.length) % pool.length;
                stage.classList.add('is-paging');
                renderSlots();
                activate(0);
                setTimeout(function () { stage.classList.remove('is-paging'); }, 70);
            }

            slots.forEach(function (btn, i) {
                btn.addEventListener('mouseenter', function () { activate(i); });
                btn.addEventListener('focus', function () { activate(i); });
                btn.addEventListener('click', function () { activate(i); });
            });
            if (nextBtn) nextBtn.addEventListener('click', function () { paginate(1); });
            if (prevBtn) prevBtn.addEventListener('click', function () { paginate(-1); });
            window.addEventListener('resize', function () { if (slots[active]) placeCard(slots[active]); });

            renderSlots();
            activate(0);
        })();
    </script>
@endpush
