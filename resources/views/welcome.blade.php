@extends('layouts.app')
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Dynamic Meta Tags setup using Blade's -->
    <title>@yield('title', 'Whitehat')</title>
    <meta name="description" content="@yield('description', '')">
    <meta name="keywords" content="@yield('keywords', '')">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<!-- Page ki Meta Information yahan daalein -->
@section('title', 'Whitehat — The CRM Built Around the Follow-Up')
@section('description', 'Whitehat helps admins manage leads, assign sales reps, track daily follow-ups, and monitor performance from a single dashboard.')
@section('keywords', 'CRM, lead management, sales team management, daily follow-up tracking, admin dashboard, sales pipeline')
@php
    $baseDir = request()->getBasePath();
    $homeUrl = url('/');
    $productName = $productName ?? "Whitehat";
@endphp

 

@section('content')
<!-- ============ HERO ============ -->
<!-- ============ NAV ============ -->
<nav class="navbar" id="navbar">
  <div class="navbar-inner">
    <a href="{{ url('/') }}" class="brand js-home-link">
      <span class="brand-mark"><i class="fa-solid fa-diagram-project"></i></span>
      <span class="brand-word">{{ $productName ?? 'Whitehat' }}</span>
    </a>
    <div class="nav-links" id="navLinks">
      <a href="#workflow">Workflow</a>
      <a href="#features">Features</a>
      <a href="#roles">Roles</a>
      <a href="#pipeline">Pipeline</a>
      <a href="#dashboard">Dashboard</a>
      <a href="javascript:void(0)" class="nav-cta-mobile js-trigger-demo">Request Demo</a>
    </div>
    <div class="nav-actions">
      <a href="javascript:void(0)" class="nav-cta js-trigger-demo">
        <span class="nav-cta-shimmer"></span>
        <i class="fa-solid fa-rocket"></i>
        Request Demo
      </a>
      <button class="nav-toggle" id="navToggle" aria-label="Toggle menu"><i class="fa-solid fa-bars"></i></button>
    </div>
  </div>
</nav>
  <header class="hero-section position-relative overflow-hidden" id="top">
    <div class="wrap hero-grid">
 
    <div class="hero-copy reveal">
      <div class="eyebrow"><i class="fa-solid fa-bolt"></i> LEAD-TO-CLOSE CRM FOR GROWING SALES TEAMS</div>
      <h1 class="serif">Manage leads, track reps, and close more deals — <em>from one screen.</em></h1>
      <p class="hero-sub">Whitehat gives admins full live visibility while empowering sales reps with organized daily follow-ups, one-click lead assignment, and real-time conversion reports — ensuring no deal falls through the cracks.</p>
      <div class="hero-actions">
        <a href="javascript:void(0)" class="btn btn-gold js-trigger-demo">Get Started <i class="fa-solid fa-arrow-right-long"></i></a>
        
        <a href="{{ url('/dashboard') }}" class="btn btn-ghost">View Dashboard</a>
      </div>
    </div>
 
    <div class="hero-visual reveal">
      <div class="dash-mock">
 
        <div class="dash-mock-head d-flex align-items-center justify-content-between">
          <div class="dash-dots"><span></span><span></span><span></span></div>
          <div class="dash-title d-flex align-items-center">Admin Overview <span class="dash-live d-inline-flex align-items-center"><i class="fa-solid fa-circle"></i> Live</span></div>
        </div>
 
        <div class="dash-kpis d-grid ">
          <div class="dash-kpi d-flex flex-column">
            <span class="k-label">Total Leads</span>
            <span class="k-value">2,438</span>
            <svg class="k-spark" viewBox="0 0 60 20" preserveAspectRatio="none"><polyline points="0,16 10,14 20,15 30,9 40,10 50,4 60,6"/></svg>
          </div>
          <div class="dash-kpi d-flex flex-column">
            <span class="k-label">New Leads</span>
            <span class="k-value">186</span>
            <svg class="k-spark" viewBox="0 0 60 20" preserveAspectRatio="none"><polyline points="0,12 10,13 20,8 30,10 40,5 50,7 60,3"/></svg>
          </div>
          <div class="dash-kpi d-flex flex-column">
            <span class="k-label">Today's Follow-ups</span>
            <span class="k-value">37</span>
            <svg class="k-spark" viewBox="0 0 60 20" preserveAspectRatio="none"><polyline points="0,8 10,10 20,6 30,11 40,9 50,13 60,10"/></svg>
          </div>
          <div class="dash-kpi d-flex flex-column">
            <span class="k-label">Closed Deals</span>
            <span class="k-value">64</span>
            <svg class="k-spark" viewBox="0 0 60 20" preserveAspectRatio="none"><polyline points="0,15 10,11 20,12 30,7 40,8 50,4 60,2"/></svg>
          </div>
        </div>
 
        <div class="dash-row d-grid">
          <div class="dash-card dash-pie d-flex flex-column">
            <span class="dash-card-label">Lead Status</span>
            <div class="pie d-flex align-items-center justify-content-center position-relative" style="--p1:38%; --p2:64%; --p3:100%;">
              <span class="pie-center d-flex flex-column align-items-center position-relative">742<small>Leads</small></span>
            </div>
            <div class="pie-legend d-grid">
              <span class="d-flex align-items-center"><i style="background:var(--color-primary)"></i>New 38%</span>
              <span class="d-flex align-items-center"><i style="background:var(--color-accent)"></i>In progress 26%</span>
              <span class="d-flex align-items-center"><i style="background:var(--color-teal)"></i>Closed 36%</span>
            </div>
          </div>
 
          <div class="dash-card dash-bars d-flex flex-column">
            <span class="dash-card-label">Monthly Sales</span>
            <div class="bars d-flex align-items-end">
              <div class="bar-col d-flex flex-column align-items-center justify-content-end"><i style="--h:38%"></i><em>Feb</em></div>
              <div class="bar-col d-flex flex-column align-items-center justify-content-end"><i style="--h:52%"></i><em>Mar</em></div>
              <div class="bar-col d-flex flex-column align-items-center justify-content-end"><i style="--h:46%"></i><em>Apr</em></div>
              <div class="bar-col d-flex flex-column align-items-center justify-content-end"><i style="--h:70%"></i><em>May</em></div>
              <div class="bar-col d-flex flex-column align-items-center justify-content-end"><i style="--h:63%"></i><em>Jun</em></div>
              <div class="bar-col is-active d-flex flex-column align-items-center justify-content-end"><i style="--h:88%"></i><em>Jul</em></div>
            </div>
          </div>
 
          <div class="dash-card dash-activity d-flex flex-column">
            <span class="dash-card-label">Recent Activity</span>
            <ul class="d-grid">
              <li class="d-flex align-items-start"><i class="fa-solid fa-user-plus"></i> New lead <strong>Rahul Sharma</strong> added <time class="ms-auto">2m</time></li>
              <li class="d-flex align-items-start"><i class="fa-solid fa-phone"></i> Priya called by <strong>Ankit</strong> <time class="ms-auto">18m</time></li>
              <li class="d-flex align-items-start"><i class="fa-solid fa-circle-check"></i> Deal closed with <strong>Verma Traders</strong> <time class="ms-auto">1h</time></li>
              <li class="d-flex align-items-start"><i class="fa-solid fa-calendar"></i> Site visit scheduled <time class="ms-auto">3h</time></li>
            </ul>
          </div>
        </div>
 
      </div>
    </div>
 
  </div>
