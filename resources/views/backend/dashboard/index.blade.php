@extends('backend.template.layouts.template-base')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_sub', 'Welcome back — here is what is happening today')

@php
    // Placeholder data — wired to the Enquiry / content models in a later phase.
    $ic = fn ($p) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $p . '</svg>';

    $kpis = [
        ['label' => 'Total Enquiries',   'num' => '1,284', 'trend' => '+12.4%', 'up' => true,  'tone' => 'brown',
         'icon' => '<path d="M5.5 5h13l1.5 8v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-4l1.5-8Z"/><path d="M4 13h4l1.4 3h5.2L20 13"/>'],
        ['label' => 'Course Enquiries',  'num' => '842',   'trend' => '+8.1%',  'up' => true,  'tone' => 'gold',
         'icon' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1M8.5 10h7M8.5 14h5"/>'],
        ['label' => 'Contact Enquiries', 'num' => '442',   'trend' => '+3.6%',  'up' => true,  'tone' => 'blue',
         'icon' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.6 7 8.4 6 8.4-6"/>'],
        ['label' => 'Converted',         'num' => '318',   'trend' => '24.7% rate', 'up' => true, 'tone' => 'green',
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

    // Monthly enquiries — value is the bar height in %.
    $months = [
        ['Jan', 42], ['Feb', 55], ['Mar', 48], ['Apr', 68], ['May', 60], ['Jun', 78],
        ['Jul', 72], ['Aug', 88], ['Sep', 66], ['Oct', 92], ['Nov', 80], ['Dec', 74],
    ];

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
                <div class="hm-card h-100">
                    <div class="kpi">
                        <span class="kpi__icon kpi__icon--{{ $k['tone'] }}">{!! $ic($k['icon']) !!}</span>
                        <div>
                            <div class="kpi__num">{{ $k['num'] }}</div>
                            <div class="kpi__label">{{ $k['label'] }}</div>
                            <div class="kpi__trend {{ $k['up'] ? 'kpi__trend--up' : 'kpi__trend--down' }}">
                                &uarr; {{ $k['trend'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- =========================== CONTENT CHIPS =========================== --}}
    <div class="row g-3 mb-3">
        @foreach ($chips as $c)
            <div class="col-6 col-md-4 col-xl-2">
                <div class="hm-card h-100">
                    <div class="stat-chip">
                        <span class="stat-chip__ico">{!! $ic($c['icon']) !!}</span>
                        <div>
                            <div class="stat-chip__num">{{ $c['num'] }}</div>
                            <div class="stat-chip__label">{{ $c['label'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ============================== CHARTS ============================== --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-8">
            <div class="hm-card h-100">
                <div class="hm-card__head">
                    <h2 class="hm-card__title">Monthly Enquiries</h2>
                    <span class="hm-card__link">This year</span>
                </div>
                <div class="hm-card__body">
                    <div class="chart-bars">
                        @foreach ($months as [$m, $v])
                            <div class="chart-bars__col">
                                <div class="chart-bars__bar" style="height: {{ $v }}%"></div>
                                <span class="chart-bars__x">{{ $m }}</span>
                            </div>
                        @endforeach
                    </div>
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
                        <div class="donut" style="background: conic-gradient(#8B451F 0 62%, #E9A320 62% 88%, #2C7BE5 88% 100%);">
                            <div class="donut__center"><b>1,284</b><span>Total</span></div>
                        </div>
                        <ul class="donut-legend">
                            <li><span class="dot" style="background:#8B451F"></span> Course <b>62%</b></li>
                            <li><span class="dot" style="background:#E9A320"></span> Contact <b>26%</b></li>
                            <li><span class="dot" style="background:#2C7BE5"></span> Event <b>12%</b></li>
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
                                        <div class="hm-table__name">{{ $r['name'] }}</div>
                                        <div class="hm-table__sub">{{ $r['email'] }}</div>
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
