@extends('backend.template.layouts.template-base')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_sub', 'Welcome back — here is what is happening today')

@php
    // Placeholder data — wired to the Enquiry / content models in a later phase.
    $ic = fn ($p) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $p . '</svg>';

    $kpis = [
        ['label' => 'Total Enquiries',   'num' => '1,284', 'trend' => '+12.4%', 'tone' => 'peach',
         'icon' => '<path d="M5.5 5h13l1.5 8v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-4l1.5-8Z"/><path d="M4 13h4l1.4 3h5.2L20 13"/>'],
        ['label' => 'Course Enquiries',  'num' => '842',   'trend' => '+8.1%',  'tone' => 'sky',
         'icon' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1M8.5 10h7M8.5 14h5"/>'],
        ['label' => 'Contact Enquiries', 'num' => '442',   'trend' => '+3.6%',  'tone' => 'lavender',
         'icon' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.6 7 8.4 6 8.4-6"/>'],
        ['label' => 'Converted',         'num' => '318',   'trend' => '24.7% rate', 'tone' => 'cream',
         'icon' => '<path d="M20 6 9 17l-5-5"/>'],
    ];

    $chips = [
        ['num' => '48', 'label' => 'Courses',      'icon' => '<path d="M12 6.5C10.5 5 8 4.5 4 5v13c4-.5 6.5 0 8 1.5 1.5-1.5 4-2 8-1.5V5c-4-.5-6.5 0-8 1.5Z"/><path d="M12 6.5v13"/>'],
        ['num' => '36', 'label' => 'Blog Posts',   'icon' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>'],
        ['num' => '24', 'label' => 'Testimonials', 'icon' => '<path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/>'],
        ['num' => '09', 'label' => 'Events',       'icon' => '<rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M3 9.5h18M8 3v3M16 3v3"/>'],
        ['num' => '18', 'label' => 'FAQs',         'icon' => '<circle cx="12" cy="12" r="9"/><path d="M9.6 9.2a2.5 2.5 0 0 1 4.8.8c0 1.7-2.4 2.3-2.4 3.5M12 17h.01"/>'],
        ['num' => '12', 'label' => 'Partners',     'icon' => '<path d="M12 3 5 6v5c0 4.6 3 7.6 7 9 4-1.4 7-4.4 7-9V6l-7-3Z"/><path d="M9.3 12l1.8 1.8 3.4-3.8"/>'],
    ];

    $months  = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $newVals = [520, 560, 600, 540, 620, 720, 700, 880, 760, 900, 820, 780];   // New Enquiries (0-1000)
    $conVals = [300, 340, 360, 320, 420, 510, 470, 600, 520, 640, 560, 540];   // Converted

    // --- Smooth SVG path from a 0-1000 series (midpoint-quadratic) ---
    $plotX = fn ($i) => 44 + $i * ((700 - 44) / 11);
    $plotY = fn ($v) => 266 - ($v / 1000) * (266 - 16);
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

    $recent = [
        ['name' => 'Aarav Sharma',   'email' => 'aarav@gmail.com',   'course' => 'Full-Stack Development', 'type' => 'Course',  'status' => 'new',        'time' => '5 min ago'],
        ['name' => 'Priya Nair',     'email' => 'priya.n@gmail.com', 'course' => 'Data Analytics',         'type' => 'Course',  'status' => 'contacted',  'time' => '22 min ago'],
        ['name' => 'Rahul Verma',    'email' => 'rahul.v@gmail.com', 'course' => 'General enquiry',        'type' => 'Contact', 'status' => 'interested', 'time' => '1 hr ago'],
        ['name' => 'Sneha Iyer',     'email' => 'sneha@gmail.com',   'course' => 'HR Training',            'type' => 'Course',  'status' => 'converted',  'time' => '3 hr ago'],
        ['name' => 'Mohammed Ali',   'email' => 'm.ali@gmail.com',   'course' => 'Cloud & DevOps',         'type' => 'Course',  'status' => 'new',        'time' => '5 hr ago'],
    ];

    $activity = [
        ['t' => 'New <b>course enquiry</b> from Aarav Sharma', 'time' => '5 minutes ago',
         'icon' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1M8.5 10h7"/>'],
        ['t' => 'Blog post <b>"Interview Tips"</b> published', 'time' => '1 hour ago',
         'icon' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/><path d="M14 3v5h5"/>'],
        ['t' => 'Enquiry from Sneha Iyer marked <b>Converted</b>', 'time' => '3 hours ago',
         'icon' => '<path d="M20 6 9 17l-5-5"/>'],
        ['t' => 'New <b>event</b> "Nail your interviews" added', 'time' => 'Yesterday',
         'icon' => '<rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M3 9.5h18M8 3v3M16 3v3"/>'],
    ];
@endphp

@section('content')

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
                    <div class="kpi-card__trend">&uarr; {{ $k['trend'] }} <span>from last month</span></div>

                </div>
            </div>
        @endforeach
    </div>

    {{-- ============================== CHARTS ============================== --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-8">
            <div class="hm-card h-100">
                <div class="hm-card__head">
                    <h2 class="hm-card__title">Enquiries Overview</h2>
                    <span class="hm-card__link">This year</span>
                </div>
                <div class="hm-card__body">
                    <svg class="linechart" viewBox="0 0 720 300" preserveAspectRatio="none" role="img" aria-label="Enquiries over the year">
                        <defs>
                            <linearGradient id="gNew" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#D8A64D" stop-opacity=".22"/>
                                <stop offset="1" stop-color="#D8A64D" stop-opacity="0"/>
                            </linearGradient>
                            <linearGradient id="gConv" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#6E5AA6" stop-opacity=".18"/>
                                <stop offset="1" stop-color="#6E5AA6" stop-opacity="0"/>
                            </linearGradient>
                        </defs>

                        {{-- gridlines + y labels --}}
                        @foreach ([0, 250, 500, 750, 1000] as $g)
                            @php $gy = round($plotY($g), 1); @endphp
                            <line class="linechart__grid" x1="44" y1="{{ $gy }}" x2="700" y2="{{ $gy }}"/>
                            <text class="linechart__ylabel" x="34" y="{{ $gy + 4 }}" text-anchor="end">{{ $g }}</text>
                        @endforeach

                        {{-- areas + lines --}}
                        <path d="{{ $smooth($newVals, true) }}"  fill="url(#gNew)"  stroke="none"/>
                        <path d="{{ $smooth($conVals, true) }}" fill="url(#gConv)" stroke="none"/>
                        <path d="{{ $smooth($newVals) }}"  fill="none" stroke="#D8A64D" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="{{ $smooth($conVals) }}" fill="none" stroke="#6E5AA6" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>

                        {{-- points --}}
                        @foreach ($newVals as $i => $v)
                            <circle cx="{{ round($plotX($i), 1) }}" cy="{{ round($plotY($v), 1) }}" r="3.4" fill="#fff" stroke="#D8A64D" stroke-width="2"/>
                        @endforeach

                        {{-- x labels --}}
                        @foreach ($months as $i => $m)
                            <text class="linechart__xlabel" x="{{ round($plotX($i), 1) }}" y="288" text-anchor="middle">{{ $m }}</text>
                        @endforeach
                    </svg>

                    <ul class="chart-legend">
                        <li><span class="line" style="background:#D8A64D"></span> New Enquiries</li>
                        <li><span class="line" style="background:#6E5AA6"></span> Converted Enquiries</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="hm-card h-100">
                <div class="hm-card__head">
                    <h2 class="hm-card__title">Enquiries by Type</h2>
                </div>
                <div class="hm-card__body">
                    <div class="donut-wrap">
                        <div class="donut" style="background: conic-gradient(#C89B3C 0 62%, #D8A64D 62% 88%, #6E5AA6 88% 100%);">
                            <div class="donut__center"><b>1,284</b><span>Total</span></div>
                        </div>
                        <ul class="donut-legend">
                            <li><span class="dot" style="background:#C89B3C"></span> Course <b>62%</b></li>
                            <li><span class="dot" style="background:#D8A64D"></span> Contact <b>26%</b></li>
                            <li><span class="dot" style="background:#6E5AA6"></span> Event <b>12%</b></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== RECENT ENQUIRIES + ACTIVITY ===================== --}}
    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="hm-card h-100">
                <div class="hm-card__head">
                    <h2 class="hm-card__title">Recent Enquiries</h2>
                    <a href="#" class="hm-card__link">View all</a>
                </div>
                <div class="table-responsive">
                    <table class="hm-table">
                        <thead>
                            <tr>
                                <th>Enquirer</th><th>Interest</th><th>Type</th><th>Status</th><th>When</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recent as $r)
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
                                    <td class="hm-table__sub">{{ $r['time'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="hm-card h-100">
                <div class="hm-card__head">
                    <h2 class="hm-card__title">Recent Activity</h2>
                </div>
                <div class="hm-card__body">
                    <ul class="feed">
                        @foreach ($activity as $a)
                            <li>
                                <span class="feed__ico">{!! $ic($a['icon']) !!}</span>
                                <div>
                                    <div class="feed__text">{!! $a['t'] !!}</div>
                                    <div class="feed__time">{{ $a['time'] }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

@endsection
