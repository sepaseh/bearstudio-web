<section id="home" aria-label="Bear Studio introduction" class="edge-frame hero-surface relative isolate mx-[2px] flex min-h-[744px] flex-col pt-[54px] pb-[40px] text-[#f5f4ef] md:mx-[4px] md:min-h-[calc(100svh_-_62px)] md:pt-[70px] xl:mx-[6px] xl:pt-[90px]">
    <div class="pointer-events-none absolute inset-x-0 top-0 bottom-1 -z-10 isolate overflow-hidden rounded-[25px] bg-[#151614]" aria-hidden="true">
        <div class="absolute -inset-y-[5%] inset-x-0 -z-20 [transform:none]">
            @if ($homeContent !== null)
                <span class="relative block size-full overflow-hidden">
                    <img alt="{{ $homeContent['heroPoster']['alt'] }}" loading="eager" decoding="async" class="object-cover absolute h-[100%] w-[100%] left-[0] top-[0] right-[0] bottom-[0] [color:transparent]" src="{{ $homeContent['heroPoster']['url'] }}" fetchpriority="high">
                    <video id="hero-video" data-preview data-src="{{ $homeContent['heroVideoUrl'] }}" poster="{{ $homeContent['heroPoster']['url'] }}" muted loop playsinline preload="none" aria-hidden="true" class="hero-live-video absolute inset-[0] w-[100%] h-[100%] object-cover pointer-events-none data-[failed=true]:[opacity:0] motion-reduce:data-[failed=true]:[opacity:0]"></video>
                </span>
            @endif
        </div>
        <div class="absolute inset-0 -z-10 bg-black/40"></div>
    </div>

    <div class="site-container flex flex-1 flex-col justify-between gap-16 md:gap-24 [width:calc(100%_-_2.5rem)] max-w-[var(--container-site)] mx-[auto] md:[width:calc(100%_-_3rem)] xl:[width:calc(100%_+_-1_*_clamp(72px,_8.75vw_-_40px,_100px))]">
        <div class="grid gap-[38px] md:grid-cols-4 md:gap-1">
            <div class="md:col-span-3" data-reveal>
                <div class="inline-block">
                    <h1 class="hero-name whitespace-nowrap tracking-[-.075em] text-[clamp(116px,21vw,336px)] [font-weight:600] leading-[.76]">Bear<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-asterisk ml-[.02em] inline size-[.22em] align-top text-bear-gold [flex-shrink:0]" aria-hidden="true"><path d="M12 5v14"></path><path d="m18.065 8.496-12.125 7"></path><path d="m5.94 8.504 12.125 7"></path></svg><span class="sr-only"> Studio</span></h1>
                    <p aria-hidden="true" class="hero-studio mt-3 md:pr-[.85em] md:text-right tracking-[-.06em] [font-size:clamp(35px,_4.5vw,_72px)] [font-weight:600] leading-[1]">Studio</p>
                </div>
            </div>
            <div class="justify-self-end text-[15px] leading-[1.2] tracking-[-0.04em] md:justify-self-start md:pt-[4vw] md:text-base xl:text-lg" data-reveal>
                <div class="space-y-2.5 xl:space-y-3">
                    <p>Video production &amp; editing</p>
                    <p>Animation &amp; motion</p>
                    <p>VFX, AI &amp; immersive</p>
                    <p>Sound &amp; music</p>
                </div>
            </div>
        </div>
        <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-[calc((100%_-_4px)*.54)] hidden md:block">
            <div class="site-container grid grid-cols-4 gap-1 text-white/55 [width:calc(100%_-_2.5rem)] max-w-[var(--container-site)] mx-[auto] md:[width:calc(100%_-_3rem)] xl:[width:calc(100%_+_-1_*_clamp(72px,_8.75vw_-_40px,_100px))]">
                @for ($index = 0; $index < 4; $index++)
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus size-5 [flex-shrink:0]" aria-hidden="true">
                        <path d="M5 12h14"></path><path d="M12 5v14"></path>
                    </svg>
                @endfor
            </div>
        </div>
        <div class="grid items-end gap-[42px] md:grid-cols-4 md:gap-1">
            <div class="order-2 md:order-1 md:col-span-2" data-reveal>
                @if ($homeContent !== null)
                    <p class="max-w-[490px] text-lg leading-[1.2] tracking-[-0.04em] indent-12 md:text-[22px]">{{ $homeContent['heroDescription'] }}</p>
                @endif
                <p class="mt-7 text-xs tracking-[-0.03em]">© {{ now()->year }} Bear Studio</p>
            </div>
            @if ($homeContent !== null)
                <div class="order-1 justify-self-end md:order-2 md:col-span-2 md:col-start-3 xl:col-span-1 xl:col-start-4 xl:justify-self-stretch">
                    <a class="group grid w-[290px] max-w-full grid-cols-[110px_1fr] gap-1 text-[#1a1a1a] xl:w-full group/rolling focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px] [touch-action:manipulation]" href="#contact">
                        <span class="relative min-h-[152px] overflow-hidden rounded-[18px] border-4 border-[#f5f4ef]">
                            <img alt="{{ $homeContent['heroPoster']['alt'] }}" aria-hidden="true" loading="lazy" decoding="async" class="object-cover absolute h-[100%] w-[100%] left-[0] top-[0] right-[0] bottom-[0] [color:transparent]" src="{{ $homeContent['heroPoster']['url'] }}">
                        </span>
                        <span class="flex flex-col justify-between rounded-[18px] bg-[#f5f4ef] p-5">
                            <span>
                                <span class="block text-xs">Video production<br>&amp; motion design</span>
                                <span class="mt-2 block text-xl leading-[1.05] font-semibold">Bear<br>Studio</span>
                            </span>
                            <span class="mt-4 flex items-center justify-between text-sm">
                                @include('public.home-contact-label')
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-up-right size-4 [flex-shrink:0]" aria-hidden="true">
                                    <path d="M7 7h10v10"></path><path d="M7 17 17 7"></path>
                                </svg>
                            </span>
                        </span>
                    </a>
                </div>
            @endif
        </div>
    </div>
</section>
