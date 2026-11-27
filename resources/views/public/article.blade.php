@extends('layouts.site')

@section('content')
    <section aria-labelledby="article-title" @class(['pb-[64px] min-[810px]:pb-[100px] min-[1200px]:pb-[140px]', '-mt-[60px]' => $socialImage])>
        <div class="site-container mx-auto w-[calc(100%_-_40px)] min-[810px]:w-[calc(100%_-_60px)] min-[1200px]:w-[calc(100%_-_72px)]">
            <article @class(['relative', 'pt-[600px] min-[810px]:pt-[690px] min-[1200px]:pt-0' => $socialImage, 'pt-[44px] md:pt-[72px] xl:pt-[110px]' => ! $socialImage]) data-article-detail>
                @if ($socialImage)
                    <div class="absolute top-[60px] right-[-16px] left-[-16px] h-[500px] min-[810px]:right-[-24px] min-[810px]:left-[-24px] min-[810px]:h-[550px] min-[1200px]:inset-y-0 min-[1200px]:right-auto min-[1200px]:left-[-30px] min-[1200px]:h-auto min-[1200px]:w-[calc((100%_+_72px)*.42)]">
                        <div class="relative h-full overflow-hidden rounded-[16px] min-[810px]:rounded-[25px] min-[1200px]:sticky min-[1200px]:top-[60px] min-[1200px]:h-[calc(100svh_-_66px)]">
                            <img src="{{ $socialImage }}" alt="{{ $article->title }}" width="{{ $article->cover_width }}" height="{{ $article->cover_height }}"
                                 loading="eager" fetchpriority="high" decoding="async" class="absolute inset-0 size-full object-cover">
                        </div>
                    </div>
                @endif

                <div @class(['min-[1200px]:grid min-[1200px]:grid-cols-2' => $socialImage])>
                    <div @class(['min-w-0', 'min-[1200px]:col-start-2' => $socialImage])>
                        <header @class(['flex flex-col gap-[30px] pb-[30px] min-[810px]:gap-[60px] min-[810px]:pb-[50px]', 'min-[1200px]:h-[100svh] min-[1200px]:justify-end min-[1200px]:pb-[140px]' => $socialImage]) data-article-intro>
                            <div>
                                <div class="mb-[14px] min-[810px]:mb-[26px]" data-reference-reveal>
                                    <p class="flex flex-wrap gap-x-3 gap-y-1 text-[12px] font-medium leading-[1.3] text-muted-foreground min-[810px]:text-[13px] min-[1200px]:text-[14px]">
                                        <span>{{ $article->category_name }}</span>
                                    </p>
                                </div>
                                <div data-reference-title>
                                    <h1 id="article-title" class="text-[36px] font-semibold leading-[1.1] tracking-[-.06em] min-[810px]:text-[50px] min-[1200px]:text-[60px]">{{ $article->title }}</h1>
                                </div>
                                <div class="mt-[22px] min-[810px]:mt-[24px] min-[1200px]:mt-[36px]" data-reference-reveal data-reveal-delay="0.1">
                                    <p class="max-w-[500px] text-[15px] font-medium leading-[1.3] text-muted-foreground min-[810px]:text-[16px] min-[1200px]:text-[18px]">{{ $article->excerpt }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-[14px]" data-reference-reveal data-reveal-delay="0.1">
                                <span class="grid size-[42px] shrink-0 place-items-center rounded-full bg-card">
                                    <svg class="size-6" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 6v12m5.196-9-10.392 6m0-6 10.392 6"></path>
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-[13px] font-medium leading-[1.4] min-[810px]:text-[14px] min-[1200px]:text-[16px]">Bear Studio</p>
                                    <p class="mt-[4px] text-[12px] font-medium leading-[1.3] text-muted-foreground min-[810px]:text-[13px] min-[1200px]:text-[14px]">Editorial</p>
                                </div>
                            </div>
                        </header>

                        <div class="border-t border-border pt-[34px] min-[810px]:pt-[50px] min-[1200px]:pt-[70px]" data-article-body>
                            @if ($articleBody->lead !== null)
                                <div data-reference-reveal>
                                    <p class="text-[24px] font-medium leading-[1.2] tracking-[-.04em] min-[810px]:text-[28px] min-[1200px]:text-[30px] [&_a]:underline">{!! $articleBody->lead !!}</p>
                                </div>
                            @endif
                            <div class="mt-[24px] max-w-[600px] space-y-[22px] text-[15px] font-medium leading-[1.4] tracking-[-.04em] min-[810px]:mt-[50px] min-[810px]:space-y-[30px] min-[810px]:text-[16px] min-[1200px]:text-[17px]">
                                @foreach ($articleBody->sections as $articleSection)
                                    <section>
                                        <h2 class="text-[20px] font-semibold leading-[1.2] tracking-[-.04em] min-[810px]:text-[22px] min-[810px]:leading-[1.3] min-[1200px]:text-[23px] [&_a]:underline">{!! $articleSection['heading'] !!}</h2>
                                        <div class="mt-[10px] space-y-[10px] text-muted-foreground min-[810px]:mt-[20px] min-[810px]:space-y-[20px] [&_a]:underline">
                                            @foreach ($articleSection['paragraphs'] as $paragraph)
                                                <p>{!! $paragraph !!}</p>
                                            @endforeach
                                            @if ($articleSection['points'] !== [])
                                                <ul class="list-disc pl-[22px] marker:text-foreground">
                                                    @foreach ($articleSection['points'] as $point)
                                                        <li>{!! $point !!}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    </section>
                                @endforeach
                                @if ($articleBody->flow !== null)
                                    <div class="space-y-[10px] text-muted-foreground min-[810px]:space-y-[20px] [&_:is(h2,h3,h4,h5,h6)]:font-semibold [&_:is(h2,h3,h4,h5,h6)]:text-foreground [&_h2]:text-[20px] min-[810px]:[&_h2]:text-[22px] min-[1200px]:[&_h2]:text-[23px] [&_a]:underline [&_blockquote]:border-l [&_blockquote]:pl-[16px] [&_ol]:list-decimal [&_ul]:list-disc [&_:is(ol,ul)]:pl-[22px] [&_li]:marker:text-foreground">{!! $articleBody->flow !!}</div>
                                @endif
                            </div>
                        </div>

                        @if ($nextArticle)
                            <nav aria-label="Article navigation" class="mt-[36px] max-w-[600px] min-[810px]:mt-[60px] min-[1200px]:mt-[70px]">
                                <a href="{{ $nextArticle['path'] }}" data-article-next data-active="false"
                                   class="group/article-next relative flex items-center overflow-hidden rounded-[14px] bg-[#0a0a0a] p-[26px] text-[#f5f5f5] focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-4 min-[810px]:justify-end min-[810px]:px-[40px] min-[810px]:py-[50px] [touch-action:manipulation]">
                                    @if ($nextArticle['cover'])
                                        <img src="{{ $nextArticle['cover'] }}" alt="" width="{{ $nextArticle['coverWidth'] }}" height="{{ $nextArticle['coverHeight'] }}" loading="lazy" decoding="async" data-article-next-media
                                             class="absolute inset-0 size-full object-cover opacity-[.6] [transform:scale(1)] [filter:blur(0px)] min-[810px]:motion-safe:group-data-[active=true]/article-next:[transform:scale(1.1)] min-[810px]:motion-safe:group-data-[active=true]/article-next:[filter:blur(5px)]">
                                    @endif
                                    <div class="relative flex flex-col gap-[10px] min-[810px]:items-end min-[810px]:gap-[14px] min-[810px]:text-right">
                                        <p class="text-[11px] font-medium leading-[1.3] text-white/70 min-[810px]:text-[12px]">Next</p>
                                        <h2 class="max-w-[400px] text-[18px] font-medium leading-[1.2] tracking-[-.04em] min-[810px]:text-[22px]">{{ $nextArticle['title'] }}</h2>
                                    </div>
                                </a>
                            </nav>
                        @endif

                        <section aria-labelledby="article-connect-title" class="mt-[36px] max-w-[600px] min-[810px]:mt-[60px] min-[1200px]:mt-[70px]">
                            <h2 id="article-connect-title" class="text-[14px] font-medium leading-[1.4] min-[810px]:text-[16px]">Let's keep in touch.</h2>
                            <p class="mt-[9px] text-[13px] font-medium leading-[1.4] text-muted-foreground min-[810px]:text-[14px] min-[1200px]:text-[15px]">Video production &amp; motion design.<br>From a different perspective.</p>
                            @if ($articleSite['socialLinks'] !== [])
                                <div class="mt-[26px] flex items-center gap-[6px] min-[810px]:mt-[34px]">
                                    @foreach ($articleSite['socialLinks'] as $link)
                                        <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $articleSite['brandName'] }} on {{ $link['label'] }}" class="inline-flex size-9 items-center justify-center focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px] [touch-action:manipulation]">
                                            @switch(strtolower($link['label']))
                                                @case('x')
                                                    <svg class="size-[20px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.633 7.584H.478l8.6-9.835L0 1.154h7.594l5.243 6.932zM17.61 20.644h2.039L6.486 3.24H4.298z"></path></svg>
                                                    @break
                                                @case('linkedin')
                                                    <svg class="size-[20px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.049c.476-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 1 1 0-4.124 2.062 2.062 0 0 1 0 4.124zm1.782 13.019H3.555V9h3.564zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0z"></path></svg>
                                                    @break
                                                @default
                                                    <svg class="size-[20px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M7 17 17 7M7 7h10v10"></path></svg>
                                            @endswitch
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </section>
                    </div>
                </div>
            </article>
        </div>
    </section>
@endsection
