{{--
| "Register for the Event" — opened by every Register button on the details page.
|
| Posts to frontend.event-registration.store over fetch, so the page never
| reloads; the response is JSON and the note under the button reports it. The
| markup mirrors the course-details enquiry modal (data-hm-field / data-hm-error
| and the same validation shape) so both modals behave identically.
|
| $event is the event being registered for; $eventOptions is every published
| event, so a visitor can switch to another one without leaving the page.
--}}
{{-- NOT modal-dialog-scrollable: that class only scrolls a .modal-body, and this
     dialog has none — it clipped the form at the fold with no way to reach the
     rest. The scrolling is done here instead, on .hm-reg__scroll, which keeps
     the title and the close button pinned while the fields move. --}}
<div class="modal fade hm-reg" id="hmRegisterModal" tabindex="-1"
     aria-labelledby="hmRegisterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content hm-reg__content">

            <button class="hm-reg__close" type="button" data-bs-dismiss="modal" aria-label="Close">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>

            <h2 class="hm-reg__title" id="hmRegisterTitle">Register for the Event</h2>

            <div class="hm-reg__scroll">

            {{-- The event being registered for, echoed back so the visitor can see
                 what they are signing up to without scrolling behind the modal. --}}
            <div class="hm-reg__event">
                <img class="hm-reg__event-img" src="{{ $event->thumbnail_url }}"
                     alt="" role="presentation" loading="lazy" decoding="async">

                <div class="hm-reg__event-body">
                    <h3 class="hm-reg__event-title">{{ $event->title }}</h3>

                    <ul class="hm-reg__event-meta">
                        @if ($event->formatted_date)
                            <li>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="4.5" width="18" height="16" rx="2.5"/><path d="M3 9.5h18M8 3v3M16 3v3"/>
                                </svg>
                                <span>{{ $event->formatted_date }}</span>
                            </li>
                        @endif
                        @if ($event->time_range)
                            <li>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 1.8"/>
                                </svg>
                                <span>{{ $event->time_range }}</span>
                            </li>
                        @endif
                        @if ($event->full_address)
                            <li>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/>
                                </svg>
                                <span>{{ $event->full_address }}</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

            <form class="hm-reg__form" id="hmRegisterForm" method="POST"
                  action="{{ route('frontend.event-registration.store') }}" novalidate>
                @csrf

                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="hm-reg__field" data-hm-field>
                            <label class="hm-reg__label" for="regName">Full Name</label>
                            <input class="hm-reg__input" id="regName" name="name" type="text"
                                   placeholder="Alex Johnson" autocomplete="name" required>
                            <p class="hm-reg__error" data-hm-error>Please enter your full name.</p>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="hm-reg__field" data-hm-field>
                            <label class="hm-reg__label" for="regPhone">Phone Number</label>
                            <div class="hm-reg__phone">
                                <span class="hm-reg__phone-code">+91</span>
                                <input class="hm-reg__input" id="regPhone" name="phone" type="tel"
                                       placeholder="Mobile Number" inputmode="numeric"
                                       pattern="[0-9]{10}" autocomplete="tel" required>
                            </div>
                            <p class="hm-reg__error" data-hm-error>Enter a valid 10-digit mobile number.</p>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="hm-reg__field" data-hm-field>
                            <label class="hm-reg__label" for="regEmail">Email</label>
                            <input class="hm-reg__input" id="regEmail" name="email" type="email"
                                   placeholder="example@gmail.com" autocomplete="email" required>
                            <p class="hm-reg__error" data-hm-error>Please enter a valid email address.</p>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="hm-reg__field" data-hm-field>
                            <label class="hm-reg__label" for="regEvent">Select Event</label>
                            <select class="hm-reg__input hm-reg__select" id="regEvent" name="event_id" required>
                                @foreach ($eventOptions as $option)
                                    <option value="{{ $option->id }}" {{ $option->id === $event->id ? 'selected' : '' }}>
                                        {{ $option->title }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="hm-reg__error" data-hm-error>Please choose an event.</p>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="hm-reg__field" data-hm-field>
                            <label class="hm-reg__label" for="regCity">City</label>
                            <input class="hm-reg__input" id="regCity" name="city" type="text"
                                   placeholder="Enter your city" autocomplete="address-level2">
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="hm-reg__field" data-hm-field>
                            <label class="hm-reg__label" for="regStatus">Professional Status</label>
                            <select class="hm-reg__input hm-reg__select" id="regStatus" name="professional_status">
                                <option value="" selected>Select your status</option>
                                @foreach (\App\Models\EventRegistration::PROFESSIONAL_STATUSES as $option)
                                    <option>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="hm-reg__field" data-hm-field>
                            <label class="hm-reg__label" for="regOrg">Company / College name</label>
                            <input class="hm-reg__input" id="regOrg" name="organisation" type="text"
                                   placeholder="Enter your company or college name" autocomplete="organization">
                        </div>
                    </div>
                </div>

                <div class="hm-reg__field hm-reg__field--terms" data-hm-field>
                    <label class="hm-reg__terms">
                        <input type="checkbox" id="regTerms" name="agreed_terms" value="1" required>
                        {{-- Opens the Content Management page in a new tab, so a
                             half-filled form is not lost to a navigation. Falls
                             back to plain text if the page is switched off. --}}
                        @php $terms = \App\Models\ContentPage::active()->where('key', 'terms-conditions')->first(); @endphp
                        <span>
                            I agree to the
                            @if ($terms)
                                <a href="{{ $terms->url }}" target="_blank" rel="noopener">{{ $terms->title }}</a>
                            @else
                                Terms &amp; Conditions
                            @endif
                        </span>
                    </label>
                    <p class="hm-reg__error" data-hm-error>Please accept the Terms &amp; Conditions.</p>
                </div>

                <button class="hm-reg__submit" type="submit">
                    <span>Register Now</span>
                </button>

                <p class="hm-reg__note" id="hmRegisterNote" role="status" hidden>
                    Thanks! Your registration has been received — check your inbox for the confirmation.
                </p>
            </form>

            </div>{{-- /.hm-reg__scroll --}}

        </div>
    </div>
</div>
