<section id="faq" aria-labelledby="faq-title" class="faq-section py-[50px] md:py-[100px] xl:py-[190px]">
    <div class="site-container [width:calc(100%_-_2.5rem)] max-w-[var(--container-site)] mx-auto grid grid-cols-[minmax(0,1fr)] items-start gap-[40px] md:[width:calc(100%_-_3rem)] md:grid-cols-[repeat(2,minmax(0,1fr))] md:gap-[4px] xl:[width:calc(100%_+_-1_*_clamp(72px,_8.75vw_-_40px,_100px))]">
        <div class="faq-heading flex flex-col items-start gap-[18px] min-w-[0] md:gap-[30px]" data-reveal>
            <h2 id="faq-title" class="display-title tracking-[-.06em] text-[62px] [font-weight:600] leading-[.92] md:text-[99px] xl:text-[144px]">FAQ.</h2>
            <p class="faq-intro max-w-[380px] m-[0] p-[0] text-[13px] [font-weight:500] leading-[1.4] tracking-[-.04em] [color:var(--muted-foreground)] [text-wrap:balance] md:text-[14px] xl:text-[16px]">
                A few things to know before we turn an idea into something you can watch.
            </p>
        </div>

        <div class="faq-list min-w-[0] flex flex-col gap-[4px]" data-reveal>
            @foreach ($faqs as $faq)
                @php
                    $expanded = $loop->first ? 'true' : 'false';
                    $answerId = 'faq-answer-'.$loop->index;
                @endphp
                <div class="faq-item [border:0] rounded-[14px] [background:var(--card)] overflow-hidden" data-accordion-item data-open="{{ $expanded }}">
                    <h3 class="m-[0]">
                        <button type="button" data-accordion-trigger data-accordion-group="faq" aria-expanded="{{ $expanded }}" aria-controls="{{ $answerId }}" class="group/faq-toggle [cursor:pointer] [touch-action:manipulation] flex items-center justify-between gap-[16px] w-[100%] p-[20px] text-[15px] [font-weight:500] leading-[1.3] tracking-[-.04em] text-left [background:none] [border:0] [color:inherit] md:p-[26px] md:text-[18px] focus-visible:[outline:2px_solid_var(--ring)] focus-visible:[outline-offset:-3px] focus-visible:rounded-[14px] motion-reduce:focus-visible:[outline:2px_solid_var(--ring)] motion-reduce:focus-visible:[outline-offset:-3px] motion-reduce:focus-visible:rounded-[14px]">
                            <span class="min-w-[0]">{{ $faq->question }}</span>
                            <span class="faq-icon [transform:rotate(0deg)] group-aria-expanded/faq-toggle:[transform:rotate(360deg)] grid [place-items:center] [flex:0_0_auto] w-[15px] h-[15px] rounded-[50%] [background:var(--foreground)] [color:var(--background)] [transform-origin:center] md:w-[18px] md:h-[18px]" data-accordion-icon>
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="[flex-shrink:0] w-[72.2222%] h-[72.2222%] [stroke-width:3]">
                                    <path d="M12 5v14M5 12h14"></path>
                                </svg>
                            </span>
                        </button>
                    </h3>
                    <div id="{{ $answerId }}" class="faq-answer overflow-hidden p-[0]" @unless ($loop->first) hidden @endunless>
                        <div class="faq-answer-inner [padding:0_20px_20px] md:[padding:0_26px_26px]">
                            <p class="m-[0] [color:var(--muted-foreground)] text-[13px] [font-weight:500] leading-[1.4] tracking-[-.04em] md:text-[15px]">{{ $faq->answer }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
