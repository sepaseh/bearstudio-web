@php
    $settings ??= \App\Support\SiteSettings::publicRecord();
    $site = $settings['site'];
    $brand = $site['brandName'];
    $brandLabel = $brand.' — home';
    $pageTitle = isset($title) ? $title.' — '.$brand : $brand.' — Video Production & Motion Design';
    $description = $canonicalPath === '/' && $site['description'] !== '' ? $site['description'] : $description;
    $canonical = \App\Support\WebsiteSeo::url($canonicalPath);
    $imageUrl = \App\Support\WebsiteSeo::image($socialImage ?? null);
    $jsonLd = \App\Support\WebsiteSeo::structured($site, $canonicalPath, $article ?? null, $imageUrl, $projects ?? []);
    $themeStyle = \App\Support\SiteSettings::themeStyle($settings);
    $navigation = ['/' => 'Home', '/projects' => 'Projects', '/services' => 'Services', '/articles' => 'Articles'];
    $activePath = '/'.explode('/', trim($canonicalPath, '/'))[0];
@endphp
<!doctype html>
<html lang="en" dir="ltr" class="dark font-sans scroll-smooth motion-reduce:scroll-auto selection:bg-[#edbd60] selection:text-[#191b18] [scroll-padding-top:84px]" style="{{ $themeStyle }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="{{ \App\Support\WebsiteSeo::allowsIndexing() ? 'index,follow' : 'noindex,nofollow' }}">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="{{ isset($article) ? 'article' : 'website' }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:site_name" content="{{ $brand }}">
    <meta name="twitter:card" content="{{ $imageUrl ? 'summary_large_image' : 'summary' }}">
    @if ($imageUrl)
        <meta property="og:image" content="{{ $imageUrl }}">
        @isset($socialImageAlt)
            <meta property="og:image:alt" content="{{ $socialImageAlt }}">
        @endisset
    @endif
    <script type="application/ld+json">{!! \Illuminate\Support\Js::encode($jsonLd) !!}</script>
    <meta name="theme-color" content="#191b18">
    <link rel="icon" type="image/svg+xml" href="/site-assets/assets/favicon.svg">
    <link rel="stylesheet" href="/site-assets/assets/website.css">
    <script src="/site-assets/assets/website.js" defer></script>
</head>
<body class="bg-(--background) text-(--foreground) font-sans font-medium antialiased tracking-[-.04em] m-0 overflow-x-clip [&.has-open-dialog]:overflow-hidden motion-reduce:[&.has-open-dialog]:overflow-hidden @if ($activePath === '/articles') [--background:var(--site-light-background,#f5f5f5)] [--foreground:var(--site-light-foreground,#0a0a0a)] [--card:var(--site-light-card,#ffffff)] [--muted:var(--site-light-card,#e8e8e8)] [--muted-foreground:var(--site-light-muted-foreground,#686868)] [--border:var(--site-light-border,#0a0a0a1a)] dark:[--background:var(--site-dark-background,#191b18)] dark:[--foreground:var(--site-dark-foreground,#e8e8df)] dark:[--card:var(--site-dark-card,#242821)] dark:[--muted:var(--site-dark-card,#282c25)] dark:[--muted-foreground:var(--site-dark-muted-foreground,#a3a79a)] dark:[--border:var(--site-dark-border,#e8e8df26)] @endif">
<div id="top">
    <a href="#main-content" class="skip-link focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px] z-[100] [background:var(--foreground)] [color:var(--background)] rounded-[.5rem] [padding:.75rem_1rem] fixed top-[1rem] left-[1rem] [transform:translateY(-200%)] [touch-action:manipulation] focus:[transform:translateY(0)] motion-reduce:focus:[transform:translateY(0)]">Skip to content</a>
    <header class="landing-header sticky top-[0] z-[40] [background:var(--background)]">
        <div class="site-container header-inner [width:calc(100%_-_2.5rem)] max-w-[var(--container-site)] mx-[auto] flex h-[60px] items-center justify-between gap-[24px] md:[width:calc(100%_-_3rem)] xl:[width:calc(100%_+_-1_*_clamp(72px,_8.75vw_-_40px,_100px))]">
            <a href="/" aria-label="{{ $brandLabel }}" class="brand-mark focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px] [touch-action:manipulation] inline-flex items-start text-[25px] [font-weight:600] leading-[1] tracking-[-.07em]">@include('shared.brand-mark')</a>
            <nav aria-label="Main navigation" class="hidden [flex:1] justify-between gap-[20px] max-w-[800px] ml-[8%] text-[16px] [color:var(--link)] lg:flex">
                @foreach ($navigation as $path => $label)
                    <a href="{{ $path }}" aria-current="{{ $activePath === $path ? 'page' : 'false' }}" class="focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px] [touch-action:manipulation] hover:[opacity:.55] motion-reduce:hover:[opacity:.55]">{{ $label }}</a>
                @endforeach
            </nav>
            <div class="header-actions flex items-center gap-[12px]">
                <button type="button" id="theme-toggle" aria-label="Toggle color theme" class="[cursor:pointer] [touch-action:manipulation] grid [place-content:center] h-[44px] min-w-[44px] p-[8px] [background:transparent] [color:inherit] [border:0] rounded-[999px] text-[23px] focus-visible:[outline:2px_solid_var(--ring)] focus-visible:[outline-offset:5px] motion-reduce:focus-visible:[outline:2px_solid_var(--ring)] motion-reduce:focus-visible:[outline-offset:5px]">◐</button>
                <button type="button" id="menu-toggle" aria-label="Open navigation menu" aria-haspopup="dialog" aria-expanded="false" aria-controls="menu-dialog" class="[cursor:pointer] [touch-action:manipulation] grid [place-content:center] h-[44px] min-w-[44px] p-[8px] [background:transparent] [color:inherit] [border:0] rounded-[0] w-[60px] gap-[8px] lg:hidden focus-visible:[outline:2px_solid_var(--ring)] focus-visible:[outline-offset:5px] motion-reduce:focus-visible:[outline:2px_solid_var(--ring)] motion-reduce:focus-visible:[outline-offset:5px]">
                    <span class="block w-[48px] h-[1px] [background:currentColor]"></span>
                    <span class="block w-[48px] h-[1px] [background:currentColor]"></span>
                </button>
            </div>
        </div>
    </header>
    <main id="main-content">
        @yield('content')
        @include('shared.contact', ['site' => $site])
    </main>
    <footer class="landing-footer pt-[50px] pb-[24px] md:pt-[100px] xl:pt-[200px]">
        <div class="site-container [width:calc(100%_-_2.5rem)] max-w-[var(--container-site)] mx-auto md:[width:calc(100%_-_3rem)] xl:[width:calc(100%_+_-1_*_clamp(72px,_8.75vw_-_40px,_100px))]">
            <div class="footer-top grid gap-[64px] lg:grid-cols-[minmax(0,1.15fr)_minmax(0,.6fr)_minmax(0,1.25fr)] lg:gap-6 xl:gap-8">
                <div class="min-w-0">
                    <p class="footer-statement max-w-[520px] text-[28px] leading-[1.2] tracking-[-.04em] md:text-[38px]">Good things start<br>with a conversation.</p>
                    @if ($site['contactEmail'] !== null)
                        <a class="text-link focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px] [touch-action:manipulation] inline-flex max-w-full gap-[24px] items-center [overflow-wrap:anywhere] [color:var(--link)] [border-bottom:1px_solid_currentColor] pb-[9px] mt-[28px] text-[16px]"
                           href="{{ \App\Support\SiteSettings::emailLink($site['contactEmail']) }}">
                            {{ $site['contactEmail'] }}
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.5" aria-hidden="true" class="[flex-shrink:0]"><path d="M7 17 17 7M7 7h10v10"></path></svg></a>
                    @endif
                    @if ($site['socialLinks'] !== [])
                        <div class="footer-socials flex flex-wrap gap-[24px] mt-[36px]">
                            @include('shared.social-links', ['links' => $site['socialLinks']])
                        </div>
                    @endif
                </div>
                <div class="footer-right lg:contents">
                    <div class="min-w-0">
                        <p class="footer-nav-label text-[13px] [color:var(--muted-foreground)] mb-[16px]">Navigation</p>
                        <nav aria-label="Footer navigation" class="flex flex-col items-start gap-[10px] text-[22px] leading-[1.2] [color:var(--link)]">
                            @foreach ($navigation as $path => $label)
                                <a href="{{ $path }}" aria-current="{{ $activePath === $path ? 'page' : 'false' }}" class="focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px] [touch-action:manipulation]">{{ $label }}</a>
                            @endforeach
                        </nav>
                    </div>
                    <a href="/" aria-label="{{ $brandLabel }}" class="footer-wordmark focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px] [touch-action:manipulation] block mt-[48px] tracking-[-.08em] lg:mt-0 lg:min-h-0 lg:[container-type:size]">@include('shared.brand-mark', ['footer' => true])</a>
                </div>
            </div>
            <div class="footer-bottom flex flex-wrap justify-between gap-[18px] mt-[64px] pt-[24px] [border-top:1px_solid_var(--border)] [color:var(--muted-foreground)] text-[11px] leading-[1.4] [font-weight:400] md:mt-[100px]">
                <p>© 2026 Bear Studio. All rights reserved.</p>
                <a href="#top" class="focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px] [touch-action:manipulation]">Back to top ↑</a>
            </div>
        </div>
    </footer>
</div>
    <dialog id="menu-dialog" aria-label="Navigation menu" class="menu-dialog fixed [color:var(--foreground)] [background:var(--background)] [border:1px_solid_var(--border)] [overscroll-behavior:contain] [inset:0_0_0_auto] m-[0] w-[min(672px,100%)] h-[100dvh] max-w-[100%] max-h-[100dvh] p-[24px] rounded-[0] sm:p-[40px] backdrop:[background:#000b] backdrop:[backdrop-filter:blur(7px)] motion-reduce:backdrop:[background:#000b] motion-reduce:backdrop:[backdrop-filter:blur(7px)]">
        <div class="menu-dialog-head flex items-center justify-between">
            <a href="/" aria-label="{{ $brandLabel }}" class="brand-mark focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px] [touch-action:manipulation] inline-flex items-start text-[25px] [font-weight:600] leading-[1] tracking-[-.07em]">@include('shared.brand-mark')</a>
            <button type="button" data-close-dialog aria-label="Close navigation menu" class="[cursor:pointer] [touch-action:manipulation] w-[44px] h-[44px] [border:1px_solid_var(--border)] rounded-[50%] text-[28px] [background:none] [color:inherit] focus-visible:[outline:2px_solid_var(--ring)] focus-visible:[outline-offset:5px] motion-reduce:focus-visible:[outline:2px_solid_var(--ring)] motion-reduce:focus-visible:[outline-offset:5px]">×</button>
        </div>
        <nav aria-label="Menu navigation" class="flex flex-col my-[40px] [color:var(--link)]">
            @foreach ($navigation as $path => $label)
                <a href="{{ $path }}" aria-current="{{ $activePath === $path ? 'page' : 'false' }}" class="focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px] [touch-action:manipulation] py-[14px] [border-bottom:1px_solid_var(--border)] text-[clamp(36px,5vw,64px)] leading-[1] tracking-[-.06em]">{{ $label }}</a>
            @endforeach
        </nav>
        @if ($site['contactEmail'] !== null)
            <a href="{{ \App\Support\SiteSettings::emailLink($site['contactEmail']) }}" class="menu-email focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px] [touch-action:manipulation] text-[14px] [color:var(--link)]">{{ $site['contactEmail'] }}</a>
        @endif
    </dialog>
    @yield('dialogs')
</body>
</html>