</header>




 
<!-- ============ WORKFLOW ============ -->
<section class="workflow-section position-relative" id="workflow">
  <div class="wrap">
    <div class="section-head">
      <div class="eyebrow">HOW IT WORKS</div>
      <h2 class="serif">Every lead follows <em>one clear path.</em></h2>
      <p>From the moment a lead lands to the moment a deal closes, Whitehat keeps every step visible — to the admin, and to the rep who owns it.</p>
    </div>
 
    <div class="flow-row d-flex align-items-stretch">
      <div class="flow-card">
        <div class="flow-icon d-inline-flex align-items-center justify-content-center"><i class="fa-solid fa-inbox"></i></div>
        <h3>Lead Added</h3>
        <p>Captured from forms, calls, or manual entry into one shared store.</p>
      </div>
      <div class="flow-arrow d-flex align-items-center"><i class="fa-solid fa-chevron-right"></i></div>
      <div class="flow-card">
        <div class="flow-icon d-inline-flex align-items-center justify-content-center"><i class="fa-solid fa-user-tag"></i></div>
        <h3>Assigned to Rep</h3>
        <p>Admin routes each lead to the right sales person in one click.</p>
      </div>
      <div class="flow-arrow d-flex align-items-center"><i class="fa-solid fa-chevron-right"></i></div>
      <div class="flow-card">
        <div class="flow-icon d-inline-flex align-items-center justify-content-center"><i class="fa-solid fa-calendar-check"></i></div>
        <h3>Follow-up Scheduled</h3>
        <p>Next call, visit, or meeting is set and lands on today's list.</p>
      </div>
      <div class="flow-arrow d-flex align-items-center"><i class="fa-solid fa-chevron-right"></i></div>
      <div class="flow-card">
        <div class="flow-icon d-inline-flex align-items-center justify-content-center"><i class="fa-solid fa-comments"></i></div>
        <h3>Customer Contacted</h3>
        <p>Every call and note is logged against the lead automatically.</p>
      </div>
      <div class="flow-arrow d-flex align-items-center"><i class="fa-solid fa-chevron-right"></i></div>
      <div class="flow-card flow-card-end">
        <div class="flow-icon d-inline-flex align-items-center justify-content-center"><i class="fa-solid fa-flag-checkered"></i></div>
        <h3>Deal Closed / Lost</h3>
        <p class="deal-closed-para">Outcome recorded, feeding straight into team reports.</p>
      </div>
    </div>
  </div>
</section>
 
<!-- ============ FEATURES ============ -->
<section class="features-section" id="features">
  <div class="wrap">
    <div class="section-head">
      <div class="eyebrow">WHAT'S INSIDE</div>
      <h2 class="serif">Six tools, <em>one CRM.</em></h2>
      <p>Everything an admin and a sales team need day to day, without switching between five different apps.</p>
    </div>
 
    <div class="feat-grid-6 d-grid">
      <div class="feat-card d-flex flex-column overflow-hidden">
       <div class="feat-card-media overflow-hidden position-relative">
  
    <img src="{{ asset('frontend/images/crm_lead_app.png') }}" alt="Lead Management Android CRM App">
</div>
        <h3>Lead Management</h3>
        <ul class="d-grid">
          <li class="position-relative">Store complete customer information</li>
          <li class="position-relative">Search and filter leads instantly</li>
          <li class="position-relative">Update lead status as it changes</li>
        </ul>
      </div>
      <div class="feat-card d-flex flex-column overflow-hidden">
        <div class="feat-card-media overflow-hidden position-relative">
          <img src="{{ asset('frontend/images/crm_team_app.png')}}" alt="Sales Team Management Android CRM App">
        </div>
        <h3>Sales Team Management</h3>
        <ul class="d-grid">
          <li class="position-relative">Manage every sales representative</li>
          <li class="position-relative">Assign leads based on load or region</li>
          <li class="position-relative">View active assignments at a glance</li>
        </ul>
      </div>
      <div class="feat-card d-flex flex-column overflow-hidden">
        <div class="feat-card-media overflow-hidden position-relative">
          <img src="{{ asset('frontend/images/crm_followup_app.png')}}" alt="Daily Follow-up Tracking Android CRM App">
        </div>
        <h3>Daily Follow-up Tracking</h3>
        <ul class="d-grid">
          <li class="position-relative">Track every pending follow-up</li>
          <li class="position-relative">Never miss a scheduled customer call</li>
          <li class="position-relative">Automatic reminders before due time</li>
        </ul>
      </div>
      <div class="feat-card d-flex flex-column overflow-hidden">
        <div class="feat-card-media overflow-hidden position-relative">
          <img src="{{ asset('frontend/images/crm_dashboard_app.png')}}" alt="Admin Dashboard Android CRM App">
        </div>
        <h3>Admin Dashboard</h3>
        <ul class="d-grid">
          <li class="position-relative">View company-wide performance</li>
          <li class="position-relative">Monitor all sales activity live</li>
          <li class="position-relative">See lead distribution across reps</li>
        </ul>
      </div>
      <div class="feat-card d-flex flex-column overflow-hidden">
        <div class="feat-card-media overflow-hidden position-relative">
          <img src="{{ asset('frontend/images/crm_analytics_app.png')}}" alt="Reports &amp; Analytics Android CRM App">
        </div>
        <h3>Reports &amp; Analytics</h3>
        <ul class="d-grid">
          <li class="position-relative">Sales and conversion reports</li>
          <li class="position-relative">Follow-up completion reports</li>
          <li class="position-relative">Performance charts by rep or team</li>
        </ul>
      </div>
      <div class="feat-card d-flex flex-column overflow-hidden">
        <div class="feat-card-media overflow-hidden position-relative">
          <img src="{{ asset('frontend/images/crm_timeline_app.png')}}" alt="Activity Timeline Android CRM App">
        </div>
        <h3>Activity Timeline</h3>
        <ul class="d-grid">
          <li class="position-relative">Every action gets recorded</li>
          <li class="position-relative">Know who updated what, and when</li>
          <li class="position-relative">Full audit trail per lead</li>
        </ul>
      </div>
    </div>
  </div>
