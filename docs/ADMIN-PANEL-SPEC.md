# HireMinds Academy — Admin Panel Functional Specification & Information Architecture

> **Status:** DRAFT for approval. No code is written until this is approved.
> **Site type:** Enquiry-based training-institute website. **NOT an LMS** — no student login, no classes, no certificates, no payments. Every conversion path ends in an **enquiry**.
> **Admin stack:** Custom **Laravel + Blade + Bootstrap 5** admin, extending the existing `backend` scaffolding (`/admin` routes, `Backend\AuthController`, `resources/views/backend/`). No Tailwind. This matches the public site's stack and the scaffolding already in the repo.

---

## 1. Executive summary

The public site is **presentation + lead capture**. Therefore the admin panel has two jobs, in priority order:

1. **Capture and work enquiries** (the revenue engine). This is the core module — everything else is content that drives enquiries.
2. **Manage the content** that renders through the existing Blade partials, with zero redesign of the frontend. The admin feeds data into the *same* views already built; the partials become data-driven instead of hardcoded arrays.

The design principle throughout: **the frontend view layer already exists and is correct.** We are not rebuilding it — we are replacing the hardcoded `@php $array = [...]` blocks in each Blade file with Eloquent data. That keeps risk low and lets us ship module-by-module.

---

## 2. Guiding principles

| Principle | Consequence for the build |
|---|---|
| Enquiry-first | The `enquiries` module is Phase 1, before most content. Enquiries are **never hard-deleted** (soft-delete + audit). |
| Reuse the existing partials | `partials/faq`, `partials/contact-form`, `partials/testimonials`, `partials/counters`, `partials/partners`, `partials/career-success`, `partials/course-card` already exist and are shared. Each maps to exactly one admin module and renders everywhere it's included. |
| One source of truth | Contact info (phone, email, addresses, map URLs) lives **once** in Settings and is referenced by the footer, the contact section, and course-details — not duplicated per page. |
| Draft → Publish → Cache-bust | Content has a status; saving invalidates the relevant cache tag so the (already performance-tuned) frontend stays fast without serving stale content. |
| Upload discipline | The media pipeline auto-converts/resizes on upload (see §15). This directly prevents a repeat of the 40 MB hero background that had to be fixed manually. |
| Role-appropriate surfaces | A Counsellor sees an enquiry workspace; a Content Manager sees content. Same login, different landing dashboards. |

---

## 3. Frontend content audit (page-by-page)

Legend — **D** = Dynamic (admin-managed), **S** = Static (in code), **R** = Reusable module (managed once, rendered many places), **F** = Functional (search/filter/pagination logic, no content to manage).

### 3.1 Home (`/`)
| Section | Class | Decision | Module | Notes |
|---|---|---|---|---|
| Hero (badge, title, desc, 2 buttons, right image, google-rating card, review card, enroll/avatars card) | D | Manage | Page Sections → *Home Hero* | Floating tech icons + aurora = **S** (decorative). |
| Trusted Partners | D · R | CRUD | Partners | marquee |
| About blurb (home) | S → **recommend D** | Optional | Page Sections → *Home About* | Marketing copy; low cost to make editable. See §4.1 |
| Counters | D · R | CRUD | Counters | shared with About + Testimonials pages |
| Top Categories | D | CRUD + SEO + "View Enquiries" | Categories | slug, per-category enquiry rollup |
| Popular Courses | D | flag on Course | Courses (`is_popular`) | no separate module |
| Why Choose HireMinds | S → **recommend D** | Optional | Why-Choose Features | 4 marketing cards; see §4.1 |
| Upcoming Events | D | CRUD | Events | carousel |
| Student Success Stories | D | CRUD | Success Stories | |
| Latest Blog | D | flag/order on Blog | Blogs (`is_featured` / latest) | |
| Journey / "Our Journey" reels | D · R | CRUD | Journey Gallery | shared with Testimonials page ("Career Success") |
| Testimonials ("Voices…") | D · R | CRUD | Testimonials | shared with About + Testimonials pages |
| FAQ | D · R | CRUD | FAQs (global group) | shared with About + Contact |
| Let's Connect (contact block + form) | D · R | Sections + Settings | Page Sections + Settings + **Enquiries** | heading/desc/image = section; phone/email/addresses/map = Settings; submissions = Enquiries |
| Footer | D · R | Settings + Menus + Social | Footer module | site-wide |

