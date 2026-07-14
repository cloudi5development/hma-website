{{-- Frontend site footer — global, reused on every page via template-base.
     Styles: assets/css/frontend/footer.css · Behaviour: assets/js/frontend/footer.js
     (both loaded site-wide via layouts/common-css and layouts/common-js). --}}
<footer class="hm-footer" id="hmFooter">
    <div class="container">
        <div class="row gy-5">

            {{-- Column 1 — Brand --}}
            <div class="col-lg-3 col-md-6 hm-footer__col hm-footer__brand">
                <a href="{{ route('frontend.index') }}" class="hm-footer__logo-link" aria-label="Hire Minds Academy — home">
                    <img src="{{ asset('assets/images/branding/logo.png') }}" alt="Hire Minds Academy"
                         class="hm-footer__logo" width="160" height="54">
                </a>
                <p class="hm-footer__about">
                    Empowering aspiring professionals with industry-focused Talent Acquisition training,
                    practical learning, and career guidance designed for long-term success.
                </p>
                <div class="hm-footer__socials">
                    <a href="#" class="hm-footer__social" aria-label="Facebook">
                        <i class="fa-brands fa-facebook-f" aria-hidden="true"></i>
                    </a>
                    <a href="#" class="hm-footer__social" aria-label="Instagram">
                        <i class="fa-brands fa-instagram" aria-hidden="true"></i>
                    </a>
                    <a href="#" class="hm-footer__social" aria-label="LinkedIn">
                        <i class="fa-brands fa-linkedin-in" aria-hidden="true"></i>
                    </a>
                    <a href="#" class="hm-footer__social" aria-label="YouTube">
                        <i class="fa-brands fa-youtube" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            {{-- Column 2 — Quick Links --}}
            <div class="col-lg-2 col-md-6 hm-footer__col">
                <h2 class="hm-footer__heading">Quick Links</h2>
                <nav class="hm-footer__nav" aria-label="Quick links">
                    <ul class="hm-footer__links">
                        <li><a href="{{ route('frontend.index') }}">Home</a></li>
                        <li><a href="{{ route('frontend.about-us') }}">About Us</a></li>
                        <li><a href="#">Programs</a></li>
                        <li><a href="{{ route('frontend.testimonials') }}">Success Stories</a></li>
                        <li><a href="{{ route('frontend.contact-us') }}">Contact Us</a></li>
                    </ul>
                </nav>
            </div>

            {{-- Column 3 — Our Programs --}}
            <div class="col-lg-3 col-md-6 hm-footer__col">
                <h2 class="hm-footer__heading">Our Programs</h2>
                <nav class="hm-footer__nav" aria-label="Our programs">
                    <ul class="hm-footer__links">
                        <li><a href="#">Talent Acquisition Training</a></li>
                        <li><a href="#">Recruitment Fundamentals</a></li>
                        <li><a href="#">LinkedIn Sourcing</a></li>
                        <li><a href="#">Boolean Search Training</a></li>
                        <li><a href="#">HR Operations</a></li>
                    </ul>
                </nav>
            </div>

            {{-- Column 4 — Contact --}}
            <div class="col-lg-4 col-md-6 hm-footer__col">
                <h2 class="hm-footer__heading">Contact Us</h2>
                <ul class="hm-footer__contact">
                    <li class="hm-footer__contact-item">
                        <i class="fa-solid fa-phone hm-footer__contact-icon" aria-hidden="true"></i>
                        <a href="tel:+917824094044" class="hm-footer__contact-link">+91 7824094044</a>
                    </li>
                    <li class="hm-footer__contact-item">
                        <i class="fa-solid fa-envelope hm-footer__contact-icon" aria-hidden="true"></i>
                        <a href="mailto:info@hiremindsacademy.com" class="hm-footer__contact-link">info@hiremindsacademy.com</a>
                    </li>
                    <li class="hm-footer__contact-item">
                        <i class="fa-solid fa-location-dot hm-footer__contact-icon" aria-hidden="true"></i>
                        <span class="hm-footer__address">
                            <span class="hm-footer__branch">Chennai Branch Address:</span>
                            No. 12, 2nd Floor, Anna Salai, T. Nagar, Chennai, Tamil Nadu 600017
                        </span>
                    </li>
                    <li class="hm-footer__contact-item">
                        <i class="fa-solid fa-location-dot hm-footer__contact-icon" aria-hidden="true"></i>
                        <span class="hm-footer__address">
                            <span class="hm-footer__branch">Coimbatore Branch Address:</span>
                            No. 45, Cross Cut Road, Gandhipuram, Coimbatore, Tamil Nadu 641012
                        </span>
                    </li>
                </ul>
            </div>

        </div>

        <hr class="hm-footer__divider">

        <div class="hm-footer__bottom">
            <p class="hm-footer__copy">
                &copy;2026 Hire Minds Academy. All Rights Reserved.
                <span class="hm-footer__dev-line">Developed by
                    <a href="https://www.cloudi5.com/" target="_blank" rel="noopener" class="hm-footer__dev">Cloudi5 Technologies</a>
                </span>
            </p>
            <nav class="hm-footer__legal" aria-label="Legal">
                <a href="#">Privacy Policy</a>
                <span class="hm-footer__legal-sep" aria-hidden="true">|</span>
                <a href="#">Terms &amp; Conditions</a>
            </nav>
        </div>
    </div>
</footer>

{{-- Floating scroll-to-top button --}}
<button type="button" class="hm-scrolltop" id="hmScrollTop" aria-label="Scroll to top">
    <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
</button>