</section>
 
<!-- ============ USER ROLES ============ -->
<section class="roles-section" id="roles">
  <div class="wrap">
    <div class="section-head">
      <div class="eyebrow">TWO DASHBOARDS, ONE SYSTEM</div>
      <h2 class="serif">Built differently for <em>admins and reps.</em></h2>
      <p>Admins see the whole business. Sales people see exactly what's theirs to do today. No clutter, no confusion.</p>
    </div>
 
    <div class="roles-grid d-grid">
      <div class="role-card d-flex align-items-center">
        <div class="role-phone-wrapper d-flex justify-content-center">
          <div class="phone-mockup d-flex flex-column overflow-hidden position-relative">
            <div class="phone-notch d-flex align-items-center justify-content-center position-absolute">
              <span class="notch-camera"></span>
              <span class="notch-speaker"></span>
            </div>
            <div class="phone-screen position-relative overflow-hidden">
              <video class="phone-video-player d-block" autoplay loop muted playsinline>
                <source src="{{ asset('frontend/images/crmvideo.mp4')}}" type="video/mp4">
                Your browser does not support HTML5 video.
              </video>
              <div class="phone-overlay position-absolute d-flex justify-content-center">
                <span class="phone-tag d-inline-flex align-items-center"><span class="live-dot"></span> Admin UI</span>
              </div>
            </div>
          </div>
        </div>
        <div class="role-body">
          <span class="role-tag d-inline-block">Admin</span>
          <h3>Full control of the business</h3>
          <ul class="role-list d-grid">
            <li class="position-relative">Manage users and permissions</li>
            <li class="position-relative">Create and import new leads</li>
            <li class="position-relative">Assign leads to sales reps</li>
            <li class="position-relative">View company-wide reports</li>
            <li class="position-relative">Monitor the entire sales team</li>
            <li class="position-relative">Track conversions across reps</li>
            <li class="position-relative">Export reports for review</li>
          </ul>
        </div>
      </div>
 
      <div class="role-card d-flex align-items-center">
        <div class="role-phone-wrapper d-flex justify-content-center">
          <div class="phone-mockup d-flex flex-column overflow-hidden position-relative">
            <div class="phone-notch d-flex align-items-center justify-content-center position-absolute">
              <span class="notch-camera"></span>
              <span class="notch-speaker"></span>
            </div>
            <div class="phone-screen position-relative overflow-hidden">
              <video class="phone-video-player d-block" autoplay loop muted playsinline>
                <source src="{{ asset('frontend/images/crmvideo.mp4')}}" type="video/mp4">
                Your browser does not support HTML5 video.
              </video>
              <div class="phone-overlay position-absolute d-flex justify-content-center">
                <span class="phone-tag tag-alt d-inline-flex align-items-center"><span class="live-dot"></span> Sales Rep UI</span>
              </div>
            </div>
          </div>
        </div>
        <div class="role-body">
          <span class="role-tag role-tag-alt d-inline-block">Sales Person</span>
          <h3>A clear list, every day</h3>
          <ul class="role-list d-grid">
            <li class="position-relative">View only assigned leads</li>
            <li class="position-relative">Update lead status after every call</li>
            <li class="position-relative">Add notes after each follow-up</li>
            <li class="position-relative">Schedule the next follow-up</li>
            <li class="position-relative">Mark a lead closed or lost</li>
            <li class="position-relative">Log daily activity updates</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>
 
<!-- ============ DAILY TRACKING ============ -->
<section class="tracking-section" id="tracking">
  <div class="wrap">
    <div class="section-head">
      <div class="eyebrow">THE DAILY DRIVER</div>
      <h2 class="serif">The one screen every rep <em>opens first.</em></h2>
      <p>A rep's whole day — calls, visits, and meetings — laid out in order, with nothing to dig for.</p>
    </div>
 
    <div class="tracking-grid d-grid align-items-start">
      <div class="tracking-timeline reveal">
        <div class="tt-head d-flex align-items-baseline justify-content-between">Today's Schedule <span>Tuesday, 16 Jul</span></div>
        <ul class="tt-list d-grid position-relative">
          <li class="d-grid align-items-center"><span class="tt-time">09:00</span><span class="tt-dot position-relative"></span><span class="tt-text">Call Rahul about renewal</span></li>
          <li class="d-grid align-items-center"><span class="tt-time">11:00</span><span class="tt-dot position-relative"></span><span class="tt-text">Site visit — Sector 62 office</span></li>
          <li class="d-grid align-items-center"><span class="tt-time">13:30</span><span class="tt-dot position-relative"></span><span class="tt-text">Follow-up with Priya on quote</span></li>
          <li class="d-grid align-items-center"><span class="tt-time">16:00</span><span class="tt-dot position-relative"></span><span class="tt-text">Team review meeting</span></li>
          <li class="d-grid align-items-center"><span class="tt-time">18:00</span><span class="tt-dot position-relative"></span><span class="tt-text">Update CRM with today's notes</span></li>
        </ul>
      </div>
 
      <div class="tracking-stats d-grid reveal">
        <div class="tstat d-flex flex-column"><span class="tstat-icon d-inline-flex align-items-center justify-content-center"><i class="fa-solid fa-circle-check"></i></span><span class="tstat-num">12</span><span class="tstat-label">Completed Tasks</span></div>
        <div class="tstat d-flex flex-column"><span class="tstat-icon d-inline-flex align-items-center justify-content-center"><i class="fa-solid fa-hourglass-half"></i></span><span class="tstat-num">5</span><span class="tstat-label">Pending Tasks</span></div>
        <div class="tstat d-flex flex-column"><span class="tstat-icon d-inline-flex align-items-center justify-content-center"><i class="fa-solid fa-phone"></i></span><span class="tstat-num">9</span><span class="tstat-label">Today's Calls</span></div>
        <div class="tstat d-flex flex-column"><span class="tstat-icon d-inline-flex align-items-center justify-content-center"><i class="fa-solid fa-people-arrows"></i></span><span class="tstat-num">2</span><span class="tstat-label">Today's Meetings</span></div>
      </div>
    </div>
  </div>
</section>
 