### 3.2 About (`/about-us`)
| Section | Class | Module |
|---|---|---|
| Hero (title words, top stat badges, pills, review badge, description) | D | Page Sections → *About Hero* (word-split animation stays a view concern) |
| Partners / Counters / Testimonials / FAQ | R | reuse the same modules |
| Our Story (4 chapters: label, title, text, photo) | D | About Story Chapters (CRUD) |
| Our Purpose (Vision + Mission) | D | Page Sections → *About Purpose* |
| Our Features (expanding cards) | D | About Features (CRUD) |
| Our Learning Approach (circle image + floating pills) | D | Page Sections → *About Approach* |
| Aurora, decorative stars/dots | S | — |

### 3.3 Courses (`/courses`)
Hero banner (breadcrumb, title, desc, "Let's Connect" CTA, image) = **Page Sections → *Courses Hero***. Grid = **Courses** module. Search + category filter + results count + pagination = **F**.

### 3.4 Course Details (`/course/{slug}`)
**Everything dynamic via the Courses module** (one course = one record + children):
- Hero: label, title, description, meta (date, students), Enroll/Brochure buttons, banner image.
- Feature strip: duration, mode, skill level, certificate, placement — **course fields**.
- About the Course (rich text).
- Course Highlights (8) → `course_highlights`.
- Skills You'll Gain (6) → `course_skills`.
- Continue Learning (related) → `course_related` (or by category).
- FAQ → course-scoped FAQs (reuse FAQ module, `course_id`).
- Contact section + **Enroll modal** → **Enquiries** (type = course).

### 3.5 Blog (`/blog`) & Blog Details (`/blog-details` → will become `/blog/{slug}`)
Banner = **Page Sections → *Blog Hero***. Listing + article = **Blogs CMS**. Article body is **block-based** today (`p` / `heading` / `figure` / custom `ai-check`) — recommend keeping a block editor (see §4.5). Sidebar: Latest (Blogs), Tags (Tags), Connect (Social Links). Category filter + search = **F**.

### 3.6 Testimonials (`/testimonials`)
Banner = section. Body = reuse **Journey Gallery** (Career Success), **Counters**, **Testimonials** modules. No new content.

### 3.7 Contact (`/contact-us`)
Banner = section. Contact block + form = reuse **Contact/Enquiries**. Map branch switch (Coimbatore / Chennai) = **Settings** (branch addresses + embed URLs). FAQ = reuse.

**Conclusion:** every repeating list becomes a CRUD module; every singular block becomes a *Page Section*; contact/branch/social/integration data becomes *Settings*; all forms feed **Enquiries**. Nothing on the frontend needs redesign.

---

## 4. Senior recommendations & improvements (beyond the brief)

**4.1 Make "Why Choose" and "Home About" editable.** They're marketing copy that changes with positioning. Cost is one small table + one section — trivial versus a code deploy every time wording changes. *Recommended; you can decline and keep them static.*

**4.2 Single source for contact info.** Store phone/email/addresses/map-embeds once in Settings. Footer, contact section, and course-details all read from it. Avoids the three-places-to-edit problem.

**4.3 Global vs course FAQs in one module.** `faqs.course_id` nullable: `NULL` = global (home/about/contact), set = that course's FAQ. One admin screen, filtered.

**4.4 Enquiry reference numbers.** Human-friendly `HM-2026-000123` for phone conversations and reporting.

**4.5 Keep the block-based blog editor.** The article renderer already supports typed blocks including a bespoke `ai-check` panel. A plain WYSIWYG would lose that. Recommend a lightweight block editor (repeatable typed rows) over dumping raw HTML.

**4.6 Spam protection on every form.** Honeypot + reCAPTCHA v3 + rate-limit. Failing checks → `status = Spam` (kept, not dropped, so nothing legitimate is lost silently).

