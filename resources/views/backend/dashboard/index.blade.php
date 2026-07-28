@extends('backend.template.layouts.template-base')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_sub', 'Welcome back — here is what is happening today')

@php
    // Live data comes from DashboardController; only the numbers are dynamic —
    // the layout, colours and icons are unchanged.
    $ic = fn ($p) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $p . '</svg>';

    $kpis = [
        ['label' => 'Total Enquiries',   'num' => number_format($stats['total']),   'trend' => $stats['today'] . ' today',       'tone' => 'peach',
         'icon' => '<path d="M5.5 5h13l1.5 8v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-4l1.5-8Z"/><path d="M4 13h4l1.4 3h5.2L20 13"/>'],
        ['label' => 'Course Enquiries',  'num' => number_format($stats['course']),  'trend' => $stats['pending'] . ' pending',   'tone' => 'sky',
         'icon' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1M8.5 10h7M8.5 14h5"/>'],
        ['label' => 'Contact Enquiries', 'num' => number_format($stats['contact']), 'trend' => $stats['contacted'] . ' contacted', 'tone' => 'lavender',
         'icon' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.6 7 8.4 6 8.4-6"/>'],
        ['label' => 'Closed',            'num' => number_format($stats['closed']),   'trend' => $stats['pending'] . ' still open', 'tone' => 'cream',
         'icon' => '<path d="M20 6 9 17l-5-5"/>'],
    ];

    $chipIcons = [
        'Courses'      => '<path d="M12 6.5C10.5 5 8 4.5 4 5v13c4-.5 6.5 0 8 1.5 1.5-1.5 4-2 8-1.5V5c-4-.5-6.5 0-8 1.5Z"/><path d="M12 6.5v13"/>',
        'Blog Posts'   => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
        'Testimonials' => '<path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/>',
        'Events'       => '<rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M3 9.5h18M8 3v3M16 3v3"/>',
        'FAQs'         => '<circle cx="12" cy="12" r="9"/><path d="M9.6 9.2a2.5 2.5 0 0 1 4.8.8c0 1.7-2.4 2.3-2.4 3.5M12 17h.01"/>',
        'Partners'     => '<path d="M12 3 5 6v5c0 4.6 3 7.6 7 9 4-1.4 7-4.4 7-9V6l-7-3Z"/><path d="M9.3 12l1.8 1.8 3.4-3.8"/>',
    ];
    $chips = [];
    foreach ($chipCounts as $label => $num) {
        $chips[] = ['num' => $num, 'label' => $label, 'icon' => $chipIcons[$label] ?? ''];
    }

    $months  = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    // Auto-scale the 0..N axis to the data so the trend reads well at any volume.
    $chartMax = max(1000, ...$newVals, ...$conVals);

    // --- Smooth SVG path from a 0..$chartMax series (midpoint-quadratic) ---
    $plotX = fn ($i) => 44 + $i * ((700 - 44) / 11);
    $plotY = fn ($v) => 266 - ($v / $chartMax) * (266 - 16);
    $smooth = function (array $vals, bool $area = false) use ($plotX, $plotY) {
        $p = [];
        foreach ($vals as $i => $v) { $p[] = [round($plotX($i), 1), round($plotY($v), 1)]; }
        $d = 'M ' . $p[0][0] . ' ' . $p[0][1];
        for ($i = 1; $i < count($p); $i++) {
            $xc = round(($p[$i - 1][0] + $p[$i][0]) / 2, 1);
            $yc = round(($p[$i - 1][1] + $p[$i][1]) / 2, 1);
            $d .= ' Q ' . $p[$i - 1][0] . ' ' . $p[$i - 1][1] . ' ' . $xc . ' ' . $yc;
        }
        $last = end($p);
        $d .= ' T ' . $last[0] . ' ' . $last[1];
        if ($area) { $d .= ' L ' . $last[0] . ' 266 L ' . $p[0][0] . ' 266 Z'; }
        return $d;
    };

    // $recent and $activity are supplied by DashboardController (real data).
    $feedIcon = '<path d="M12 8v4l3 2"/><circle cx="12" cy="12" r="9"/>';   // clock — generic activity icon

    // Real Course vs Contact split for the donut.
    $donutTotal   = max(1, $stats['total']);
    $coursePct    = round($stats['course']  / $donutTotal * 100);
    $contactPct   = 100 - $coursePct;
@endphp

@section('content')

    {{-- ============================== HERO BANNER ==============================
         Compact welcome banner. The cream background + 3D illustration + feature
         circles are the template's banner-right.png; only the left copy is overlaid. --}}
    {{-- <section class="dash-hero">
        <div class="dash-hero__content">
            <span class="dash-hero__badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v4c0 1 2.7 2.5 6 2.5s6-1.5 6-2.5v-4"/></svg>
                Welcome Back, Admin
            </span>

            <h1 class="dash-hero__title">
                Empowering Learners.<br>
                <span class="dash-hero__title-accent">Building Bright Futures.</span>
            </h1>

            <span class="dash-hero__rule" aria-hidden="true"></span>

            <p class="dash-hero__text">Manage enquiries, track enrollments, and support student success — all from one smart dashboard.</p>

            <a href="{{ route('backend.contact-enquiries.index') }}" class="dash-hero__btn">
                View Reports
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
        </div>
    </section> --}}

    {{-- ============================== KPI ROW ============================== --}}
    <div class="row g-3 mb-3">
        @foreach ($kpis as $k)
            <div class="col-6 col-xl-3">
                <div class="kpi-card kpi-card--{{ $k['tone'] }} h-100">
                    <div class="kpi-card__top">
                        <span class="kpi-card__label">{{ $k['label'] }}</span>
                        <span class="kpi-card__icon">{!! $ic($k['icon']) !!}</span>
                    </div>
                    <div class="kpi-card__num">{{ $k['num'] }}</div>
                    <div class="kpi-card__trend">{{ $k['trend'] }}</div>

                </div>
            </div>
        @endforeach
    </div>

    {{-- ============================== CHARTS ============================== --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-8">
            <div class="hm-card h-100 analytics-card">
                <div class="hm-card__head">
                    <div>
                        <h2 class="hm-card__title">Enquiries Overview</h2>
                        <p class="analytics-card__sub">Enquiry analytics</p>
                    </div>
                    <span class="hm-card__link">{{ now()->year }}</span>
                </div>
                <div class="hm-card__body">
                    <svg class="linechart" viewBox="0 0 720 300" preserveAspectRatio="none" role="img" aria-label="Enquiries over the year">
                        <defs>
                            <pattern id="enquiryDots" width="7" height="7" patternUnits="userSpaceOnUse">
                                <circle cx="1.5" cy="1.5" r="1" fill="#C8CDD9" opacity=".48"/>
                            </pattern>
                            <linearGradient id="gNew" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#FF747A" stop-opacity=".10"/>
                                <stop offset="1" stop-color="#FF747A" stop-opacity="0"/>
                            </linearGradient>
                            <linearGradient id="gConv" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#6F8FFF" stop-opacity=".10"/>
                                <stop offset="1" stop-color="#6F8FFF" stop-opacity="0"/>
                            </linearGradient>
                        </defs>

                        <rect class="linechart__dots" x="45" y="22" width="655" height="244" rx="18" fill="url(#enquiryDots)"/>

                        {{-- gridlines + y labels (auto-scaled to the data) --}}
                        @foreach ([0, 0.25, 0.5, 0.75, 1] as $frac)
                            @php $g = (int) round($chartMax * $frac); $gy = round($plotY($g), 1); @endphp
                            <line class="linechart__grid" x1="44" y1="{{ $gy }}" x2="700" y2="{{ $gy }}"/>
                            <text class="linechart__ylabel" x="34" y="{{ $gy + 4 }}" text-anchor="end">{{ $g }}</text>
                        @endforeach

                        {{-- areas + lines --}}
                        <path d="{{ $smooth($newVals, true) }}"  fill="url(#gNew)"  stroke="none"/>
                        <path d="{{ $smooth($conVals, true) }}" fill="url(#gConv)" stroke="none"/>
                        <path d="{{ $smooth($newVals) }}"  fill="none" stroke="#FF747A" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="{{ $smooth($conVals) }}" fill="none" stroke="#6F8FFF" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>

                        {{-- points --}}
                        @foreach ($newVals as $i => $v)
                            <circle cx="{{ round($plotX($i), 1) }}" cy="{{ round($plotY($v), 1) }}" r="3.4" fill="#fff" stroke="#FF747A" stroke-width="2"/>
                        @endforeach

                        {{-- x labels --}}
                        @foreach ($months as $i => $m)
                            <text class="linechart__xlabel" x="{{ round($plotX($i), 1) }}" y="288" text-anchor="middle">{{ $m }}</text>
                        @endforeach
                    </svg>

                    <ul class="chart-legend">
                        <li><span class="line" style="background:#FF747A"></span> New Enquiries</li>
                        <li><span class="line" style="background:#6F8FFF"></span> Converted Enquiries</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="hm-card h-100 donut-card">
                <div class="hm-card__head">
                    <div>
                        <p class="donut-card__period">All time</p>
                        <h2 class="hm-card__title">Enquiries by Type</h2>
                    </div>
                </div>
                <div class="hm-card__body">
                    <div class="donut-wrap">
                        <div class="donut" style="background: conic-gradient(#FF8278 0 {{ $coursePct }}%, #7664C7 {{ $coursePct }}% 100%);">
                            <div class="donut__center"><span>Total enquiries</span><b>{{ number_format($stats['total']) }}</b></div>
                        </div>
                        <ul class="donut-legend">
                            <li><span class="dot" style="background:#FF8278"></span> Course <b>{{ $coursePct }}%</b></li>
                            <li><span class="dot" style="background:#7664C7"></span> Contact <b>{{ $contactPct }}%</b></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== RECENT ENQUIRIES + ACTIVITY ===================== --}}
    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="hm-card recent-enquiries-card">
                <div class="hm-card__head">
                    <h2 class="hm-card__title">Recent Enquiries</h2>
                    <a href="#" class="hm-card__link">View all</a>
                </div>
                <div class="table-responsive">
                    <table class="hm-table">
                        <thead>
                            <tr>
                                <th>Enquirer</th><th>Interest</th><th>Type</th><th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recent->take(4) as $r)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="hm-table__ava">{{ strtoupper(substr($r['name'], 0, 1)) }}</span>
                                            <div>
                                                <div class="hm-table__name">{{ $r['name'] }}</div>
                                                <div class="hm-table__sub">{{ $r['email'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $r['course'] }}</td>
                                    <td>{{ $r['type'] }}</td>
                                    <td><span class="pill pill--{{ $r['status'] }}">{{ ucfirst($r['status']) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="hm-card content-overview-card">
                <div class="hm-card__head">
                    <h2 class="hm-card__title">Content Overview</h2>
                    <span class="content-overview-card__badge">Website</span>
                </div>
                <div class="hm-card__body">
                    <ul class="feed">
                        @forelse ($activity as $a)
                            <li>
                                <span class="feed__ico">{!! $ic($feedIcon) !!}</span>
                                <div>
                                    <div class="feed__text"><b>{{ $a->action }}</b> — {{ $a->description }}</div>
                                    <div class="feed__time">{{ $a->created_at->diffForHumans() }} · {{ $a->actor }}</div>
                                </div>
                            </li>
                        @empty
                            <li>
                                <span class="feed__ico">{!! $ic($feedIcon) !!}</span>
                                <div>
                                    <div class="feed__text">No recent activity yet.</div>
                                    <div class="feed__time">Actions you take on enquiries will appear here.</div>
                                </div>
                            </li>
                        @endforelse
                    </ul>
                    <table class="content-overview-table">
                        <thead><tr><th>Content</th><th>Total</th></tr></thead>
                        <tbody>
                            @foreach ($chips as $chip)
                                <tr>
                                    <td><span class="content-overview-table__icon">{!! $ic($chip['icon']) !!}</span><span>{{ $chip['label'] }}</span></td>
                                    <td><span class="content-overview-table__count">{{ number_format($chip['num']) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