<!-- ============ LEAD PIPELINE (KANBAN) ============ -->
<section class="pipeline-section" id="pipeline">
  <div class="wrap">
    <div class="section-head">
      <div class="eyebrow">THE PIPELINE</div>
      <h2 class="serif">Watch a lead move, <em>stage by stage.</em></h2>
      <p>A live board of where every lead stands right now — drag-and-drop simple for reps, fully visible for admins.</p>
    </div>
 
    <div class="kanban d-grid align-items-stretch">
      <div class="kanban-col d-flex flex-column overflow-hidden position-relative reveal">
        <div class="kanban-head d-flex align-items-center justify-content-between">New Leads <span>8</span></div>
        <div class="klead-list d-flex flex-column">
          <div class="klead"><b>Rahul Sharma</b><small class="d-block">Website enquiry</small></div>
          <div class="klead"><b>Neha Gupta</b><small class="d-block">Referral</small></div>
          <div class="klead"><b>Mohit Rana</b><small class="d-block">Cold call</small></div>
        </div>
      </div>
      <div class="kanban-col d-flex flex-column overflow-hidden position-relative reveal">
        <div class="kanban-head d-flex align-items-center justify-content-between">Contacted <span>6</span></div>
        <div class="klead-list d-flex flex-column">
          <div class="klead"><b>Priya Mehta</b><small class="d-block">Called once</small></div>
          <div class="klead"><b>Sanjay Kumar</b><small class="d-block">WhatsApp reply</small></div>
        </div>
      </div>
      <div class="kanban-col d-flex flex-column overflow-hidden position-relative reveal">
        <div class="kanban-head d-flex align-items-center justify-content-between">Interested <span>5</span></div>
        <div class="klead-list d-flex flex-column">
          <div class="klead"><b>Verma Traders</b><small class="d-block">Wants quote</small></div>
          <div class="klead"><b>Anita Joshi</b><small class="d-block">Comparing options</small></div>
        </div>
      </div>
      <div class="kanban-col d-flex flex-column overflow-hidden position-relative reveal">
        <div class="kanban-head d-flex align-items-center justify-content-between">Site Visit <span>4</span></div>
        <div class="klead-list d-flex flex-column">
          <div class="klead"><b>Deepak Singh</b><small class="d-block">Visit tomorrow</small></div>
          <div class="klead"><b>Kavita Rao</b><small class="d-block">Visit confirmed</small></div>
        </div>
      </div>
      <div class="kanban-col d-flex flex-column overflow-hidden position-relative reveal">
        <div class="kanban-head d-flex align-items-center justify-content-between">Negotiation <span>3</span></div>
        <div class="klead-list d-flex flex-column">
          <div class="klead"><b>Alok Traders</b><small class="d-block">Discussing price</small></div>
        </div>
      </div>
      <div class="kanban-col kanban-col-closed d-flex flex-column overflow-hidden position-relative reveal">
        <div class="kanban-head d-flex align-items-center justify-content-between">Closed <span>64</span></div>
        <div class="klead-list d-flex flex-column">
          <div class="klead klead-won"><b>Suresh Yadav</b><small class="d-block">Deal won</small></div>
          <div class="klead klead-won"><b>R. Enterprises</b><small class="d-block">Deal won</small></div>
        </div>
      </div>
    </div>
  </div>
</section>
 