**4.7 Soft deletes + audit log everywhere.** Especially enquiries and content. Nothing is truly destroyed; every change is attributable.

**4.8 Slug-change redirects.** A `redirects` table so renaming a course/blog slug 301s the old URL — protects the SEO you already invested in. *(Phase 7 / future.)*

**4.9 UTM capture on enquiries.** Store `utm_source/medium/campaign` + referrer so Marketing can attribute leads.

**4.10 Cache invalidation baked in.** Tie every content save to a cache tag flush, consistent with the performance work already done (config/view cache, image optimization). Frontend stays at its current speed.

**4.11 Branch-aware from day one (lightweight).** You already run Chennai + Coimbatore. A `branches` table now (even just two rows) lets enquiries carry a branch and lets you route/report per branch later without a migration reshuffle. *(Recommended; can be deferred.)*

---

## 5. Information Architecture — Admin sidebar

```
DASHBOARD

ENQUIRIES                     ◀ core
  ├─ All Enquiries
  ├─ General
  ├─ Course Enquiries
  ├─ Contact Enquiries
  ├─ Event Registrations
  ├─ Follow-ups (due / overdue)
  └─ Spam / Archive

CONTENT
  ├─ Courses
  │   ├─ All Courses          (highlights, skills, related, course-FAQs live inside a course)
  │   └─ Course Categories    (+ View Courses · View Enquiries · SEO slug)
  ├─ Blog
  │   ├─ Posts
  │   ├─ Blog Categories
  │   ├─ Tags
  │   └─ Authors
  ├─ Events
  ├─ Success Stories
  ├─ Testimonials
  ├─ Partners
  ├─ FAQs                     (global + course-scoped)
  ├─ Counters
  └─ Journey Gallery

PAGE BUILDER  (singular section content)
  ├─ Home           (Hero · About · Why-Choose · Let's Connect)
  ├─ About          (Hero · Story Chapters · Purpose · Features · Approach)
  ├─ Courses Hero
  ├─ Blog Hero
  ├─ Testimonials Hero
  └─ Contact Hero

APPEARANCE
  ├─ Footer         (columns / quick links / programs / career support)
  ├─ Social Links
  └─ Navigation Menus

SEO
  ├─ Page Meta      (per page: title, desc, keywords, canonical, OG, schema)
  └─ Redirects      (future)

MEDIA LIBRARY

REPORTS
  ├─ Enquiry Reports
  ├─ Course Reports
  ├─ Category Reports
  ├─ Blog Reports
  └─ Monthly Reports

SETTINGS
  ├─ General        (name, logo, favicon, tagline)
  ├─ Contact & Branches (phone, email, addresses, map embeds)
  ├─ Integrations   (SMTP, GA4, Meta Pixel, GTM, WhatsApp, reCAPTCHA)
  └─ Cache & Maintenance

ADMINISTRATION  (Super Admin / Admin only)
  ├─ Users
  ├─ Roles & Permissions
  └─ Activity Log

Global search (top bar) · Notifications bell (top bar) · Profile menu
```

---

## 6. Dashboard specification

Role-aware landing. **Counsellors** land on the enquiry workspace; **Content/Marketing** land on the content dashboard; **Admins** see the full board.

**Row 1 — KPI cards (clickable → filtered list):**
Today's Enquiries · This Month · Pending Follow-ups (due today / overdue) · Conversion Rate (converted ÷ total, 30d) · Courses · Blogs · Events · Testimonials · FAQs · Active Categories.

**Row 2 — Charts:**
- Monthly Enquiries (12-month line/bar).
- Enquiry Status Funnel (New → Contacted → Interested → Follow-up → Converted).
- Course-wise Enquiries (top 10 bar).
- Category-wise Enquiries (donut).

**Row 3 — Lists:**
- Recent Enquiries (name · course/category · source · status · assigned · time; quick actions).
- Top Enquired Courses.
- Latest Blogs (status).
- Recent Activity (audit feed).

**Filters:** global date-range picker; branch selector (if branches enabled).

---

## 7. Module specifications

