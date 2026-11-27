@extends('layouts.site')

@section('content')
    @include('public.archive-intro', [
        'headingId' => 'services-title',
        'description' => 'From live-action films and animation to visual effects, immersive experiences, and original sound.',
    ])

    @php
        $pageIds = ['top', 'theme-toggle', 'menu-toggle', 'main-content', 'contact', 'contact-title', 'menu-dialog', 'services-title'];
        $serviceTitles = array_map(static fn ($service) => $service['slug'].'-title', $services);
        $catalogId = static fn ($slug) => in_array($slug, $pageIds, true) || in_array($slug, $serviceTitles, true)
            ? 'service--item-'.$slug : $slug;
    @endphp

    <section aria-label="Service catalog" class="pb-[50px] md:pb-[100px] xl:pb-[140px]">
        <div class="site-container mx-auto w-[calc(100%_-_40px)] max-w-[var(--container-site)] min-[810px]:w-[calc(100%_-_60px)] min-[1200px]:w-[calc(100%_-_72px)]">
            @if ($services !== [])
                <div class="grid items-start gap-8 lg:grid-cols-4 lg:gap-1" data-service-catalog>
                    <nav aria-label="Service groups" class="min-w-0 lg:sticky lg:top-24 lg:pr-5 xl:pr-10">
                        <p class="mb-4 text-xs text-muted-foreground lg:mb-7">Explore our services</p>
                        <div class="grid grid-cols-2 gap-x-3 gap-y-1 lg:grid-cols-1 lg:gap-y-2">
                            @foreach ($services as $service)
                                @php
                                    $id = $catalogId($service['slug']);
                                @endphp
                                <a href="#{{ $id }}" data-service-link="{{ $id }}" aria-controls="{{ $id }}"
                                    @if ($loop->first) aria-current="location" @endif
                                    class="group/service-link relative inline-flex h-auto min-h-14 w-full shrink-0 items-start justify-start gap-2 rounded-none border-0 bg-transparent bg-clip-padding px-0 py-3 text-left text-sm font-medium whitespace-normal text-muted-foreground outline-none select-none hover:bg-transparent hover:text-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 aria-[current=location]:text-foreground dark:hover:bg-transparent lg:min-h-0 lg:gap-3 lg:py-4 [touch-action:manipulation]">
                                    <span aria-hidden="true" class="mt-0.5 text-[10px] leading-[1.5] lg:mt-1">{{ sprintf('%02d', $loop->iteration) }}</span>
                                    <span class="min-w-0 text-base leading-[1.15] tracking-[-.04em] md:text-xl lg:text-[25px] xl:text-[32px]">{{ $service['title'] }}</span>
                                    <span aria-hidden="true" data-service-link-indicator class="mt-0.5 ml-auto shrink-0 [transform:translateX(-5px)] opacity-0 group-aria-[current=location]/service-link:[transform:translateX(0px)] group-aria-[current=location]/service-link:opacity-100 lg:mt-1">
                                        <svg class="size-3.5 lg:size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="m7 7 10 10M17 7v10H7"></path>
                                        </svg>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </nav>

                    <div class="grid min-w-0 gap-2 lg:col-span-3 lg:pl-5 xl:pl-10">
                        @foreach ($services as $service)
                            @php
                                $id = $catalogId($service['slug']);
                                $titleId = $service['slug'].'-title';
                                if (in_array($titleId, $pageIds, true)) {
                                    $titleId = 'service--title-'.$service['slug'];
                                }
                                $image = $service['images'][0] ?? null;
                            @endphp
                            <article id="{{ $id }}" data-service-panel aria-labelledby="{{ $titleId }}" tabindex="-1" class="overflow-hidden rounded-[20px] border border-border bg-background focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-4 focus-visible:ring-offset-background">
                                <div class="p-5 md:p-8 xl:p-10 {{ $image ? 'grid items-start gap-5 sm:grid-cols-[176px_minmax(0,1fr)] md:gap-7' : '' }}">
                                    @if ($image)
                                        <div data-service-artwork class="relative aspect-square w-full max-w-[176px] overflow-hidden rounded-[12px] bg-muted">
                                            <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" width="{{ $image['width'] }}" height="{{ $image['height'] }}" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover object-center">
                                        </div>
                                    @endif
                                    <div class="min-w-0" data-reference-reveal>
                                        <h2 id="{{ $titleId }}" class="flex items-start gap-3 text-[26px] leading-[1.1] tracking-[-.04em] md:gap-5 md:text-[36px] xl:text-[44px]">
                                            <span aria-hidden="true" class="mt-1 shrink-0 text-sm leading-[1.5] text-muted-foreground md:mt-2">[{{ sprintf('%02d', $loop->iteration) }}]</span>
                                            <span class="min-w-0">{{ $service['title'] }}</span>
                                        </h2>
                                        <p class="mt-6 max-w-[620px] text-base leading-[1.5] text-muted-foreground xl:text-lg">{{ $service['description'] }}</p>
                                        @if ($service['tags'] !== [])
                                            <p class="mt-8 mb-3 text-xs text-muted-foreground">Categories</p>
                                            <ul aria-label="{{ $service['title'] }} categories" class="flex flex-wrap gap-2">
                                                @foreach ($service['tags'] as $tag)
                                                    <li class="rounded-full border border-border px-3.5 py-2 text-xs leading-tight md:text-sm">{{ $tag }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-10 lg:ml-[25%] lg:pl-5 xl:mt-[50px] xl:pl-10">
                <a href="/projects" class="inline-flex items-center gap-5 border-b border-foreground/40 pb-2 text-base focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px] [touch-action:manipulation]">
                    Explore our projects
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M7 7h10v10"></path></svg>
                </a>
            </div>
        </div>
    </section>
@endsection