<!-- ============ DASHBOARD PREVIEW ============ -->
<section class="dashboard-section position-relative" id="dashboard">
  <div class="wrap">
    <div class="section-head section-head-light">
      <div class="eyebrow">THE FULL PICTURE</div>
      <h2 class="serif">Everything the admin <em>needs, on one page.</em></h2>
      <p>Nine widgets, one dashboard — revenue, leads, reps, and reminders, all live.</p>
    </div>
 
    <div class="widget-grid d-grid">
      <!-- 1. Revenue Widget (Featured Wide) -->
      <div class="widget d-flex flex-column position-relative overflow-hidden reveal widget-wide widget-featured">
        <div class="widget-top d-flex align-items-start justify-content-between">
          <div>
            <span class="widget-label d-block">Revenue Overview</span>
            <span class="widget-sub-label d-block">Current Q3 Performance</span>
          </div>
          <div class="widget-header-right d-flex align-items-center">
            <span class="widget-pill d-inline-flex align-items-center pill-accent">+18.2% YoY</span>
            <div class="widget-icon d-inline-flex align-items-center justify-content-center icon-accent"><i class="fa-solid fa-chart-line"></i></div>
          </div>
        </div>
        <div class="widget-main-metric d-flex align-items-baseline justify-content-between">
          <strong class="widget-value">₹18.6L <small class="widget-unit">INR</small></strong>
          <span class="widget-trend d-inline-flex align-items-center trend-up"><i class="fa-solid fa-arrow-trend-up"></i> ₹2.4L above target</span>
        </div>
        <div class="widget-chart-wrapper">
          <div class="widget-target-line" title="Monthly Target: ₹2.5L"><span>Target</span></div>
          <div class="widget-bars-modern">
            <div class="bar-col"><div class="bar" style="--h:45%"></div><span>Jan</span></div>
            <div class="bar-col"><div class="bar" style="--h:60%"></div><span>Feb</span></div>
            <div class="bar-col"><div class="bar" style="--h:52%"></div><span>Mar</span></div>
            <div class="bar-col"><div class="bar" style="--h:78%"></div><span>Apr</span></div>
            <div class="bar-col"><div class="bar" style="--h:68%"></div><span>May</span></div>
            <div class="bar-col"><div class="bar" style="--h:85%"></div><span>Jun</span></div>
            <div class="bar-col bar-active"><div class="bar" style="--h:95%"></div><span>Jul</span></div>
          </div>
        </div>
      </div>

      <!-- 2. Monthly Leads Widget -->
      <div class="widget d-flex flex-column position-relative overflow-hidden reveal widget-card-teal">
        <div class="widget-top d-flex align-items-start justify-content-between">
          <span class="widget-label d-block">Monthly Leads</span>
          <div class="widget-icon d-inline-flex align-items-center justify-content-center icon-teal"><i class="fa-solid fa-user-plus"></i></div>
        </div>
        <strong class="widget-value">186</strong>
        <div class="widget-trend d-inline-flex align-items-center trend-up"><i class="fa-solid fa-arrow-up"></i> 12% vs last month</div>
        <div class="widget-progress-box">
          <div class="progress-meta"><span>Goal: 240</span><span>78%</span></div>
          <div class="progress-bar"><div class="progress-fill" style="width: 78%"></div></div>
        </div>
      </div>

      <!-- 3. Top Performer Widget (Gold Glow) -->
      <div class="widget d-flex flex-column position-relative overflow-hidden reveal widget-glow-gold">
        <div class="widget-top d-flex align-items-start justify-content-between">
          <span class="widget-label d-block">Top Performer</span>
          <div class="widget-icon d-inline-flex align-items-center justify-content-center icon-gold"><i class="fa-solid fa-crown"></i></div>
        </div>
        <div class="performer-box">
          <div class="performer-avatar">
            <span>AR</span>
            <i class="online-badge"></i>
          </div>
          <div class="performer-details">
            <strong class="widget-value-sm">Ankit R.</strong>
            <span class="performer-role">Senior Sales Exec</span>
          </div>
        </div>
        <div class="performer-metrics">
          <div class="p-metric">
            <strong>14</strong>
            <span>Deals Won</span>
          </div>
          <div class="p-divider"></div>
          <div class="p-metric">
            <strong>₹4.2L</strong>
            <span>Revenue</span>
          </div>
        </div>
      </div>

      <!-- 4. Upcoming Follow-ups Widget -->
      <div class="widget d-flex flex-column position-relative overflow-hidden reveal widget-card-sky">
        <div class="widget-top d-flex align-items-start justify-content-between">
          <span class="widget-label d-block">Follow-Ups Due</span>
          <div class="widget-icon d-inline-flex align-items-center justify-content-center icon-sky"><i class="fa-solid fa-phone-volume"></i></div>
        </div>
        <strong class="widget-value">37</strong>
        <div class="widget-trend d-inline-flex align-items-center trend-urgent"><i class="fa-regular fa-clock"></i> Due next 24 hrs</div>
        <div class="followup-breakdown">
          <span class="f-pill f-urgent"><i class="fa-solid fa-circle-exclamation"></i> 8 High Priority</span>
          <span class="f-pill"><i class="fa-solid fa-check-double"></i> 29 Regular</span>
        </div>
      </div>

      <!-- 5. Conversion Rate Widget -->
      <div class="widget d-flex flex-column position-relative overflow-hidden reveal widget-card-conversion">
        <div class="widget-top d-flex align-items-start justify-content-between">
          <span class="widget-label d-block">Conversion Rate</span>
          <div class="widget-icon d-inline-flex align-items-center justify-content-center icon-teal"><i class="fa-solid fa-bullseye"></i></div>
        </div>
        <div class="ring-display">
          <div class="widget-ring-modern" style="--val:28%">
            <div class="ring-inner">
              <strong>28%</strong>
              <span>Avg Win</span>
            </div>
          </div>
          <div class="ring-meta">
            <div><strong class="text-accent">52</strong> Won</div>
            <div><strong>186</strong> Total</div>
          </div>
        </div>
      </div>

      <!-- 6. Notifications Widget -->
      <div class="widget d-flex flex-column position-relative overflow-hidden reveal widget-card-amber">
        <div class="widget-top d-flex align-items-start justify-content-between">
          <span class="widget-label d-block">Live Alerts</span>
          <div class="widget-icon d-inline-flex align-items-center justify-content-center icon-amber"><i class="fa-solid fa-bell"></i></div>
        </div>
        <strong class="widget-value">5 <small class="text-sub">New</small></strong>
        <div class="notif-list">
          <div class="notif-item alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span><strong>2 Overdue</strong> follow-ups requiring immediate action</span>
          </div>
          <div class="notif-item alert-info">
            <i class="fa-solid fa-circle-info"></i>
            <span><strong>3 Leads</strong> assigned to your queue today</span>
          </div>
        </div>
      </div>

      <!-- 7. Recent Activities Widget (Wide) -->
      <div class="widget d-flex flex-column position-relative overflow-hidden reveal widget-wide widget-card-primary">
        <div class="widget-top d-flex align-items-start justify-content-between">
          <span class="widget-label d-block">Recent Activities Feed</span>
          <span class="live-pulse"><i class="pulse-circle"></i> Live Updates</span>
        </div>
        <ul class="widget-activity-modern">
          <li>
            <div class="act-avatar bg-primary">PR</div>
            <div class="act-content">
              <strong>Lead assigned to Priya</strong>
              <span>Warm inbound inquiry • Enterprise Tier</span>
            </div>
            <time class="act-time">5m ago</time>
          </li>
          <li>
            <div class="act-avatar bg-accent">VT</div>
            <div class="act-content">
              <strong>Deal closed — Verma Traders</strong>
              <span>₹1.4L annual contract finalized</span>
            </div>
            <time class="act-time">1h ago</time>
          </li>
          <li>
            <div class="act-avatar bg-teal">WS</div>
            <div class="act-content">
              <strong>New lead from pricing page</strong>
              <span>Demo requested by CFO of Apex Group</span>
            </div>
            <time class="act-time">2h ago</time>
          </li>
        </ul>
      </div>

      <!-- 8. Calendar Widget -->
      <div class="widget reveal widget-card-sky">
        <div class="widget-top">
          <span class="widget-label">Schedule</span>
          <div class="widget-icon icon-primary"><i class="fa-regular fa-calendar-check"></i></div>
        </div>
        <div class="cal-header">
          <strong class="widget-value-sm">Today's Agenda</strong>
          <span class="cal-badge">4 events</span>
        </div>
        <div class="widget-weekstrip-modern">
          <div class="week-day"><span>M</span><strong>14</strong></div>
          <div class="week-day"><span>T</span><strong>15</strong></div>
          <div class="week-day day-active"><span>W</span><strong>16</strong><i class="day-dot"></i></div>
          <div class="week-day"><span>T</span><strong>17</strong></div>
          <div class="week-day"><span>F</span><strong>18</strong></div>
          <div class="week-day"><span>S</span><strong>19</strong></div>
          <div class="week-day"><span>S</span><strong>20</strong></div>
        </div>
      </div>
    </div>
  </div>
</section>
 
<!-- ============ WHY CHOOSE ============ -->
<section class="why-section" id="why">
  <div class="wrap">
    <div class="section-head">
      <div class="eyebrow">WHY TEAMS SWITCH</div>
      <h2 class="serif">Built for the follow-up, <em>not just the form.</em></h2>
    </div>
 
    <div class="why-grid d-grid">
      <div class="why-card">
        <div class="why-icon d-inline-flex align-items-center justify-content-center"><i class="fa-solid fa-arrow-trend-up"></i></div>
        <h3>Increase Sales</h3>
        <p>Manage every lead efficiently so nothing goes cold before a rep even sees it.</p>
      </div>
      <div class="why-card">
        <div class="why-icon d-inline-flex align-items-center justify-content-center"><i class="fa-solid fa-people-carry-box"></i></div>
        <h3>Better Team Productivity</h3>
        <p>Track daily work across the whole team from a single admin view.</p>
      </div>
      <div class="why-card">
        <div class="why-icon d-inline-flex align-items-center justify-content-center"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <h3>Faster Follow-ups</h3>
        <p>Automatic reminders mean no opportunity is ever missed.</p>
      </div>
      <div class="why-card">
        <div class="why-icon d-inline-flex align-items-center justify-content-center"><i class="fa-solid fa-chart-pie"></i></div>
        <h3>Real-Time Reports</h3>
        <p>Monitor conversions, pipeline health, and rep performance from one place.</p>
      </div>
    </div>
  </div>