> Every list view has: search, column sort, status filter, pagination, bulk actions, per-row actions. Every CRUD form has: validation, media picker, draft/publish where relevant, an SEO tab where relevant, and drag-to-reorder where `sort_order` exists. All content tables carry `is_active`, `sort_order`, `created_by`, timestamps, and soft-deletes.

### 7.1 Enquiries  *(core)*
**Frontend sources:** shared contact form (home, contact, course-details), Enroll modal (course-details), event registration links.
**List columns:** Ref · Name · Phone · Email · Type · Course/Category/Event · Source page · Status · Assigned · Created.
**Actions:** View (full timeline) · Change status · Assign staff · Add note · Set follow-up date · Convert · Mark spam · Export CSV · (bulk: assign / status / export).
**Fields captured:** `reference_no`, `type` (general|course|contact|event), `name`, `email`, `phone`, `city`, `course_id?`, `category_id?`, `event_id?`, `looking_for`, `interest`, `message`, `source_url`, `status`, `assigned_to?`, `follow_up_at?`, `utm_*`, `referrer`, `ip`, `user_agent`.
**Children:** `enquiry_notes` (timeline: note + status transition + author + time).
**Rules:** never hard-delete; spam auto-flag; new enquiry → notification + optional email/WhatsApp.

### 7.2 Courses
**Renders:** Courses grid, Course Details (all of it), Home "Popular Courses" (`is_popular`), "Continue Learning".
**Fields:** `title`, `slug`, `category_id`, `badge/tag`, `thumbnail`, `hero_image`, `rating`, `duration`, `mode`, `skill_level`, `certificate`, `placement_support`, `students_count`, `event_date?`, `short_description`, `about` (rich), `is_popular`, `status`, `sort_order`, `published_at`, SEO (via seo_meta).
**Children:** `course_highlights` (title, icon, order ×8) · `course_skills` (title, description, order ×6) · course FAQs (FAQ module, `course_id`) · `course_related` (self-pivot or auto-by-category).
**Actions:** View · Edit · Delete · Duplicate · Toggle Popular · **View Enquiries** (for this course).

### 7.3 Categories (course categories)
**Renders:** Home "Top Categories", course filtering.
**Fields:** `name`, `slug`, `icon` (iconsax), `tone/blob`, `image`, `description`, `sort_order`, `is_active`, SEO.
**Actions:** View · Edit · Delete · **View Courses** · **View Enquiries** (direct category enquiries **plus** enquiries whose course belongs to this category — the rollup you specified).

### 7.4 Blog CMS
**`blogs`:** `title`, `slug`, `blog_category_id`, `author_id`, `featured_image`, `banner_image`, `short_description`, `content` (blocks), `reading_time`, `publish_date`, `is_featured`, `status` (draft|published|scheduled), SEO (`meta_title`, `meta_description`, `og_image`).
**Supporting:** `blog_categories` (name, slug), `tags` + `blog_tag` pivot, `authors` (name, avatar, bio).
**Renders:** Blog listing, Blog details (article + sidebar Latest/Tags), Home "Latest Blog".
**Actions:** View · Edit · Delete · Duplicate · Toggle Featured · Preview draft.

### 7.5 Events
`speaker_name`, `title`, `price`, `event_date`, `thumbnail/person_image`, `tone` (card gradient), `description`, `button_text`, `registration_link`, `sort_order`, `is_active`. Registrations → Enquiries (type = event, `event_id`).

### 7.6 Testimonials
`name`, `designation`, `review`, `rating`, `photo`, `sort_order`, `is_active`. Rendered on home / about / testimonials.

### 7.7 Success Stories
`student_image`, `name`, `company`, `designation`, `salary`, `story`, `sort_order`, `is_active`.

### 7.8 Partners
`name`, `logo`, `website_url`, `sort_order`, `is_active`.

### 7.9 FAQs
`question`, `answer`, `course_id?` (null = global), `sort_order`, `is_active`. One screen, filter by *Global* vs a course.

### 7.10 Counters
`value`, `suffix`, `label`, `icon`, `group` (home|about|testimonials or shared), `sort_order`, `is_active`.

