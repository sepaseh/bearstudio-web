<section id="about" aria-labelledby="studio-title" class="section-space pt-[50px] pb-[25px] md:pt-[100px] md:pb-[50px] xl:pt-[190px] xl:pb-[95px]">
    <div class="site-container [width:calc(100%_-_2.5rem)] max-w-[var(--container-site)] mx-auto md:[width:calc(100%_-_3rem)] xl:[width:calc(100%_+_-1_*_clamp(72px,_8.75vw_-_40px,_100px))]">
        @include('public.home-section-intro', [
            'titleId' => 'studio-title',
            'label' => 'About us.',
            'sectionTitle' => $homeContent['aboutTitle'],
            'subtitle' => $homeContent['aboutSubtitle'],
            'sectionDescription' => $homeContent['aboutDescription'],
        ])
        <div class="mt-[50px] grid gap-1 md:mt-[60px] md:grid-cols-2 xl:mt-[100px] xl:grid-cols-4">
            @foreach ($homeContent['approach'] as $index => $card)
                <div class="h-full" data-reveal>
                    <div data-slot="card" data-size="default" class="group/card flex flex-col gap-(--card-spacing) overflow-hidden bg-card text-sm text-card-foreground ring-foreground/10 [--card-spacing:--spacing(4)] has-data-[slot=card-footer]:pb-0 has-[>img:first-child]:pt-0 data-[size=sm]:[--card-spacing:--spacing(3)] data-[size=sm]:has-data-[slot=card-footer]:pb-0 *:[img:first-child]:rounded-t-xl *:[img:last-child]:rounded-b-xl h-full rounded-[18px] p-0 ring-0">
                        <div data-slot="card-content" class="flex min-h-[150px] flex-col justify-between gap-8 p-[30px] lg:h-[150px] lg:gap-3 lg:p-6">
                            <div class="flex items-center justify-between">
                                <span aria-hidden="true" class="flex gap-0.5">
                                    @for ($dot = 0; $dot < 4; $dot++)
                                        <span class="size-2 rounded-full {{ $dot <= $index ? 'bg-foreground' : 'bg-foreground/15' }}"></span>
                                    @endfor
                                </span>
                                <span class="text-[11px] text-muted-foreground">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            </div>
                            <div class="flex items-center gap-5">
                                <img alt="{{ $card['image']['alt'] }}" loading="lazy" width="50" height="50" decoding="async" class="size-[50px] shrink-0 rounded-md object-cover [color:transparent]" src="{{ $card['image']['url'] }}">
                                <h3 class="min-w-0 text-base leading-[1.2] font-medium tracking-[-0.04em] lg:line-clamp-3 lg:[overflow-wrap:anywhere]">{{ $card['title'] }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