</section>
 
<!-- ============ STATS ============ -->
<section class="stats-section" id="stats">
  <div class="wrap stats-row d-flex align-items-center justify-content-around">
    <div class="stat-item d-flex flex-column align-items-center text-center"><span class="stat-num d-inline-block" data-count="10000">0</span><span class="stat-suffix">+</span><span class="stat-label">Leads Managed</span></div>
    <div class="stat-sep"></div>
    <div class="stat-item d-flex flex-column align-items-center text-center"><span class="stat-num d-inline-block" data-count="250">0</span><span class="stat-suffix">+</span><span class="stat-label">Sales Users</span></div>
    <div class="stat-sep"></div>
    <div class="stat-item d-flex flex-column align-items-center text-center"><span class="stat-num d-inline-block" data-count="95">0</span><span class="stat-suffix">%</span><span class="stat-label">Follow-up Success</span></div>
    <div class="stat-sep"></div>
    <div class="stat-item d-flex flex-column align-items-center text-center"><span class="stat-num-static d-inline-block">24/7</span><span class="stat-label">Access, Anywhere</span></div>
  </div>
</section>
 
<!-- ============ FINAL CTA / GET IN TOUCH ============ -->
<section class="final-cta-section get-in-touch-section" id="demo">
  <div class="wrap get-in-touch-inner d-grid align-items-center position-relative reveal">
    <!-- Left Column: Text & Features -->
    <div class="get-in-touch-left position-relative">
      <div class="get-in-touch-eyebrow d-flex align-items-center">── GET IN TOUCH</div>
      <h2 class="serif">Let's help you run a sharper sales team.</h2>
      <p class="get-in-touch-sub">Share a few details and our team will set up a personalized walkthrough of Whitehat CRM for your business.</p>
      
      <div class="get-in-touch-features d-flex flex-column">
        <div class="touch-feat-item d-flex align-items-start">
          <div class="touch-feat-icon d-flex align-items-center justify-content-center"><i class="fa-solid fa-user-tie"></i></div>
          <div class="touch-feat-text">
            <h3>Expert Guidance</h3>
            <p>Personalized assistance to set up your pipeline.</p>
          </div>
        </div>
        
        <div class="touch-feat-item d-flex align-items-start">
          <div class="touch-feat-icon d-flex align-items-center justify-content-center"><i class="fa-solid fa-desktop"></i></div>
          <div class="touch-feat-text">
            <h3>Live Product Walkthrough</h3>
            <p>See the admin and sales-rep views in action.</p>
          </div>
        </div>
        
        <div class="touch-feat-item d-flex align-items-start">
          <div class="touch-feat-icon d-flex align-items-center justify-content-center"><i class="fa-solid fa-tags"></i></div>
          <div class="touch-feat-text">
            <h3>Latest Plans & Offers</h3>
            <p>Current pricing and onboarding offers for your team size.</p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Right Column: Login / Demo Form -->
   <div class="get-in-touch-right position-relative w-100">
  <div class="get-in-touch-card w-100">
    <div class="get-in-touch-card-header">
      <h3>Request a demo</h3>
      <p>We reply the same working day.</p>
    </div>
    
   <?php
$formIdSuffix = isset($formInstanceId) ? '_' . $formInstanceId : '_' . substr(md5(uniqid()), 0, 4);
?>
    
<form class="modal-form compact-form login-demo-form d-flex flex-column" action="process_form.php" method="post">
  <div class="form-row-2 d-grid">
    <div class="form-group d-flex flex-column">
      <label class="d-flex align-items-center" for="fullName<?php echo $formIdSuffix; ?>">Full Name</label>
      <input class="w-100" type="text" id="fullName<?php echo $formIdSuffix; ?>" name="fullName" required placeholder="e.g. Rahul Sharma">
    </div>

    <div class="form-group d-flex flex-column">
      <label class="d-flex align-items-center" for="mobile<?php echo $formIdSuffix; ?>">Phone Number</label>
      <input class="w-100" type="tel" id="mobile<?php echo $formIdSuffix; ?>" name="mobile" required placeholder="+91 98765 43210">
    </div>
  </div>

  <div class="form-row-2 d-grid">
    <div class="form-group d-flex flex-column">
      <label class="d-flex align-items-center" for="email<?php echo $formIdSuffix; ?>">Business Email</label>
      <input class="w-100" type="email" id="email<?php echo $formIdSuffix; ?>" name="email" required placeholder="name@company.com">
    </div>

    <div class="form-group d-flex flex-column">
      <label class="d-flex align-items-center" for="bhkSize<?php echo $formIdSuffix; ?>">Role / Team Size</label>
      <select class="w-100" id="bhkSize<?php echo $formIdSuffix; ?>" name="bhkSize" required>
        <option value="" disabled selected>Select option...</option>
        <option value="Admin (1-5 reps)">Admin (1-5 reps)</option>
        <option value="Manager (6-20 reps)">Manager (6-20 reps)</option>
        <option value="Director (21-50 reps)">Director (21-50 reps)</option>
        <option value="Enterprise (50+ reps)">Enterprise (50+ reps)</option>
      </select>
    </div>
  </div>

  <div class="form-group d-flex flex-column">
    <label class="d-flex align-items-center" for="message<?php echo $formIdSuffix; ?>">Requirements (Optional)</label>
    <input class="w-100" type="text" id="message<?php echo $formIdSuffix; ?>" name="message" placeholder="Any specific feature focus or workflow?">
  </div>

  <button type="submit" class="btn btn-gold modal-submit-btn compact-submit-btn w-100 d-flex align-items-center justify-content-center">
    <span>Request Demo & Login</span>
    <i class="fa-solid fa-arrow-right"></i>
  </button>
</form>
    
    <div class="get-in-touch-card-footer text-center">
      <p>Your information is used only to respond to this enquiry.</p>
    </div>
  </div>
</div>
  </div>
</section>