### 7.11 Journey Gallery
`title`, `thumbnail`, `video_thumbnail?`, `instagram_url`, `sort_order`, `is_active`. Powers home "Our Journey" reels + testimonials "Career Success".

### 7.12 Why-Choose Features  *(recommended dynamic)*
`title`, `description`, `pills` (json), `person_image`, `bg_image`, `tone`, `sort_order`, `is_active`.

### 7.13 Page Sections  *(singular structured content)*
A `page_sections` table keyed by `page` + `section_key`, storing typed fields (structured, validated — not free JSON dumped into a textarea). Covers: Home Hero, Home About, About Hero/Purpose/Approach, Courses/Blog/Testimonials/Contact heroes, Let's-Connect heading/desc/image. Repeating children of a section (e.g. About *Story Chapters*, About *Features*) are their own small tables for clean forms + ordering.

### 7.14 Footer / Menus / Social
`navigation_menus` + `menu_items` (Quick Links, Programs, Career Support — label, url, order, status). `social_links` (platform, url, icon, order, status). Footer logo/description/copyright/legal = Settings.

### 7.15 SEO module
Polymorphic `seo_meta` (`seoable_type`, `seoable_id`): `meta_title`, `meta_description`, `keywords`, `canonical_url`, `og_image`, `schema_json`. Attaches to Pages, Courses, Blogs, Categories. A **Page Meta** screen lists the 8 pages; entity SEO lives on each entity's form (an "SEO" tab).

### 7.16 Media Library
Central browsable library (Spatie MediaLibrary under the hood): grid, folders, search, replace, delete, alt text, usage count. **Upload policy in §15.** Every image field across modules opens this picker.

### 7.17 Settings / Integrations
Grouped key/value `settings`: General (name, logo, favicon, tagline), Contact & Branches, Integrations (SMTP, GA4, Meta Pixel, GTM, WhatsApp number, reCAPTCHA keys). Cast by type; cached.

### 7.18 Users, Roles & Permissions
`users` (+ avatar, phone, is_active, last_login_at). Spatie roles/permissions. Roles: **Super Admin, Admin, Content Manager, Marketing, Counsellor** (matrix §11).

### 7.19 Notifications
In-app (bell) via Laravel notifications: new enquiry, new contact submission, new event registration, follow-up due. Optional email/WhatsApp fan-out (queued).

### 7.20 Reports
Enquiry / Course / Category / Blog / Monthly. Filter by date + branch + status. Export CSV/PDF; (future) scheduled email.

### 7.21 Global search
Top-bar search across Courses, Blogs, Testimonials, Events, FAQs, Enquiries (LIKE queries first; Laravel Scout later — §16).

---

## 8. Database schema

**Content — collections (each: `id`, `is_active`, `sort_order`, `created_by`, timestamps, `deleted_at`):**
`partners` · `categories` · `courses` · `course_highlights` · `course_skills` · `course_related` (pivot) · `blog_categories` · `blogs` · `tags` · `blog_tag` (pivot) · `authors` · `events` · `success_stories` · `testimonials` · `faqs` · `counters` · `journey_items` · `why_choose_features` · `about_story_chapters` · `about_features` · `social_links`.

**Sections / config:**
`page_sections` (page, section_key, payload typed) · `settings` (group, key, value, type) · `pages` (key, name, route) · `seo_meta` (polymorphic) · `navigation_menus` · `menu_items` · `branches`.

**Enquiries (core):**
`enquiries` · `enquiry_notes`.

**Access / system:**
`users` · Spatie `roles` / `permissions` / pivots · `activity_log` · `media` (+ `media_folders`) · `notifications` · `redirects` (future).

### Key relationships (ERD in prose)
```
categories 1───* courses
courses    1───* course_highlights
courses    1───* course_skills
courses    *───* courses            (course_related, self)
courses    1───* faqs               (faqs.course_id nullable; NULL = global)
courses    1───* enquiries
categories 1───* enquiries          (direct) ; category enquiry count = direct  +  via courses.category_id
events     1───* enquiries
blog_categories 1───* blogs
authors    1───* blogs
blogs      *───* tags               (blog_tag)
enquiries  1───* enquiry_notes
users      1───* enquiries          (assigned_to)
users      *───* roles              (Spatie)
seo_meta   *───1 {pages|courses|blogs|categories}   (polymorphic seoable)
media      *───1 <any>              (polymorphic, Spatie)
branches   1───* enquiries          (optional)
```

