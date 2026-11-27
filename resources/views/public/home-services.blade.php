    <section
      id="services"
      aria-labelledby="services-title"
      class="services-section edge-frame section-space mx-[2px] relative isolate py-[50px] rounded-[25px] overflow-hidden [color:#f5f4ef] [background:#111310] [--services-parallax-distance:24px] md:mx-[4px] md:py-[100px] md:[--services-parallax-distance:48px] xl:mx-[6px] xl:py-[190px] xl:[--services-parallax-distance:72px]"
    >
      <div
        class="services-background absolute [top:calc(0px_-_var(--services-parallax-distance)_-_8px)] right-[0] [bottom:calc(0px_-_var(--services-parallax-distance)_-_8px)] left-[0] z-[-1] pointer-events-none [background:url('/thumbnails/texture.jpg')_center/cover] [filter:grayscale(1)] [opacity:.15] [transform:translate3d(0,0,0)] motion-reduce:[transform:none] motion-reduce:[will-change:auto]"
        aria-hidden="true"
      ></div>
      <div
        class="site-container [width:calc(100%_-_2.5rem)] max-w-[var(--container-site)] mx-[auto] md:[width:calc(100%_-_3rem)] xl:[width:calc(100%_+_-1_*_clamp(72px,_8.75vw_-_40px,_100px))]"
      >
        <div
          class="quarter-heading grid gap-[32px] mb-[50px] md:gap-[4px] md:grid-cols-[repeat(4,minmax(0,1fr))] xl:mb-[80px]"
          data-reveal
        >
          <p class="section-kicker tracking-[-.04em] text-[1rem] [font-weight:500] leading-[1.2]">⊕   What we do.</p>
          <h2
            id="services-title"
            class="display-title tracking-[-.06em] text-[62px] [font-weight:600] leading-[.92] md:text-[99px] md:[grid-column:span_3] md:min-w-[0] xl:text-[144px]"
          >
            Services.<sup
              class="text-[18px] [color:#ffffff70] [vertical-align:top] ml-[12px] leading-[1.5] tracking-[-.04em] md:text-[28px] md:ml-[20px]"
              >({{ count($services) }})</sup
            >
          </h2>
        </div>
        <div class="service-list flex flex-col gap-[0]">
          @foreach ($services as $service)
              @php
                  $isOpen = $loop->first;
                  $expanded = $isOpen ? 'true' : 'false';
                  $panelId = 'service-panel-'.$loop->index;
              @endphp
              <div
                class="service-item group/service [--service-closed-height:68px] [--service-icon-top:16px] grid [grid-template-columns:44px_minmax(0,1fr)] gap-[0] [border:0] md:[--service-closed-height:62px] md:[--service-icon-top:10px] md:[grid-template-columns:25%_minmax(0,1fr)] xl:[--service-closed-height:66px] md:data-[open=true]:[--service-icon-top:30px] md:motion-reduce:data-[open=true]:[--service-icon-top:30px]"
                data-accordion-item
                data-open="{{ $expanded }}"
              >
                <span
                  class="service-index pt-[24px] text-[11px] leading-[1.4] [font-weight:500] tracking-[-.04em] [color:#ffffff99] md:pt-[21px] md:text-[14px] xl:pt-[23px] xl:text-[16px] group-data-[open=true]/service:pt-[16px] md:group-data-[open=true]/service:pt-[30px] motion-reduce:group-data-[open=true]/service:pt-[16px] md:motion-reduce:group-data-[open=true]/service:pt-[30px]"
                  aria-hidden="true"
                  >({{ sprintf('%03d', $loop->iteration) }})</span
                >
                <div
                  class="service-body relative min-w-[0] min-h-[var(--service-closed-height)] overflow-hidden [border-bottom:1px_solid_#ffffff1a]"
                >
                  <h3 class="service-heading absolute inset-[0] z-[2] m-[0]">
                    <button
                      type="button"
                      data-accordion-trigger
                      data-accordion-group="services"
                      aria-label="{{ $service['title'] }}"
                      aria-expanded="{{ $expanded }}"
                      aria-controls="{{ $panelId }}"
                      class="[cursor:pointer] [touch-action:manipulation] absolute inset-[0] w-[100%] h-[100%] p-[0] [border:0] [background:none] [color:inherit] text-left focus-visible:[outline:2px_solid_var(--ring)] focus-visible:[outline-offset:-3px] focus-visible:rounded-[4px] motion-reduce:focus-visible:[outline:2px_solid_var(--ring)] motion-reduce:focus-visible:[outline-offset:-3px] motion-reduce:focus-visible:rounded-[4px]"
                    >
                      <span
                        class="service-title absolute left-[0] right-[52px] top-[22px] block text-[18px] leading-[1.2] [font-weight:500] tracking-[-.04em] [opacity:1] md:right-[82px] md:top-[20px] md:text-[19px] xl:text-[22px] group-data-[open=true]/service:[opacity:0] motion-reduce:group-data-[open=true]/service:[opacity:0]"
                        data-service-collapsed-title
                        aria-hidden="true"
                        >{{ $service['title'] }}</span
                      ><span
                        class="round-icon [transform:rotate(-90deg)] group-data-[open=true]/service:[transform:rotate(0deg)] absolute right-[0] top-[var(--service-icon-top)] grid [place-items:center] w-[36px] h-[36px] [border:1px_solid_#ffffff1a] rounded-[50%] [transform-origin:center] md:w-[42px] md:h-[42px] xl:w-[46px] xl:h-[46px]"
                        data-accordion-icon
                        ><svg
                          width="20"
                          height="20"
                          viewbox="0 0 20 20"
                          fill="none"
                          stroke="currentColor"
                          stroke-width="2"
                          aria-hidden="true"
                          class="[flex-shrink:0] w-[16px] h-[16px] md:w-[20px] md:h-[20px]"
                        >
                          <path
                            d="M10 2v16"
                            data-service-icon-vertical
                            class="[transform-origin:10px_10px] group-data-[open=true]/service:[opacity:0] motion-reduce:group-data-[open=true]/service:[opacity:0]"
                          ></path>
                          <path d="M2 10h16"></path></svg
                      ></span>
                    </button>
                  </h3>
                  <div
                    class="service-panel group-data-[open=true]/service:opacity-100 relative min-w-[0] [opacity:0]"
                    id="{{ $panelId }}"
                    @if (!$isOpen) hidden @endif
                  >
                    <div
                      class="service-panel-inner grid grid-cols-[minmax(0,1fr)] gap-[18px] [padding:16px_36px_26px_0] md:gap-[26px] md:[padding:30px_0_40px] xl:[grid-template-columns:minmax(0,1.66fr)_minmax(0,1fr)] xl:gap-[40px] xl:[padding:30px_86px_70px_0]"
                    >
                      <div class="service-top flex flex-col gap-[24px] min-w-[0] md:gap-[30px] xl:flex-row xl:gap-[44px]">
                        @if ($service['images'] !== [])
                            <div
                              class="service-thumb relative block [flex:0_0_auto] w-[156px] h-[60px] overflow-visible md:h-[80px]"
                              data-service-layer
                              aria-hidden="true"
                            >
                              @foreach ($service['images'] as $image)
                                  @php
                                      $left = ['left-[0]', 'left-[38px]', 'left-[76px]'][$loop->index];
                                      $radius = $loop->first ? 'rounded-[12px]' : 'rounded-[14px]';
                                  @endphp
                                  <span
                                    class="service-frame absolute top-[0] {{ $left }} w-[60px] h-[60px] overflow-hidden {{ $radius }} [box-shadow:-8px_0_16px_#0006] md:w-[80px] md:h-[80px]"
                                  ><img
                                    src="{{ $image['thumb'] ?? $image['url'] }}"
                                    alt=""
                                    loading="lazy"
                                    decoding="async"
                                    width="80"
                                    height="80"
                                    class="w-[100%] h-[100%] object-cover"
                                  /></span>
                              @endforeach
                            </div>
                        @endif
                        <div
                          class="service-info flex flex-col gap-[12px] min-w-[0] md:gap-[16px] xl:gap-[20px]"
                          data-service-layer
                        >
                          <p
                            class="service-expanded-title m-[0] text-[24px] leading-[1.2] [font-weight:500] tracking-[-.04em] md:text-[28px] xl:text-[30px]"
                            aria-hidden="true"
                          >
                            {{ $service['title'] }}
                          </p>
                          <p
                            class="service-description m-[0] [color:#ffffff99] text-[13px] leading-[1.4] [font-weight:500] tracking-[-.04em] md:text-[14px] xl:text-[16px]"
                          >
                            {{ $service['description'] }}
                          </p>
                        </div>
                      </div>
                      @if ($service['tags'] !== [])
                          <div
                            class="service-categories-group flex flex-col gap-[16px] min-w-[0] xl:gap-[18px]"
                            data-service-layer
                          >
                            <p
                              class="service-categories m-[0] text-[11px] [color:#ffffff99] leading-[1.3] tracking-[-.04em] xl:text-[12px]"
                            >
                              Categories
                            </p>
                            <ul class="service-tags flex flex-wrap gap-[8px]">
                              @foreach ($service['tags'] as $tag)
                                  <li
                                    class="rounded-[999px] [background:#f5f4ef] [color:#191b18] [padding:10px_18px] text-[11px] [font-weight:600] leading-[1] tracking-[-.04em] xl:text-[12px]"
                                  >{{ $tag }}</li>
                              @endforeach
                            </ul>
                          </div>
                      @endif
                    </div>
                  </div>
                </div>
              </div>
          @endforeach
        </div>
      </div>
    </section>