<!-- ============ FOOTER ============ -->
<footer class="site-footer">
  <div class="wrap footer-grid">
    <div class="footer-brand">
      <a href="<?php echo $homeUrl; ?>" class="brand js-home-link">
        <span class="brand-mark"><i class="fa-solid fa-diagram-project"></i></span>
        <span class="brand-word"><?php echo $productName; ?></span>
      </a>
      <p>The CRM built around the follow-up — leads, reps, and reports in one place.</p>
      <div class="footer-brand-socials">
        <a href="https://linkedin.com" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="fa-brands fa-linkedin"></i></a>
        <a href="https://twitter.com" target="_blank" rel="noopener" aria-label="Twitter"><i class="fa-brands fa-x-twitter"></i></a>
        <a href="https://instagram.com" target="_blank" rel="noopener" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
      </div>
    </div>
    <div class="footer-col">
      <h4>Product</h4>
      <a href="<?php echo $baseDir; ?>/workflow">Workflow</a>
      <a href="<?php echo $baseDir; ?>/features">Features</a>
      <a href="<?php echo $baseDir; ?>/pipeline">Pipeline</a>
      <a href="<?php echo $baseDir; ?>/dashboard">Dashboard</a>
    </div>
    <div class="footer-col">
      <h4>Roles</h4>
      <a href="<?php echo $baseDir; ?>/roles">Admin</a>
      <a href="<?php echo $baseDir; ?>/roles">Sales Person</a>
      <a href="<?php echo $baseDir; ?>/tracking">Daily Tracking</a>
    </div>
    <div class="footer-col">
      <h4>Company</h4>
      <a href="javascript:void(0)" class="js-trigger-demo">Request Demo</a>
      <a href="<?php echo $baseDir; ?>/contact" class="js-trigger-demo">Contact Us</a>
    </div>
  </div>
  <div class="wrap footer-bottom-row">
    <span>&copy; <?php echo date("Y"); ?> <?php echo $productName; ?>. All rights reserved.</span>
    <div class="footer-bottom-legal">
      <a href="<?php echo $baseDir; ?>/privacy-policy">Privacy Policy</a>
      <span class="legal-separator">|</span>
      <a href="<?php echo $baseDir; ?>/terms-and-conditions">Terms of Use</a>
    </div>
  </div>
</footer>


<!-- ============ DEMO MODAL WITH NEW COMPACT FORM ============ -->
<div class="modal-overlay position-fixed top-0 start-0 end-0 bottom-0 d-flex align-items-center justify-content-center" id="demoModal">
  <div class="modal-container position-relative">
    <button class="modal-close position-absolute d-flex align-items-center justify-content-center" id="modalClose" aria-label="Close modal">&times;</button>

    <div class="modal-header compact-header text-start">
      <h3 class="d-flex align-items-center gap-2 mb-1"><i class="fa-solid fa-bolt text-gold"></i> Access Whitehat CRM</h3>
      <p class="mb-3 text-muted">Launch your instant personalized sandbox demo.</p>
    </div>
    
    <?php
    $formIdSuffix = isset($formInstanceId) ? '_' . $formInstanceId : '_' . substr(md5(uniqid()), 0, 4);
    ?>
    <div class="form-message" style="display: none;"></div>

    <form class="modal-form compact-form login-demo-form d-flex flex-column" action="{{ url('/submit-demo') }}" method="post">
      @csrf
      <div class="form-row-2 d-grid">
        <div class="form-group d-flex flex-column">
          <label class="d-flex align-items-center" for="fullName<?php echo $formIdSuffix; ?>">Full Name</label>
          <input class="w-100" type="text" id="fullName<?php echo $formIdSuffix; ?>" name="fullName" required placeholder="e.g. Rahul Sharma">
        </div>

        <div class="form-group d-flex flex-column">
          <label class="d-flex align-items-center" for="mobile<?php echo $formIdSuffix; ?>">Phone Number</label>
          <input class="w-100" type="tel" id="mobile<?php echo $formIdSuffix; ?>" name="mobile" required placeholder="+91 98765 43210">
        </div>
      </div>

      <div class="form-row-2 d-grid">
        <div class="form-group d-flex flex-column">
          <label class="d-flex align-items-center" for="email<?php echo $formIdSuffix; ?>">Business Email</label>
          <input class="w-100" type="email" id="email<?php echo $formIdSuffix; ?>" name="email" required placeholder="name@company.com">
        </div>

        <div class="form-group d-flex flex-column">
          <label class="d-flex align-items-center" for="bhkSize<?php echo $formIdSuffix; ?>">Role / Team Size</label>
          <select class="w-100" id="bhkSize<?php echo $formIdSuffix; ?>" name="bhkSize" required>
            <option value="" disabled selected>Select option...</option>
            <option value="Admin (1-5 reps)">Admin (1-5 reps)</option>
            <option value="Manager (6-20 reps)">Manager (6-20 reps)</option>
            <option value="Director (21-50 reps)">Director (21-50 reps)</option>
            <option value="Enterprise (50+ reps)">Enterprise (50+ reps)</option>
          </select>
        </div>
      </div>

      <div class="form-group d-flex flex-column">
        <label class="d-flex align-items-center" for="message<?php echo $formIdSuffix; ?>">Requirements (Optional)</label>
        <input class="w-100" type="text" id="message<?php echo $formIdSuffix; ?>" name="message" placeholder="Any specific feature focus or workflow?">
      </div>

      <button type="submit" class="btn btn-gold modal-submit-btn compact-submit-btn w-100 d-flex align-items-center justify-content-center mt-3">
        <span>Request Demo & Login</span>
        <i class="fa-solid fa-arrow-right ms-2"></i>
      </button>
    </form>
  </div>
</div>




<script>
    document.addEventListener("DOMContentLoaded", function () {
  const statNumbers = document.querySelectorAll(".stat-num");
  let animated = false;

  function runCounter() {
    if (animated) return;
    
    statNumbers.forEach((numEl) => {
      const target = +numEl.getAttribute("data-count");
      const duration = 2000; // Animation duration in milliseconds (2 seconds)
      const stepTime = 20; // Frame interval
      const steps = duration / stepTime;
      const increment = target / steps;
      let current = 0;

      const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
          numEl.textContent = target.toLocaleString(); // Format with commas if needed
          clearInterval(timer);
        } else {
          numEl.textContent = Math.floor(current).toLocaleString();
        }
      }, stepTime);
    });

    animated = true;
  }

  // Trigger counter when stats section enters the viewport
  const statsSection = document.getElementById("stats");
  if (statsSection) {
    const observer = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          runCounter();
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.3 }); // Triggers when 30% of the section is visible

    observer.observe(statsSection);
  }
});
</script>


