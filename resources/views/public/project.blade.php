@extends('layouts.site')

@section('content')
    <section aria-labelledby="project-title" class="pt-[44px] pb-[64px] md:pt-[72px] xl:pt-[110px] xl:pb-[100px]">
        <div class="site-container [width:calc(100%_-_2.5rem)] max-w-[var(--container-site)] mx-auto md:[width:calc(100%_-_3rem)] xl:[width:calc(100%_+_-1_*_clamp(72px,_8.75vw_-_40px,_100px))]">
            <a href="/projects" class="inline-flex min-h-[44px] items-center gap-[10px] text-[14px] hover:underline focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px]"><span aria-hidden="true">←</span> All projects</a>
            <header class="mt-[22px] mb-[24px] flex flex-col gap-[12px] md:mt-[32px] md:mb-[36px] md:flex-row md:items-end md:justify-between">
                <h1 id="project-title" class="min-w-0 text-[clamp(38px,7vw,116px)] font-semibold leading-[.98] tracking-[-.06em] [text-wrap:balance] [overflow-wrap:anywhere]">{{ $project['title'] }}</h1>
                <p class="text-[15px] text-(--muted-foreground) md:pb-[5px] md:text-[18px]">{{ $project['service'] }}</p>
            </header>
            @if ($project['video'] || $project['poster'])
                <div class="overflow-hidden rounded-[16px] bg-black md:rounded-[24px]">
                    @if ($project['video'])
                        <video src="{{ $project['video'] }}" @if ($project['poster']) poster="{{ $project['poster'] }}" @endif aria-label="{{ $project['title'] }} — full film" controls playsinline preload="none" class="block aspect-video max-h-[72svh] w-full object-contain"></video>
                    @elseif ($project['poster'])
                        <img src="{{ $project['poster'] }}" alt="{{ $project['title'] }}" @if ($project['posterWidth'] && $project['posterHeight']) width="{{ $project['posterWidth'] }}" height="{{ $project['posterHeight'] }}" @endif fetchpriority="high" class="block max-h-[72svh] w-full object-contain">
                    @endif
                </div>
            @endif
            @if ($project['summary'] !== '' || $project['watch'])
                <div class="mt-[24px] flex flex-wrap items-start justify-between gap-x-[40px] gap-y-[16px] md:mt-[36px]">
                    @if ($project['summary'] !== '')
                        <p class="max-w-[680px] min-w-0 whitespace-pre-line text-[16px] leading-[1.5] tracking-[-.025em] text-(--muted-foreground) [overflow-wrap:anywhere] md:text-[18px]">{{ $project['summary'] }}</p>
                    @endif
                    @if ($project['watch'])
                        <a href="{{ $project['watch'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-[44px] items-center gap-[10px] border-b border-(--border) text-[14px] hover:underline focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px]">{{ $project['watchLabel'] ? 'Watch on '.$project['watchLabel'] : 'Watch video' }} <span aria-hidden="true">↗</span></a>
                    @endif
                </div>
            @endif
        </div>
    </section>

    @if ($next !== [])
        <section aria-labelledby="next-projects-title" class="pb-[64px] md:pb-[100px] xl:pb-[140px]">
            <div class="site-container [width:calc(100%_-_2.5rem)] max-w-[var(--container-site)] mx-auto md:[width:calc(100%_-_3rem)] xl:[width:calc(100%_+_-1_*_clamp(72px,_8.75vw_-_40px,_100px))]">
                <h2 id="next-projects-title" class="mb-[20px] text-[clamp(28px,4vw,62px)] font-semibold leading-[1.05] tracking-[-.05em] md:mb-[30px]">Next projects</h2>
                <div class="grid max-w-[1100px] gap-[12px] md:grid-cols-2 md:gap-[16px]">
                    @foreach ($next as $entry)
                        <a href="{{ $entry['path'] }}" class="group block min-w-0 overflow-hidden rounded-[16px] bg-(--card) focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px]">
                            @if ($entry['poster'])
                                <img src="{{ $entry['poster'] }}" alt="" @if ($entry['posterWidth'] && $entry['posterHeight']) width="{{ $entry['posterWidth'] }}" height="{{ $entry['posterHeight'] }}" @endif loading="lazy" decoding="async" class="block aspect-video w-full object-cover">
                            @endif
                            <div class="px-[18px] py-[16px] md:px-[24px] md:py-[20px]">
                                <span class="block text-[13px] text-(--muted-foreground)">{{ $entry['service'] }}</span>
                                <span class="mt-[4px] block text-[22px] font-semibold leading-[1.2] [overflow-wrap:anywhere] group-hover:underline">{{ $entry['title'] }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
