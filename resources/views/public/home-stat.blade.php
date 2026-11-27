<div class="h-full" data-reveal>
    <div data-slot="card" data-size="default" class="group/card flex flex-col gap-(--card-spacing) overflow-hidden bg-card text-sm text-card-foreground ring-foreground/10 [--card-spacing:--spacing(4)] has-data-[slot=card-footer]:pb-0 has-[>img:first-child]:pt-0 data-[size=sm]:[--card-spacing:--spacing(3)] data-[size=sm]:has-data-[slot=card-footer]:pb-0 *:[img:first-child]:rounded-t-xl *:[img:last-child]:rounded-b-xl h-full rounded-[18px] p-0 ring-0">
        <div data-slot="card-content" class="flex h-full flex-col p-4 md:p-5 xl:p-8">
            @if ($counter)
                <span data-total-project-count class="block text-[72px] leading-[.9] font-semibold tracking-[-.065em] tabular-nums md:text-[80px] xl:text-[132px]">{{ $count }}</span>
            @else
                <div class="text-[72px] leading-[0.9] font-semibold tracking-[-0.065em] md:text-[80px] xl:text-[132px]">
                    <span class="block tabular-nums">{{ $count }}</span>
                </div>
            @endif
            <h3 class="mt-7 text-base leading-[1.1] font-semibold xl:mt-10 xl:text-xl {{ $counter ? 'tracking-[-.04em]' : 'tracking-[-0.04em]' }}">{{ $heading }}</h3>
            <p class="mt-3 text-[13px] leading-[1.3] tracking-[-0.025em] text-muted-foreground md:text-sm xl:mt-4 xl:text-base">{{ $detail }}</p>
        </div>
    </div>
</div>
