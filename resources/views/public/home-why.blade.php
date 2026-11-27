<section @if ($homeContent !== null) aria-labelledby="why-title" @else aria-label="Bear Studio at a glance" @endif class="section-space pt-[25px] pb-[50px] md:pt-[50px] md:pb-[100px] xl:pt-[95px] xl:pb-[190px]">
    <div class="site-container [width:calc(100%_-_2.5rem)] max-w-[var(--container-site)] mx-auto md:[width:calc(100%_-_3rem)] xl:[width:calc(100%_+_-1_*_clamp(72px,_8.75vw_-_40px,_100px))]">
        @if ($homeContent !== null)
            @include('public.home-section-intro', [
                'titleId' => 'why-title',
                'label' => 'Why choose us.',
                'sectionTitle' => $homeContent['whyTitle'],
                'subtitle' => $homeContent['whySubtitle'],
            ])
        @endif

        <div class="mt-[50px] grid items-end gap-10 md:mt-[60px] md:grid-cols-6 md:gap-1">
            @if ($homeContent !== null)
                <div class="md:col-span-2" data-reveal>
                    <a class="group relative flex aspect-[4/5] flex-col justify-between overflow-hidden rounded-[18px] bg-card p-6 text-white md:p-5 xl:p-8 group/rolling focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px] [touch-action:manipulation]" href="#contact">
                        <img data-why-image alt="{{ $homeContent['whyImage']['alt'] }}" loading="lazy" decoding="async" class="[transform:scale(1)] object-cover group-hover:[transform:scale(1.05)] group-focus-visible:[transform:scale(1.05)] motion-reduce:[transform:scale(1)] absolute h-[100%] w-[100%] left-[0] top-[0] right-[0] bottom-[0] [color:transparent]" src="{{ $homeContent['whyImage']['url'] }}">
                        <span class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/10 to-black/15"></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus relative size-5 self-end [flex-shrink:0]" aria-hidden="true">
                            <path d="M5 12h14"></path><path d="M12 5v14"></path>
                        </svg>
                        <span class="relative flex items-center justify-between gap-5 text-[28px] leading-none font-medium tracking-[-0.06em] xl:text-[38px]">
                            @include('public.home-contact-label')
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-up-right size-6 xl:size-8 [flex-shrink:0]" aria-hidden="true">
                                <path d="M7 7h10v10"></path><path d="M7 17 17 7"></path>
                            </svg>
                        </span>
                    </a>
                </div>
            @endif

            <div class="min-w-0 {{ $homeContent === null ? 'md:col-span-6' : 'md:col-span-3 md:col-start-4' }}">
                @if ($homeContent !== null)
                    <div data-reveal>
                        <p class="text-xl leading-[1.2] tracking-[-0.04em] indent-10 md:indent-12 xl:text-[28px]">{{ $homeContent['whyDescription'] }}</p>
                    </div>
                @endif
                <div class="mt-10 grid grid-cols-2 gap-1 md:mt-12 xl:mt-[60px]">
                    @include('public.home-stat', [
                        'count' => $totalProjects,
                        'counter' => true,
                        'heading' => 'Successful projects',
                        'detail' => 'A selection of ideas brought to life, from concept to final frame.',
                    ])
                    @include('public.home-stat', [
                        'count' => '04',
                        'counter' => false,
                        'heading' => 'Creative disciplines',
                        'detail' => 'Film & editing, animation, visual effects, and sound & music.',
                    ])
                </div>
            </div>
        </div>
    </div>
</section>