<!-- ============ SCRIPTS ============ -->
<script>
  // ---- Global Modal Handlers & Nav Script ----
  document.addEventListener('DOMContentLoaded', function() {
    const demoModal = document.getElementById('demoModal');
    const modalClose = document.getElementById('modalClose');
    const modalTriggers = document.querySelectorAll('.nav-cta, .nav-cta-mobile, [href*="/demo"], [href*="/contact"], .js-trigger-demo, [href*="/workflow"] + .btn');

    window.showModal = function(e) {
      if (e) e.preventDefault();
      if (!demoModal) return;
      demoModal.classList.add('is-active');
      document.body.style.overflow = 'hidden';
      if (window.history && window.history.replaceState && window.location.pathname.includes('/demo')) {
        window.history.replaceState(null, '', '{{ url("/") }}');
      }
    }

    window.hideModal = function() {
      if (!demoModal) return;
      demoModal.classList.remove('is-active');
      document.body.style.overflow = '';
      const modalForm = demoModal.querySelector('form');
      if (modalForm) modalForm.reset();
      const modalMsg = demoModal.querySelector('.form-message');
      if (modalMsg) {
        modalMsg.style.display = 'none';
        modalMsg.className = 'form-message';
      }
    }

    if (modalTriggers.length > 0) {
      modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', window.showModal);
      });
    }

    if (modalClose) {
      modalClose.addEventListener('click', window.hideModal);
    }

    if (demoModal) {
      demoModal.addEventListener('click', (e) => {
        if (e.target === demoModal) {
          window.hideModal();
        }
      });
    }

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && demoModal && demoModal.classList.contains('is-active')) {
        window.hideModal();
      }
    });

    // ---- Mobile Nav & Smooth Scroll Handler ----
    const navToggle = document.getElementById('navToggle');
    const navLinks  = document.getElementById('navLinks');
    
    if (navToggle && navLinks) {
      const toggleIcon = navToggle.querySelector('i');
      
      function openMenu() {
        navLinks.classList.add('is-open');
        navToggle.classList.add('is-active');
        if (toggleIcon) {
          toggleIcon.classList.remove('fa-bars');
          toggleIcon.classList.add('fa-xmark');
        }
        document.body.style.overflow = 'hidden';
      }
      
      function closeMenu() {
        navLinks.classList.remove('is-open');
        navToggle.classList.remove('is-active');
        if (toggleIcon) {
          toggleIcon.classList.remove('fa-xmark');
          toggleIcon.classList.add('fa-bars');
        }
        document.body.style.overflow = '';
      }
      
      navToggle.addEventListener('click', () => {
        navLinks.classList.contains('is-open') ? closeMenu() : openMenu();
      });
      
      document.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', (e) => {
          const href = link.getAttribute('href');
          if (!href || href.startsWith('http') || href.startsWith('tel') || href.startsWith('mailto')) {
            return;
          }
          
          if (href.includes('/demo') || href.includes('/contact') || link.classList.contains('js-trigger-demo')) {
            e.preventDefault();
            closeMenu();
            window.showModal();
            return;
          }
          
          const sections = ['workflow', 'features', 'roles', 'pipeline', 'dashboard', 'tracking'];
          for (let sec of sections) {
            if (href.endsWith('/' + sec) || href === sec || href.endsWith('section=' + sec)) {
              const target = document.getElementById(sec);
              if (target) {
                e.preventDefault();
                closeMenu();
                setTimeout(() => {
                  const navbarH = document.getElementById('navbar') ? document.getElementById('navbar').offsetHeight : 76;
                  const top = target.getBoundingClientRect().top + window.scrollY - navbarH - 8;
                  window.scrollTo({ top, behavior: 'smooth' });
                  if (window.history && window.history.pushState) {
                    window.history.pushState(null, '', href);
                  }
                }, 120);
                return;
              }
            }
          }
          
          if (link.classList.contains('js-home-link') || link.classList.contains('brand')) {
            const topEl = document.getElementById('top');
            if (topEl) {
              e.preventDefault();
              closeMenu();
              window.scrollTo({ top: 0, behavior: 'smooth' });
              if (window.history && window.history.pushState) {
                window.history.pushState(null, '', href);
              }
              return;
            }
          }
        });
      });

      document.addEventListener('click', (e) => {
        if (navLinks.classList.contains('is-open') &&
            !navLinks.contains(e.target) &&
            !navToggle.contains(e.target)) {
          closeMenu();
        }
      });
    }

    // Auto-scroll or open modal based on URL query/path
    const urlParams = new URLSearchParams(window.location.search);
    let sec = urlParams.get('section');
    if (!sec) {
      const parts = window.location.pathname.split('/').filter(Boolean);
      if (parts.length > 0) {
        const last = parts[parts.length - 1];
        if (['workflow', 'features', 'roles', 'pipeline', 'dashboard', 'tracking', 'demo'].includes(last)) {
          sec = last;
        }
      }
    }
    if (sec === 'demo') {
      window.showModal();
      if (window.history && window.history.replaceState) {
        window.history.replaceState(null, '', '{{ url("/") }}');
      }
      return;
    }
    if (sec) {
      const target = document.getElementById(sec);
      if (target) {
        setTimeout(() => {
          const navbarH = document.getElementById('navbar') ? document.getElementById('navbar').offsetHeight : 76;
          const top = target.getBoundingClientRect().top + window.scrollY - navbarH - 8;
          window.scrollTo({ top, behavior: 'smooth' });
        }, 350);
      }
    }

    // ---- AJAX Form Submission Handler ----
    const allForms = document.querySelectorAll('.login-demo-form');
    allForms.forEach(form => {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn ? submitBtn.innerHTML : '<span>Request Demo & Login</span><i class="fa-solid fa-arrow-right"></i>';
        
        let formMessage = form.previousElementSibling;
        if (!formMessage || !formMessage.classList.contains('form-message')) {
          formMessage = form.parentElement.querySelector('.form-message');
        }
        
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
        }
        
        if (formMessage) {
          formMessage.style.display = 'none';
          formMessage.className = 'form-message';
        }
        
        const formData = new FormData(form);
        
        fetch('{{ url("/submit-demo") }}', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          },
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
          }
          
          if (data.status === 'success' || data.status === true) {
            if (formMessage) {
              formMessage.innerHTML = `<i class="fa-solid fa-circle-check"></i> ${data.message} Redirecting to confirmation...`;
              formMessage.className = 'form-message form-message-success';
              formMessage.style.display = 'block';
            }
            
            setTimeout(() => {
              window.location.href = data.redirect || '{{ url("/thankyou") }}';
            }, 700);
          } else {
            if (formMessage) {
              formMessage.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> ${data.message || 'Something went wrong. Please check your inputs.'}`;
              formMessage.className = 'form-message form-message-error';
              formMessage.style.display = 'block';
            }
          }
        })
        .catch(error => {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
          }
          if (formMessage) {
            formMessage.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Connection error. Please try again.';
            formMessage.className = 'form-message form-message-error';
            formMessage.style.display = 'block';
          }
        });
      });
    });
  });
</script>


@endsection