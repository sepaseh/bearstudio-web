@if ($footer ?? false)
    <span class="relative block lg:h-full">
        <span data-brand-fallback class="block lg:flex lg:h-full lg:flex-col lg:justify-center lg:gap-[3cqh]"><span
            class="block text-[clamp(100px,39vw,352px)] leading-[.82] [font-weight:600] lg:text-right lg:text-[min(48cqw,91cqh)]"
            >Bear</span><small
            class="block text-right text-[38px] [font-weight:600] leading-[1] mt-[10px] tracking-[-.06em] md:text-[58px] lg:mt-0 lg:text-[min(13cqw,22cqh)]"
            >Studio</small></span>
    </span>
@else
    <span class="relative block"><span data-brand-fallback class="flex items-start">Bear</span></span>
@endif
