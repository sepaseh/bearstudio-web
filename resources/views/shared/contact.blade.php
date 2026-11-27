@php
    $details = [];
    if ($site['contact']['phone'] !== null) {
        $details[] = ['label' => $site['contact']['phone'], 'url' => 'tel:'.$site['contact']['phone']];
    }
    if ($site['contact']['whatsAppNumber'] !== null) {
        $details[] = ['label' => 'WhatsApp', 'url' => 'https://wa.me/'.substr($site['contact']['whatsAppNumber'], 1)];
    }
    $contactLinks = $site['contactEmail'] !== null || $details !== [] || $site['socialLinks'] !== [];
@endphp

@if ($site['contactTitle'] !== '' || $site['contactDescription'] !== '' || $contactLinks)
    <section id="contact" @if ($site['contactTitle'] !== '') aria-labelledby="contact-title" @else aria-label="Contact" @endif
             class="contact-information edge-frame mx-[2px] py-[50px] rounded-[25px] [background:linear-gradient(#0009,#0009),url('/thumbnails/texture.jpg')_center/cover] [color:#f1eee8] md:mx-[4px] md:py-[100px] xl:mx-[6px] xl:py-[200px]">
        <div class="site-container contact-layout [width:calc(100%_-_2.5rem)] max-w-[var(--container-site)] mx-[auto] grid gap-[56px] items-center md:[width:calc(100%_-_3rem)] lg:gap-[4px] lg:grid-cols-[repeat(2,minmax(0,1fr))] xl:[width:calc(100%_+_-1_*_clamp(72px,_8.75vw_-_40px,_100px))]">
            <div data-reveal class="lg:[order:2]">
                <p class="section-kicker tracking-[-.04em] text-[1rem] [font-weight:500] leading-[1.2] mb-[22px]">Create together</p>
                @if ($site['contactTitle'] !== '')
                    <h2 id="contact-title" class="display-title tracking-[-.06em] text-[62px] [font-weight:600] leading-[.92] md:text-[99px] xl:text-[144px]">{{ $site['contactTitle'] }}</h2>
                @endif
                @if ($site['contactDescription'] !== '')
                    <p class="contact-intro mt-[30px] max-w-[520px] text-[22px] leading-[1.2] md:text-[28px] xl:text-[32px]">{{ $site['contactDescription'] }}</p>
                @endif
            </div>
            @if ($contactLinks)
                <div class="contact-links lg:[order:1] lg:pr-[64px]" data-reveal>
                    <p class="text-[14px] [color:#ffffffa6]">Start a conversation</p>
                    @if ($site['contactEmail'] !== null)
                        <a href="{{ \App\Support\SiteSettings::emailLink($site['contactEmail']) }}" class="contact-email focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px] [touch-action:manipulation] flex items-center justify-between gap-[16px] py-[20px] [border-bottom:1px_solid_#ffffff4d] text-[clamp(20px,2.2vw,32px)] leading-[1.2] tracking-[-.04em] [overflow-wrap:anywhere]">
                            {{ $site['contactEmail'] }}
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.5" aria-hidden="true" class="[flex-shrink:0] w-[24px] h-[24px]"><path d="M7 17 17 7M7 7h10v10"></path></svg>
                        </a>
                    @endif
                    @foreach ($details as $detail)
                        <a href="{{ $detail['url'] }}" class="focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px] [touch-action:manipulation] flex items-center justify-between gap-[16px] py-[16px] [border-bottom:1px_solid_#ffffff4d] text-[16px] [overflow-wrap:anywhere]">
                            {{ $detail['label'] }}
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.5" aria-hidden="true" class="[flex-shrink:0]"><path d="M7 17 17 7M7 7h10v10"></path></svg>
                        </a>
                    @endforeach
                    @if ($site['socialLinks'] !== [])
                        <div class="contact-socials flex flex-wrap gap-[24px] mt-[30px]">
                            @include('shared.social-links', ['links' => $site['socialLinks']])
                        </div>
                    @endif
                    <p class="contact-note mt-[50px] [color:#ffffffa6] text-[16px] leading-[1.4]">Video production &amp; motion design<br>From a different perspective.</p>
                </div>
            @endif
        </div>
    </section>
@else
    <div id="contact"></div>
@endif
