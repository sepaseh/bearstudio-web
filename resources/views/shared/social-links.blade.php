@foreach ($links as $link)
    <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer"
       class="focus-visible:[outline:2px_solid_var(--ring)] focus-visible:outline-offset-[5px] [touch-action:manipulation] inline-flex items-center gap-[8px] text-[14px]">
        {{ $link['label'] }}
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.5" aria-hidden="true" class="[flex-shrink:0]">
            <path d="M7 17 17 7M7 7h10v10"></path>
        </svg></a>
@endforeach
