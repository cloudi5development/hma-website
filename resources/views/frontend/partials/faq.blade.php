{{--
|--------------------------------------------------------------------------
| FAQ — shared section
|--------------------------------------------------------------------------
|
| Used by the home page and the about page:
|   @include('frontend.partials.faq')
|
| Push its stylesheet from the page:
|   <link rel="stylesheet" href="{{ asset('assets/css/frontend/faq.css') }}">
|
| NOTE: the accordion uses Bootstrap's collapse (data-bs-toggle), so the page
| MUST also load the Bootstrap JS bundle.
--}}
    @php
        $faqs = [
            ['q' => 'What courses does Hire Minds Academy offer?', 'a' => 'Our courses span in-demand fields like software development, data & AI, cloud & DevOps, cyber security, and professional skills. Every program is designed by industry experts and blends practical learning, live projects, interview preparation, and dedicated placement support so you graduate genuinely job-ready.'],
            ['q' => 'How long are the training programs?', 'a' => 'Most tracks run between three and six months depending on the depth you choose. We offer flexible weekday and weekend batches along with self-paced modules, so you can learn effectively whether you are a student, a working professional, or switching careers.'],
            ['q' => 'Will I receive placement assistance?', 'a' => 'Yes. Every learner gets end-to-end placement support including resume building, mock interviews, portfolio reviews, and direct referrals to our hiring partners. Our career team stays with you from your very first module until you sign your offer letter.'],
            ['q' => 'Do I receive a course certificate?', 'a' => 'Absolutely. On successful completion of your program and final projects, you receive an industry-recognized certificate from Hire Minds Academy that you can add to your resume and LinkedIn to showcase your verified, job-ready skills to recruiters.'],
            ['q' => 'Can beginners join these courses?', 'a' => 'Definitely. Our programs are structured to take complete beginners from the fundamentals all the way to advanced, real-world skills. With mentor support, hands-on labs, and a friendly community, no prior experience is required to get started.'],
        ];
    @endphp
    <section class="hm-faq" id="faq" data-io aria-labelledby="hmFaqTitle">

        {{-- Same slow-rotating hero background + soft warm overlay --}}
        <div class="hm-faq__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/Hero-section/hero-bg.webp') }}" alt="" role="presentation" loading="lazy">
        </div>
        <div class="hm-faq__overlay" aria-hidden="true"></div>

        <div class="container hm-faq__container">
            <div class="row align-items-center g-5">

                {{-- Left: heading + description + illustration --}}
                <div class="col-lg-5 hm-faq__left hm-anim hm-anim--left">
                    <span class="hm-faq__label">
                        <span class="hm-faq__label-icon" aria-hidden="true"></span>
                        <span class="hm-faq__label-text">FAQ</span>
                    </span>
                    <h2 class="hm-faq__title" id="hmFaqTitle">Frequently Asking Questions</h2>
                    <p class="hm-faq__desc">
                        Gain practical skills, learn from industry experts, and receive career guidance
                        that prepares you for real-world opportunities.
                    </p>

                    <div class="hm-faq__visual">
                        <img class="hm-faq__deco hm-faq__deco--star1" src="{{ asset('assets/images/faq/star.png') }}" alt="" aria-hidden="true" loading="lazy">
                        <img class="hm-faq__deco hm-faq__deco--star2" src="{{ asset('assets/images/faq/star.png') }}" alt="" aria-hidden="true" loading="lazy">
                        <img class="hm-faq__deco hm-faq__deco--dots" src="{{ asset('assets/images/faq/component.png') }}" alt="" aria-hidden="true" loading="lazy">
                        <span class="hm-faq__deco hm-faq__deco--stripe" aria-hidden="true"></span>
                        <img class="hm-faq__img" src="{{ asset('assets/images/faq/faq-img.webp') }}"
                             alt="A learner considering the Hire Minds Academy programs" loading="lazy">
                    </div>
                </div>

                {{-- Right: Bootstrap accordion --}}
                <div class="col-lg-7 hm-faq__right">
                    <div class="accordion hm-faq__accordion" id="hmFaqAccordion">
                        @foreach ($faqs as $i => $faq)
                            @php $open = $i === 2; @endphp
                            <div class="accordion-item hm-faq__item hm-anim hm-anim--up hm-anim--d{{ $i + 1 }}">
                                <h3 class="accordion-header">
                                    <button class="accordion-button {{ $open ? '' : 'collapsed' }}" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#hmFaqBody{{ $i }}"
                                            aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="hmFaqBody{{ $i }}">
                                        {{ $faq['q'] }}
                                    </button>
                                </h3>
                                <div id="hmFaqBody{{ $i }}" class="accordion-collapse collapse {{ $open ? 'show' : '' }}"
                                     data-bs-parent="#hmFaqAccordion">
                                    <div class="accordion-body">{{ $faq['a'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
