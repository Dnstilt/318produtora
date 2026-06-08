@extends('layouts.landing')

@section('content')
<div id="landing-root">

    <div id="sidebar-nav" class="fixed left-0 top-0 z-50 h-screen w-[130px] px-3 py-6 xl:w-[200px] transition-transform duration-500">
        <div class="flex flex-col gap-6">
            <a href="#publicidade" class="block items-center justify-center opacity-100 hover:opacity-80 transition-opacity" data-target="publicidade">
                <img id="landing-navbar-logo" src="{{ asset(config('app.logo_path')) }}" alt="318 Produtora" class="h-[72px] w-[110px] sm:h-[64px] sm:w-[92px] lg:h-[72px] lg:w-[110px] xl:h-[80px] xl:w-[120px]" />
            </a>

            <nav class="font-juana flex flex-col items-start gap-3">
                @php
                $nav = [
                ['id' => 'publicidade', 'label' => 'Publicidade'],
                ['id' => 'ooh', 'label' => 'OOH'],
                ['id' => 'documentarios', 'label' => 'Documentários'],
                ['id' => 'natureza', 'label' => 'Natureza'],
                ['id' => 'rodape', 'label' => 'Fotos e Contato'],
                ];
                @endphp

                @foreach ($nav as $item)
                <a
                    href="#{{ $item['id'] }}"
                    class="nav-item js-nav-item group relative flex h-10 items-center justify-center text-[#ff2600] text-bold"
                    data-target="{{ $item['id'] }}">
                    <span class="nav-icon flex h-12 w-12 items-center justify-center sm:h-12 sm:w-12 lg:h-13 lg:w-13 xl:h-14 xl:w-14">
                        <span class="h-3/4 w-3/4 rounded-full border-2 border-[#ff2600] sm:h-7 sm:w-7 lg:h-8 lg:w-8 xl:h-9 xl:w-9"></span>
                    </span>
                    <span class="nav-label px-2 tracking-wide text-3xl">{{ $item['label'] }}</span>
                </a>
                @endforeach
            </nav>
        </div>
    </div>

    <div id="cursor-bracket" aria-hidden="true"></div>
    <div id="cursor-ring" aria-hidden="true"></div>
    <main class="relative">
        @php
        $frames = [
        ['id' => 'publicidade', 'slug' => 'publicidade', 'preload' => 'auto'],
        ['id' => 'ooh', 'slug' => 'ooh', 'preload' => 'none'],
        ['id' => 'documentarios', 'slug' => 'documentarios', 'preload' => 'none'],
        ['id' => 'natureza', 'slug' => 'natureza', 'preload' => 'none'],
        ];
        @endphp

        <div id="frames-wrapper" class="relative h-screen w-screen overflow-hidden bg-[#ff2600]">
            @foreach ($frames as $frame)
            @php
            $section = $sections[$frame['slug']] ?? null;
            @endphp
            <section
                id="{{ $frame['id'] }}"
                class="js-frame absolute inset-0 h-screen w-screen overflow-hidden"
                data-frame="{{ $frame['id'] }}">
                <video
                    class="js-frame-video absolute inset-0 h-full w-full object-cover"
                    autoplay
                    muted
                    loop
                    playsinline
                    preload="{{ $frame['preload'] }}"
                    data-preload="{{ $frame['preload'] }}"
                    data-desktop-webm="{{ $section?->video_webm_desktop ? asset('storage/'.$section->video_webm_desktop) : '' }}"
                    data-desktop-mp4="{{ $section?->video_mp4_desktop ? asset('storage/'.$section->video_mp4_desktop) : '' }}"
                    data-mobile-webm="{{ $section?->video_webm_mobile ? asset('storage/'.$section->video_webm_mobile) : '' }}"
                    data-mobile-mp4="{{ $section?->video_mp4_mobile ? asset('storage/'.$section->video_mp4_mobile) : '' }}"></video>

                <div class="pointer-events-none absolute inset-0 flex flex-col justify-end items-center pb-16 px-6 md:justify-center md:items-end md:pb-0 md:pr-16 lg:pr-24">
                    <div class="js-frame-text w-full text-center max-w-[900px] md:text-right md:max-w-[70vw] lg:max-w-[80vw]">
                        @if($section?->title)
                        <h2 class="text-5xl md:text-7xl lg:text-9xl font-juana text-[#ff2600] mb-4 md:mb-6 lg:mb-8 break-words leading-tight">
                            {{ $section->title }}
                        </h2>
                        @endif
                        @if($section?->description_text)
                        <p class="font-pragext text-[#ffffff] text-3xl md:text-3xl lg:text-4xl break-words">
                            {{ $section->description_text }}
                        </p>
                        @endif
                    </div>
                </div>
            </section>
            @endforeach
        </div>

        <footer id="rodape" class="gallery-bg relative min-h-screen w-screen">
            <div class="crt-grain" aria-hidden="true"></div>
            <div class="crt-scanlines" aria-hidden="true"></div>
            <div class="crt-vignette" aria-hidden="true"></div>
            <div class="px-6 pt-16 sm:pt-24 relative z-10">
                @if($rodapeTitulo)
                <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold text-[#ff2600] mb-4 font-juana text-center">
                    <span class="line-wrap"><span class="line-inner footer-line" data-delay="0">{{ $rodapeTitulo }}</span></span>
                </h1>
                @endif

                @if($rodapeSubtitulo)
                <h4 class="text-lg md:text-xl font-pragext text-center text-[#ff2600] mb-10">
                    <span class="line-wrap"><span class="line-inner footer-line" data-delay="280">{{ $rodapeSubtitulo }}</span></span>
                </h4>
                @endif

                <section class="w-full pb-12" id="gallery-carousel">
                    <div class="carousel-wrap" id="carousel-wrap">
                        <span id="carousel-tot" style="display:none">{{ $photos->count() }}</span>
                        <div class="carousel-track" id="carousel-track">
                            {{-- 3 cópias para loop infinito --}}
                            @foreach ([0, 1, 2] as $copy)
                            @foreach ($photos ?? collect() as $index => $photo)
                            <div class="carousel-slide {{ $copy === 1 && $index === 0 ? 'is-active' : 'is-side' }}"
                                data-idx="{{ $index }}"
                                data-title="{{ $photo->title ?? 'Foto ' . ($index + 1) }}">
                                <picture>
                                    @if ($photo->photo_avif)
                                    <source srcset="{{ asset('storage/'.$photo->photo_avif) }}" type="image/avif">
                                    @endif
                                    @if ($photo->photo_webp)
                                    <source srcset="{{ asset('storage/'.$photo->photo_webp) }}" type="image/webp">
                                    @endif
                                    <img class="carousel-img"
                                        src="{{ $photo->photo_jpg ? asset('storage/'.$photo->photo_jpg) : '' }}"
                                        alt="Foto {{ $index + 1 }}">
                                </picture>
                                <div class="carousel-overlay {{ $copy === 1 && $index === 0 ? 'is-visible' : '' }}">
                                </div>
                            </div>
                            @endforeach
                            @endforeach
                        </div>
                    </div>
                </section>
            </div>

            <div class="mx-auto mt-12 flex max-w-5xl flex-col items-center gap-6 px-6 text-center relative z-10">
                <a href="#publicidade" class="flip-logo-wrap block items-center opacity-100 hover:opacity-80 transition-opacity lg:pb-5 js-nav-item"
                    data-src="{{ asset(config('app.logo_path')) }}" aria-label="318 Produtora" data-target="publicidade">
                    <img
                        src="{{ asset(config('app.logo_path')) }}"
                        alt="318 Produtora"
                        class="w-auto h-32 sm:h-40 md:h-48 lg:h-56 xl:h-64 object-contain mx-auto" />
                </a>
                <div class="flex items-center justify-center gap-4">
                    @foreach ($socialLinks as $link)
                    <a
                        href="{{ $link->url ?: '#' }}"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#FF2600]/60 text-[#FF2600]/90 hover:text-[#FF2600] hover:border-[#FF2600] transition-colors"
                        target="{{ $link->url ? '_blank' : '_self' }}"
                        rel="noopener noreferrer"
                        aria-label="{{ $link->platform }}">
                        @if($link->platform === 'instagram')
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z" />
                        </svg>
                        @elseif($link->platform === 'youtube')
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
                        </svg>
                        @elseif($link->platform === 'vimeo')
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M22.396 7.164c-.093 2.026-1.507 4.8-4.245 8.32C15.323 19.161 12.93 21 11.003 21c-1.562 0-2.955-1.933-4.18-5.801L5.05 6.55c-.563-2.091-1.39-2.185-2.483-2.185H.813V2.428h2.641c2.148 0 3.738 1.488 4.766 4.464l1.378 5.438c.677 2.656 1.43 3.984 2.257 3.984 1.137 0 2.871-2.158 5.204-6.474.966-1.745 1.258-3.033.876-3.864-.475-1.03-1.631-.968-3.468-.621 1.054-3.23 3.65-4.845 7.784-4.845 2.518 0 3.96 1.83 4.145 6.134z" />
                        </svg>
                        @endif
                    </a>
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center justify-center gap-6 text-sm">
                    <p class="font-pragext text-[#FF2600]">
                        ©2026 318 Produtora e Website Urutau®.
                    </p>
                </div>
            </div>
        </footer>
    </main>
</div>

<div id="landing-loading-overlay" aria-hidden="true"></div>
<div id="landing-loading-logo-wrapper" aria-hidden="true">
    <img src="{{ asset(config('app.logo_path')) }}" alt="318 Produtora" />
</div>
<div id="landing-loading-progress" aria-hidden="true">
    <div id="landing-loading-progress-track">
        <div id="landing-loading-progress-bar"></div>
    </div>
    <div id="landing-loading-progress-label">0%</div>
</div>
@endsection