{{--
| The banner across the top of a dynamic form.
|
| Shared by the public page and the admin preview so the two cannot drift — a
| preview that does not look like the live form is not a preview.
|
|   @include('frontend.partials.form-header', ['form' => $form])
|
| The band is the same terracotta gradient and off-centre gold glow the Terms /
| Privacy pages use (content-page.css), so a form reads as part of the site
| rather than as a bare box that happens to be hosted on it.
|
| The logo sits on a white chip: it is drawn for a light background — the site
| header puts it on white — and would not read on the terracotta.
--}}
<header class="hmf__banner">
    <span class="hmf__banner-glow" aria-hidden="true"></span>

    <div class="hmf__banner-inner">
        {{-- The logo only — not a link. The form's page stands on its own and
             nothing on it leads into the rest of the website. --}}
        <span class="hmf__brand">
            <img src="{{ \App\Models\Setting::image('site_logo', 'assets/images/branding/logo.png') }}"
                 alt="Hire Minds Academy" width="150" height="44">
        </span>

        <h1 class="hmf__title">{{ $form->title }}</h1>

        @if (filled($form->description))
            <p class="hmf__desc">{{ $form->description }}</p>
        @endif
    </div>
</header>
