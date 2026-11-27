@extends('layouts.site')

@section('content')
    @include('public.archive-intro', [
        'headingId' => 'articles-title',
        'description' => 'Ideas on film, motion and the choices that bring a story into focus, from the first brief to the final frame.',
    ])

    <section aria-label="Article archive" class="pb-[64px] min-[810px]:pb-[100px] min-[1200px]:pb-[140px]">
        <div class="site-container mx-auto w-[calc(100%_-_40px)] max-w-[var(--container-site)] min-[810px]:w-[calc(100%_-_60px)] min-[1200px]:w-[calc(100%_-_72px)]">
            <div class="grid grid-cols-1 gap-[4px] min-[810px]:grid-cols-2 min-[1200px]:grid-cols-4">
                @foreach ($articles as $entry)
                    @php($featured = $loop->first && $entry['cover'])
                    <article
                        @class(['articles-card group/article aspect-[4/5] min-w-0 w-full self-start min-[810px]:min-h-[520px]', 'min-h-[470px]' => $featured, 'min-h-[420px]' => ! $featured])
                        data-article-card data-article-featured="{{ $featured ? 'true' : 'false' }}" data-active="false"
                        data-reference-reveal data-reveal-delay="{{ ($loop->index % 4) * 0.055 }}"
                    >
                        <a href="{{ $entry['path'] }}" aria-labelledby="article-{{ $entry['slug'] }}" class="block h-full rounded-[18px] focus-visible:outline-2 focus-visible:outline-ring focus-visible:outline-offset-4">
                            <div data-slot="card" data-size="default" @class(['relative flex h-full flex-col overflow-hidden rounded-[18px] bg-(--card)', 'text-[#f5f4ef]' => $featured, 'p-[10px] text-(--foreground)' => ! $featured])>
                                @if ($featured)
                                    <div class="absolute inset-0 [transform:scale(1)] [filter:blur(0px)] motion-safe:min-[810px]:group-data-[active=true]/article:[transform:scale(1.1)] motion-safe:min-[810px]:group-data-[active=true]/article:[filter:blur(5px)]" data-article-media>
                                        <img src="{{ $entry['cover'] }}" alt="{{ $entry['title'] }}" width="{{ $entry['coverWidth'] }}" height="{{ $entry['coverHeight'] }}" loading="eager" decoding="async" class="absolute inset-0 size-full object-cover">
                                    </div>
                                    <div data-slot="card-content" class="relative isolate mt-auto flex flex-col gap-[20px] p-[30px]">
                                        <div class="pointer-events-none absolute inset-0 -z-10 [background:linear-gradient(rgba(10,10,10,0)_0%,rgba(10,10,10,.95)_100%)]" aria-hidden="true"></div>
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] leading-[1.3] text-white/75 min-[1200px]:text-[12px]">
                                            <span>{{ $entry['category'] }}</span>
                                        </div>
                                        <div class="flex flex-col gap-[20px]">
                                            <h2 id="article-{{ $entry['slug'] }}" class="m-0 max-w-[400px] text-[24px] font-semibold leading-[1.1] tracking-[-.04em] text-balance min-[810px]:text-[30px]">{{ $entry['title'] }}</h2>
                                            <p class="m-0 max-w-[400px] text-[13px] leading-[1.4] text-white/75 min-[810px]:text-[14px] min-[1200px]:text-[15px]">{{ $entry['excerpt'] }}</p>
                                        </div>
                                    </div>
                                @else
                                    <div class="relative min-h-[110px] flex-1">
                                        @if ($entry['cover'])
                                            <div class="relative size-[110px] overflow-hidden rounded-[12px] motion-safe:min-[1200px]:group-data-[active=true]/article:w-[170px] motion-safe:min-[1200px]:group-data-[active=true]/article:h-[170px]" data-article-media>
                                                <img src="{{ $entry['cover'] }}" alt="{{ $entry['title'] }}" width="{{ $entry['coverWidth'] }}" height="{{ $entry['coverHeight'] }}" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover">
                                            </div>
                                        @endif
                                        <span class="absolute top-[10px] right-[10px] size-[18px] [transform:rotate(0deg)] motion-safe:min-[1200px]:group-data-[active=true]/article:[transform:rotate(90deg)]" data-article-icon aria-hidden="true">
                                            <svg class="size-full" viewBox="0 0 18 18" aria-hidden="true">
                                                <circle cx="9" cy="9" r="9" fill="currentColor"></circle>
                                                <path d="M9 5v8M5 9h8" fill="none" stroke="var(--card)" stroke-width="2"></path>
                                            </svg>
                                        </span>
                                    </div>
                                    <div data-slot="card-content" class="flex flex-col gap-[25px] p-[20px] min-[810px]:p-[14px] min-[1200px]:gap-[16px] min-[1200px]:p-[20px]">
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[12px] leading-[1.3] text-(--muted-foreground)">
                                            <span>{{ $entry['category'] }}</span>
                                        </div>
                                        <div class="flex flex-col gap-[10px] min-[1200px]:gap-[6px]">
                                            <h2 id="article-{{ $entry['slug'] }}" class="m-0 text-[20px] font-medium leading-[1.3] tracking-[-.04em] text-balance">{{ $entry['title'] }}</h2>
                                            <p class="m-0 text-[13px] leading-[1.4] text-(--muted-foreground) min-[810px]:text-[14px] min-[1200px]:text-[15px]">{{ $entry['excerpt'] }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