The **category → enquiries rollup** you called out is a UNION: `enquiries.category_id = X` **OR** `enquiries.course_id IN (courses where category_id = X)`.

---

## 9. Enquiry workflow (state machine)

```
Visitor submits form
        │  (honeypot + reCAPTCHA + rate-limit)
        ▼
   ┌─────────┐   pass → New          fail → Spam
   │ create  │──────────────┐            │
   │ enquiry │              ▼            ▼
   └─────────┘        notify admins   Spam bucket (kept)
                      + counsellors
                            │
        assign (manual or round-robin)
                            ▼
   New → Contacted → Interested → Follow-up ──┐
                            │                 │ (reminder on follow_up_at → dashboard)
                            ▼                 │
                        Converted  /  Closed ◀┘
```
Every transition writes an `enquiry_notes` row (from-status, to-status, author, timestamp, optional note). "Pending Follow-ups" = `status = Follow-up AND follow_up_at <= today`.

---

## 10. Content / admin workflow

`Login → role-aware dashboard → module list → create/edit (validation · media picker · SEO tab · ordering) → draft → preview → publish → cache tag flushed → live`. Bulk actions on lists. Everything audited.

---

## 11. Roles & permissions matrix

| Capability | Super Admin | Admin | Content Manager | Marketing | Counsellor |
|---|:--:|:--:|:--:|:--:|:--:|
| Dashboard | ✔ | ✔ | ✔ (content) | ✔ (marketing) | ✔ (enquiry) |
| Enquiries — view all | ✔ | ✔ | — | ✔ (read) | ✔ |
| Enquiries — work (status/notes/assign) | ✔ | ✔ | — | — | ✔ |
| Enquiries — assign staff | ✔ | ✔ | — | — | — |
| Courses / Categories | ✔ | ✔ | ✔ | — | read |
| Blog / Tags / Authors | ✔ | ✔ | ✔ | ✔ | — |
| Events / Success / Testimonials / Partners / FAQ / Counters / Journey | ✔ | ✔ | ✔ | ✔ (partial) | — |
| Page Sections / Footer / Menus | ✔ | ✔ | ✔ | — | — |
| SEO | ✔ | ✔ | ✔ | ✔ | — |
| Media Library | ✔ | ✔ | ✔ | ✔ | — |
| Reports | ✔ | ✔ | read | ✔ | enquiry-only |
| Settings / Integrations | ✔ | ✔ | — | analytics only | — |
| Users | ✔ | ✔ (no roles) | — | — | — |
| Roles & Permissions | ✔ | — | — | — | — |
| Activity Log | ✔ | ✔ | — | — | — |

---

## 12. Reusable modules → frontend placements

| Module | Home | About | Courses | Course Details | Blog | Blog Details | Testimonials | Contact |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| Partners | ✔ | ✔ | | | | | | |
| Counters | ✔ | ✔ | | | | | ✔ | |
| Testimonials | ✔ | ✔ | | | | | ✔ | |
| FAQ | ✔ | ✔ | | ✔ | | | | ✔ |
| Contact/Enquiry | ✔ | | | ✔ | | | | ✔ |
| Journey Gallery | ✔ | | | | | | ✔ | |
| Courses (cards) | ✔ | | ✔ | ✔ (related) | | | | |
| Social Links | ✔ (footer) | | | | | ✔ (sidebar) | | ✔ |

Manage once, appears everywhere it's included.

---

## 13. Recommended packages & tech decisions

