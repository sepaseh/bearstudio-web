<dialog id="project-dialog" aria-label="Bear Studio project player" class="project-dialog fixed [color:var(--foreground)] [background:var(--background)] [border:1px_solid_var(--border)] [overscroll-behavior:contain] inset-[0] m-[auto] [width:min(1280px,calc(100%_-_24px))] max-h-[92dvh] rounded-[24px] [padding:16px_20px_28px] md:[padding:20px_28px_32px] xl:[padding:24px_36px_40px] backdrop:[background:#000b] backdrop:[backdrop-filter:blur(7px)] motion-reduce:backdrop:[background:#000b] motion-reduce:backdrop:[backdrop-filter:blur(7px)]">
    <div class="project-dialog-toolbar sticky top-[0] z-[3] flex justify-end min-h-[44px] mb-[20px] pointer-events-none">
        <button type="button" data-close-dialog aria-label="Close video" class="dialog-close [cursor:pointer] [touch-action:manipulation] static [float:none] top-[0] z-[2] grid [place-items:center] w-[44px] h-[44px] [border:1px_solid_var(--border)] rounded-[50%] text-[28px] leading-[1] [background:var(--background)] [color:var(--foreground)] pointer-events-auto focus-visible:[outline:2px_solid_var(--ring)] focus-visible:[outline-offset:5px] motion-reduce:focus-visible:[outline:2px_solid_var(--ring)] motion-reduce:focus-visible:[outline-offset:5px]">×</button>
    </div>

    @foreach ($projects as $entry)
        <section data-project-detail="{{ $entry['slug'] }}" data-provider="{{ $entry['video'] === null ? 'watch' : 'mp4' }}" hidden aria-labelledby="detail-title-{{ $entry['slug'] }}">
            <div class="project-detail-layout grid grid-cols-[minmax(0,1fr)] gap-[28px] items-start md:[grid-template-columns:minmax(0,1.4fr)_minmax(0,0.8fr)] md:gap-[32px] xl:gap-[44px]">
                <div class="modal-media min-w-[0] [order:1]">
                    @if ($entry['video'])
                        <video data-player-video data-player-src="{{ $entry['video'] }}" @if ($entry['poster']) poster="{{ $entry['poster'] }}" @endif aria-label="{{ $entry['title'] }} — full film" controls playsinline preload="none" class="block w-[100%] max-h-[66dvh] object-contain [background:#000] rounded-[12px]"></video>
                    @endif
                    @if ($entry['poster'])
                        <img data-player-poster @if ($entry['video']) hidden @endif src="{{ $entry['poster'] }}" alt="{{ $entry['title'] }}" @if ($entry['posterWidth'] !== null) width="{{ $entry['posterWidth'] }}" height="{{ $entry['posterHeight'] }}" @endif loading="lazy" decoding="async" class="block w-[100%] max-h-[66dvh] object-contain [background:#000] rounded-[12px]">
                    @endif
                    @if ($entry['video'])
                        <p data-player-status role="status" class="text-[14px] [color:var(--muted-foreground)] [&:not(:empty)]:mt-[12px]"></p>
                    @endif
                </div>
                <div class="modal-copy min-w-[0] [order:2]">
                    <p class="modal-category pt-[0] mb-[14px] text-[13px] [color:var(--muted-foreground)]">{{ $entry['service'] }}</p>
                    <h2 id="detail-title-{{ $entry['slug'] }}" class="[clear:both] mb-[0] text-[30px] leading-[1.1] tracking-[-.05em] [font-weight:600] max-w-[850px] md:text-[32px] xl:text-[38px]">{{ $entry['title'] }}</h2>
                    @if ($entry['summary'] !== '')
                        <p class="modal-description [white-space:pre-line] max-w-[800px] text-[16px] leading-[1.5] [color:var(--muted-foreground)] mt-[24px] tracking-[-.025em]">{{ $entry['summary'] }}</p>
                    @endif
                    @if ($entry['watch'])
                        <a href="{{ $entry['watch'] }}" target="_blank" rel="noopener noreferrer" class="modal-original focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px] [touch-action:manipulation] inline-flex items-center gap-[14px] mt-[24px] pb-[8px] [border-bottom:1px_solid_var(--border)] text-[14px]">
                            {{ $entry['watchLabel'] ? 'Watch on '.$entry['watchLabel'] : 'Watch video' }}
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="[flex-shrink:0]">
                                <path d="M7 17 17 7M7 7h10v10"></path>
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
        </section>
    @endforeach
</dialog>
