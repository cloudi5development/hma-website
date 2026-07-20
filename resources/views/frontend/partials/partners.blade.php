{{--
|--------------------------------------------------------------------------
| Our Trusted Partners — shared section
|--------------------------------------------------------------------------
|
| Used by the home page and the about page. Include it with:
|
|   @include('frontend.partials.partners')
|
| and push its stylesheet from the page:
|
|   <link rel="stylesheet" href="{{ asset('assets/css/frontend/partners.css') }}?v={{ filemtime(public_path('assets/css/frontend/partners.css')) }}">
|
--}}
@php
    // Logos live in public/assets/images/partners-section/. Add a row here
    // (e.g. ['img' => 'flipkart.webp', 'name' => 'Flipkart']) once the image
    // exists and it will appear in the marquee automatically.
    $partners = [
        ['img' => 'amason.webp',    'name' => 'Amazon'],
        ['img' => 'google.webp',    'name' => 'Google'],
        ['img' => 'microsoft.webp', 'name' => 'Microsoft'],
        ['img' => 'tech.webp',      'name' => 'Tech'],
    ];
@endphp

<section class="hm-partners" aria-labelledby="hmPartnersTitle">
    <div class="container">
        <h2 class="hm-partners__title" id="hmPartnersTitle">Our Trusted Partners</h2>
    </div>

    {{-- Full-bleed marquee so the logos run to the screen edges. Two identical
         groups + translateX(-50%) = seamless infinite loop. --}}
    <div class="hm-partners__marquee">
        <div class="hm-partners__track">
            @for ($group = 0; $group < 2; $group++)
                <ul class="hm-partners__group" @if ($group === 1) aria-hidden="true" @endif>
                    {{-- Each group repeats the set a few times so it always spans wider
                         than the viewport (no blank gap on large screens). --}}
                    @for ($repeat = 0; $repeat < 3; $repeat++)
                        @foreach ($partners as $partner)
                            <li class="hm-partners__item">
                                <img class="hm-partners__logo"
                                     src="{{ asset('assets/images/partners-section/'.$partner['img']) }}"
                                     alt="{{ $partner['name'] }} Partner Logo"
                                     height="40" loading="lazy" draggable="false">
                            </li>
                        @endforeach
                    @endfor
                </ul>
            @endfor
        </div>
    </div>
</section>