- **UI:** continue the **custom Bootstrap 5** admin (scaffolding exists; matches the site). *(Alternatives if you want speed over consistency: Filament — fastest but Tailwind+Livewire; Backpack — Bootstrap-based CRUD. My recommendation: custom Bootstrap, since the foundation is already here.)*
- `spatie/laravel-permission` — roles & permissions.
- `spatie/laravel-medialibrary` — uploads, conversions, responsive variants.
- `spatie/laravel-activitylog` — audit trail.
- `spatie/laravel-sluggable` — slugs.
- `laravel/scout` — global search (later).
- `barryvdh/laravel-dompdf` — PDF reports (later).
- Charts: Chart.js (vanilla, matches "no heavy JS frameworks").

---

## 14. Caching & performance

Consistent with the optimization already done: cache `settings`, menus, and published section/collection content under cache **tags**; flush the relevant tag on every save. Keep `config:cache` + `view:cache` in the deploy step. Result: admin edits go live without degrading the current page speed.

---

## 15. Media & upload policy  *(prevents the 40 MB-image class of bug)*

On every upload the media pipeline: validates type/size, strips metadata, **auto-converts photos to WebP**, generates sized conversions (thumb / card / hero), and rejects anything past a sensible dimension/size cap. Admins pick from the library; the frontend gets right-sized assets automatically. This makes the earlier manual image-optimization pass a permanent, enforced rule rather than a one-off cleanup.

---

## 16. Future scalability

- **Branches** as first-class (routing, per-branch staff & reports).
- **Notifications automation:** queued email/SMS/WhatsApp on enquiry events; drip follow-ups.
- **Lead scoring & UTM attribution** for Marketing.
- **Laravel Scout** (Meilisearch/Algolia) replacing LIKE search.
- **API layer** (Sanctum + API Resources) for a future mobile/counsellor app — headless-ready.
- **Multi-language** (spatie/laravel-translatable) if they expand regionally.
- **Slug redirects** table for SEO continuity.
- **Scheduled reports** emailed to management.
- **Course batches/schedules** (dates only, still not an LMS) if enquiry demand needs slotting.

---

## 17. Phased implementation roadmap

| Phase | Deliverable | Why this order |
|---|---|---|
| **0 — Foundation** | Admin auth/layout (extend existing), roles & permissions, Settings, Media Library, Dashboard shell, cache plumbing | Everything else sits on this |
| **1 — Enquiries** | Enquiries CRUD + workspace, wire the live contact form + enroll modal + event links, notifications, spam protection | The revenue engine; highest value first |
| **2 — Courses** | Courses + Categories (+ highlights/skills/related/course-FAQs), make Courses page + Course Details + Popular + Related dynamic | Biggest content surface; drives course enquiries |
| **3 — Blog CMS** | Blogs + categories/tags/authors + block editor; Blog + Blog Details + Home "Latest" dynamic | Marketing/SEO surface |
| **4 — Home modules** | Hero, Counters, Partners, Events, Success Stories, Journey, Why-Choose, Testimonials, FAQ | Turns the homepage fully dynamic |
| **5 — About + Footer + SEO** | About sections, Footer/menus/social, Page-meta SEO | Completes site content |
| **6 — Reports + Users + Notifications polish** | Charts, exports, user management, notification fan-out | Operational maturity |
| **7 — Hardening** | Caching depth, redirects, audit review, scalability items | Production polish |

Each phase ships independently: the frontend keeps working throughout, because we swap hardcoded arrays for DB data one section at a time.

---

## 18. Decisions needed from you before we start

1. **Admin UI:** confirm **custom Bootstrap 5** (recommended) — or do you want Filament/Backpack instead?
2. **Make "Why Choose" + "Home About" editable** (§4.1)? (recommend yes)
3. **Branches now or later** (§4.11)? Two rows today saves a migration later.
4. **Blog editor:** keep the **block-based** editor (preserves the AI-detector panel) or switch to a WYSIWYG?
5. **Notifications at launch:** in-app only first, or in-app + email/WhatsApp from Phase 1?
6. **Blog authors:** dedicated `authors` table (recommended) or reuse admin users?
7. **Confirm scope:** no payments, no student login, enquiry-only — correct?
8. **Phase 1 kickoff:** start with **Enquiries** (recommended), or would you rather see **Courses** first?

Once you approve (and answer the above), we implement **phase by phase, module by module** — starting with the foundation and the enquiry engine.
