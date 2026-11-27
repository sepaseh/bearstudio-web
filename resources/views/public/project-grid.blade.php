<section id="portfolio" @if ($home) aria-labelledby="projects-title" @else aria-label="Project catalog" @endif @class(['section-space', 'py-[25px] md:py-[50px] xl:py-[95px]' => $home, 'pb-[64px] min-[810px]:pb-[100px] min-[1200px]:pb-[140px]' => ! $home])>
    <div @class(['site-container max-w-[var(--container-site)] mx-auto', '[width:calc(100%_-_2.5rem)] md:[width:calc(100%_-_3rem)] xl:[width:calc(100%_+_-1_*_clamp(72px,_8.75vw_-_40px,_100px))]' => $home, 'w-[calc(100%_-_40px)] min-[810px]:w-[calc(100%_-_60px)] min-[1200px]:w-[calc(100%_-_72px)]' => ! $home])>
        @if ($home)
            <div class="portfolio-heading grid gap-[24px] items-start mb-[40px] md:gap-[4px] md:mb-[70px] md:grid-cols-[repeat(4,minmax(0,1fr))] xl:mb-[90px]" data-reveal>
                <p class="section-kicker flex items-center gap-3 tracking-[-.04em] text-[1rem] [font-weight:500] leading-[1.2]">
                    @include('public.section-plus')
                    Selected projects
                </p>
                <h2 id="projects-title" class="display-title tracking-[-.06em] text-[62px] [font-weight:600] leading-[.92] md:text-[99px] md:[grid-column:span_2] xl:text-[144px]">Projects</h2>
                <div class="max-w-[260px] md:[padding:40px_0_0_20px] xl:[padding:64px_0_0_40px]">
                    <p class="text-[16px] leading-[1.2]">Eyes craving to watch.</p>
                    <a href="/projects" class="mt-[6px] inline-flex min-h-[44px] items-center gap-[10px] text-[14px] underline underline-offset-4 hover:opacity-70 focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px]">
                        All projects <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        @elseif ($services->isNotEmpty())
            @php($tags = ['' => 'All'] + $services->all())
            <nav id="archive-filters" aria-label="Filter projects" class="mb-[16px] flex scroll-mt-[76px] flex-wrap gap-[6px]">
                @foreach ($tags as $slug => $label)
                    <a href="/projects{{ $slug !== '' ? '?category='.rawurlencode($slug) : '' }}#archive-filters" @if (($selected ?? '') === $slug) aria-current="true" @endif class="inline-flex min-h-[44px] max-w-full touch-manipulation items-center rounded-full border border-transparent bg-(--card) px-[17px] py-[10px] text-[13px] tracking-[-.03em] text-(--foreground) [overflow-wrap:anywhere] motion-safe:transition-colors aria-[current=true]:bg-(--foreground) aria-[current=true]:text-(--background) focus-visible:outline-2 focus-visible:outline-ring focus-visible:outline-offset-[5px]">{{ $slug !== '' && strcasecmp($label, 'All') === 0 ? $label.' (service)' : $label }}</a>
                @endforeach
            </nav>
        @endif

        @if ($projects->isNotEmpty())
            <div @class(['portfolio-grid grid grid-cols-1 gap-[4px] md:grid-cols-2 lg:grid-cols-3', 'xl:grid-cols-4' => ! $home])>
            @foreach ($projects as $entry)
                @php($link = $home ? ($entry['video'] ?? $entry['watch']) : $entry['path'])
                <article data-project-card data-reveal class="min-w-[0]">
                    <a @if ($link !== null) href="{{ $link }}" @endif @if ($home) target="_blank" rel="noopener noreferrer" @endif data-project="{{ $entry['slug'] }}" aria-labelledby="project-title-{{ $loop->index }}" aria-describedby="project-category-{{ $loop->index }}" class="project-card group/project focus-visible:[outline:2px_solid_var(--ring)] [touch-action:manipulation] block rounded-[18px] [outline-offset:4px] motion-reduce:focus-visible:[outline:2px_solid_var(--ring)]">
                        <div class="project-card-label flex min-h-[54px] items-center justify-between gap-[20px] [padding:16px_20px] mb-[4px] [background:var(--card)] rounded-[14px] md:min-h-[58px] md:[padding:14px_24px] md:rounded-[18px]">
                            <span id="project-category-{{ $loop->index }}" class="project-card-category inline-block m-[0] [color:var(--muted-foreground)] text-[12px] leading-[1.3] [font-weight:500]">{{ $entry['service'] }}</span>
                            <span class="project-card-dots flex [flex:0_0_auto] items-center gap-[3px]" aria-hidden="true">
                                <span class="block w-[6px] h-[6px] rounded-[50%] [background:color-mix(in_srgb,var(--foreground)_14%,var(--card))] md:w-[8px] md:h-[8px] group-focus-visible/project:[background:#fd5f59] motion-reduce:group-focus-visible/project:[background:#fd5f59] [@media(hover:hover)_and_(pointer:fine)]:group-hover/project:[background:#fd5f59] motion-reduce:[@media(hover:hover)_and_(pointer:fine)]:group-hover/project:[background:#fd5f59]"></span>
                                <span class="block w-[6px] h-[6px] rounded-[50%] [background:color-mix(in_srgb,var(--foreground)_14%,var(--card))] md:w-[8px] md:h-[8px] group-focus-visible/project:[background:#ffbb2c] motion-reduce:group-focus-visible/project:[background:#ffbb2c] [@media(hover:hover)_and_(pointer:fine)]:group-hover/project:[background:#ffbb2c] motion-reduce:[@media(hover:hover)_and_(pointer:fine)]:group-hover/project:[background:#ffbb2c]"></span>
                                <span class="block w-[6px] h-[6px] rounded-[50%] [background:color-mix(in_srgb,var(--foreground)_14%,var(--card))] md:w-[8px] md:h-[8px] group-focus-visible/project:[background:#25c93d] motion-reduce:group-focus-visible/project:[background:#25c93d] [@media(hover:hover)_and_(pointer:fine)]:group-hover/project:[background:#25c93d] motion-reduce:[@media(hover:hover)_and_(pointer:fine)]:group-hover/project:[background:#25c93d]"></span>
                            </span>
                        </div>
                        <div class="project-card-media relative aspect-[4/5] overflow-hidden rounded-[14px] [background:var(--card)] md:rounded-[18px]">
                            <div class="project-card-visual absolute inset-[4px] overflow-hidden rounded-[16px] group-focus-visible/project:motion-safe:inset-[0] [@media(hover:hover)_and_(pointer:fine)]:group-hover/project:motion-safe:inset-[0]">
                                <div class="project-card-picture absolute inset-[0] [filter:blur(0px)] [transform:scale(1)] group-focus-visible/project:motion-safe:[filter:blur(7px)] group-focus-visible/project:motion-safe:[transform:scale(1.13)] motion-reduce:group-focus-visible/project:[filter:none] motion-reduce:group-focus-visible/project:[transform:none] [@media(hover:hover)_and_(pointer:fine)]:group-hover/project:motion-safe:[filter:blur(7px)] [@media(hover:hover)_and_(pointer:fine)]:group-hover/project:motion-safe:[transform:scale(1.13)] motion-reduce:group-hover/project:[filter:none] motion-reduce:group-hover/project:[transform:none]">
                                    @if ($entry['poster'])
                                        <img src="{{ $entry['poster'] }}" alt="{{ $entry['title'] }}" @if ($entry['posterWidth'] !== null) width="{{ $entry['posterWidth'] }}" height="{{ $entry['posterHeight'] }}" @endif loading="{{ $loop->index < 2 ? 'eager' : 'lazy' }}" decoding="async" class="absolute inset-[0] w-[100%] h-[100%] object-cover">
                                    @endif
                                    @if ($entry['preview'])
                                        <video data-preview data-src="{{ $entry['preview'] }}" @if ($entry['poster']) poster="{{ $entry['poster'] }}" @endif muted loop playsinline preload="none" aria-hidden="true" @class(['absolute inset-[0] w-[100%] h-[100%] object-cover data-[failed=true]:[opacity:0] motion-reduce:data-[failed=true]:[opacity:0]', 'pointer-events-none' => ! $home])></video>
                                    @endif
                                </div>
                                <span data-project-overlay aria-hidden="true" class="absolute inset-[0] pointer-events-none [background:#0a0a0a] [opacity:.15] group-focus-visible/project:[opacity:.2] motion-reduce:group-focus-visible/project:[opacity:.2] [@media(hover:hover)_and_(pointer:fine)]:group-hover/project:[opacity:.2] motion-reduce:[@media(hover:hover)_and_(pointer:fine)]:group-hover/project:[opacity:.2]"></span>
                            </div>
                            <h3 id="project-title-{{ $loop->index }}" class="project-card-title absolute inset-[0] z-[1] flex items-center justify-center m-[0] [padding:24px_10%] pointer-events-none [color:#f5f4ef] text-center text-[clamp(24px,3.5vw,48px)] [font-weight:600] leading-[1.1] tracking-[-.05em] [text-wrap:balance] [overflow-wrap:anywhere] [text-shadow:0_1px_3px_#000,0_2px_18px_#000a] [-webkit-text-stroke:1px_#0009] [paint-order:stroke_fill] [transform:scale(1)] [transform-origin:center] lg:text-[clamp(24px,2.7vw,42px)] group-focus-visible/project:motion-safe:[transform:scale(.8)] motion-reduce:group-focus-visible/project:[transform:none] [@media(hover:hover)_and_(pointer:fine)]:group-hover/project:motion-safe:[transform:scale(.8)] motion-reduce:group-hover/project:[transform:none]">
                                <span class="block min-w-[0] max-w-[100%]">{{ $entry['title'] }}</span>
                            </h3>
                        </div>
                    </a>
                </article>
            @endforeach
            </div>
        @endif
    </div>
</section>
