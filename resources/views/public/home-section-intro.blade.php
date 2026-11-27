<header class="grid gap-8 md:grid-cols-4 md:gap-1">
    <p class="section-kicker flex items-center gap-3 self-start tracking-[-.04em] text-[1rem] font-medium leading-[1.2]" data-reveal>
        @include('public.section-plus')
        {{ $label }}
    </p>
    <div class="min-w-0 md:col-span-3">
        <h2 id="{{ $titleId }}" class="max-w-[1000px] text-[32px] leading-[1.1] font-semibold tracking-[-.06em] md:text-[50px] xl:text-[60px]" data-reveal>
            {{ $sectionTitle }} <span class="text-muted-foreground">{{ $subtitle }}</span>
        </h2>
        @isset($sectionDescription)
            <p class="mt-[30px] max-w-[530px] text-base leading-[1.4] tracking-[-.03em] text-muted-foreground xl:text-[18px]" data-reveal>
                {!! nl2br(e($sectionDescription), false) !!}
            </p>
        @endisset
    </div>
</header>
