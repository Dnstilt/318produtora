@extends('layouts.landing')

@section('content')
<div id="landing-root">

    <div id="sidebar-nav" class="fixed left-0 top-0 z-50 h-screen  px-3 py-6 transition-transform duration-500">
        <div class="flex flex-col gap-6">
            <a href="#home" class="logo-navbar block items-center justify-center opacity-100 hover:opacity-80 transition-opacity"
                data-target="home" aria-label="318 Produtora">
                <div id="landing-navbar-logo" class="logo-navbar opacity-0">
                    @include('partials.logo-static')
                </div>
            </a>

            <nav class="font-juana flex flex-col items-start">
                @php
                $nav = [
                ['id' => 'home', 'label' => 'Home'],
                ['id' => 'ooh', 'label' => 'OOH'],
                ['id' => 'eventos', 'label' => 'Eventos'],
                ['id' => 'oque-mais-fazemos', 'label' => 'O que mais fazemos'],
                ['id' => 'fotos', 'label' => 'Fotos e Contato'],
                ];
                @endphp

                @foreach ($nav as $item)
                <a
                    href="#{{ $item['id'] }}"
                    class="nav-item js-nav-item group relative flex items-center justify-center text-[#ff2600] text-bold"
                    data-target="{{ $item['id'] }}">
                    <span class="nav-icon flex items-center justify-center">
                        <span class="nav-icon-circle"></span>
                    </span>
                    <span class="nav-label px-2 tracking-wide">{{ $item['label'] }}</span>
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
        ['id' => 'home', 'slug' => 'home', 'preload' => 'auto'],
        ['id' => 'ooh', 'slug' => 'ooh', 'preload' => 'none'],
        ['id' => 'eventos', 'slug' => 'eventos', 'preload' => 'none'],
        ['id' => 'oque-mais-fazemos', 'slug' => 'oque-mais-fazemos', 'preload' => 'none'],
        ];
        @endphp

        <div id="frames-wrapper" class="relative h-screen w-screen overflow-hidden bg-[#000000]">

            @foreach ($frames as $frame)
            @php
            $section = $sections[$frame['slug']] ?? null;
            @endphp
            <section
                id="{{ $frame['id'] }}"
                class="js-frame absolute inset-0 h-screen w-screen overflow-hidden"
                data-frame="{{ $frame['id'] }}">
                @if($section?->video_public_id)
                <video
                    autoplay muted loop playsinline
                    class="js-frame-video absolute inset-0 h-full w-full object-cover"
                    style="z-index: 1"
                    data-preload="{{ $frame['preload'] }}"
                    data-desktop-webm="{{ $section->video_webm_desktop ? asset('storage/'.$section->video_webm_desktop) : 'https://res.cloudinary.com/'.$cloudName.'/video/upload/w_1920,h_1080,c_fill,vc_vp9,q_auto/'.$section->video_public_id.'.webm' }}"
                    data-desktop-mp4="{{ $section->video_mp4_desktop ? asset('storage/'.$section->video_mp4_desktop) : 'https://res.cloudinary.com/'.$cloudName.'/video/upload/w_1920,h_1080,c_fill,vc_h264,q_auto/'.$section->video_public_id.'.mp4' }}"
                    data-mobile-webm="{{ $section->video_webm_mobile ? asset('storage/'.$section->video_webm_mobile) : 'https://res.cloudinary.com/'.$cloudName.'/video/upload/w_768,h_1280,c_fill,vc_vp9,q_auto/'.$section->video_public_id.'.webm' }}"
                    data-mobile-mp4="{{ $section->video_mp4_mobile ? asset('storage/'.$section->video_mp4_mobile) : 'https://res.cloudinary.com/'.$cloudName.'/video/upload/w_768,h_1280,c_fill,vc_h264,q_auto/'.$section->video_public_id.'.mp4' }}">
                </video>
                @endif
                <div class="frame-content pointer-events-none absolute  inset-0 flex flex-col justify-end items-center px-6 md:justify-center md:pr-16 lg:pr-24" style="z-index: 2">
                    <div class="js-frame-text w-full text-center md:text-right md:max-w-[70vw] lg:max-w-[80vw]">
                        @if($section?->title)
                        <h2 class="frame-title font-juana text-[#ff2600] mb-4 md:mb-6 lg:mb-8 break-words leading-tight">
                            {{ $section->title }}
                        </h2>
                        @endif
                        @if($section?->description_text)
                        <p class="frame-description font-pragext text-[#ffffff] break-words">
                            {{ $section->description_text }}
                        </p>
                        @endif
                    </div>
                </div>
            </section>
            @endforeach
        </div>

        <footer id="fotos" class="gallery-bg relative min-h-screen w-screen">
            <div class="footer-content pt-16 sm:pt-24">
                @if($fotosTitulo)
                <h1 id="footer-title" class="footer-title font-bold text-[#ff2600] mb-4 mt-16 font-juana text-center opacity-0">
                    {{ $fotosTitulo }}
                </h1>
                @endif

                @if($fotosSubtitulo)
                <p id="footer-subtitle" class="footer-subtitle font-pragext text-center text-[#000000ea] opacity-0">
                    {{ $fotosSubtitulo }}
                </p>
                @endif

                <section class="w-full" id="gallery-grid-section">
                    <div class="text-[#ff2600] gallery-grid-wrap">
                        <div class="gallery-col gallery-col-left">
                            @foreach (($photos ?? collect()) as $index => $photo)
                            @if ($index % 2 === 0)
                            <div class="gallery-grid-item-wrap">
                                <div class="gallery-grid-item">
                                    <picture>
                                        @if ($photo->photo_avif)
                                        <source srcset="{{ asset('storage/'.$photo->photo_avif) }}" type="image/avif">
                                        @endif
                                        @if ($photo->photo_webp)
                                        <source srcset="{{ asset('storage/'.$photo->photo_webp) }}" type="image/webp">
                                        @endif
                                        <img class="gallery-grid-img"
                                            src="{{ $photo->photo_jpg ? asset('storage/'.$photo->photo_jpg) : '' }}"
                                            alt="{{ $photo->title ?? 'Foto' }}"
                                            loading="lazy">
                                    </picture>
                                    <div class="gallery-grid-overlay"></div>
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </div>

                        <div class="gallery-col gallery-col-right">
                            @foreach (($photos ?? collect()) as $index => $photo)
                            @if ($index % 2 !== 0)
                            <div class="gallery-grid-item-wrap">
                                <div class="gallery-grid-item">
                                    <picture>
                                        @if ($photo->photo_avif)
                                        <source srcset="{{ asset('storage/'.$photo->photo_avif) }}" type="image/avif">
                                        @endif
                                        @if ($photo->photo_webp)
                                        <source srcset="{{ asset('storage/'.$photo->photo_webp) }}" type="image/webp">
                                        @endif
                                        <img class="gallery-grid-img"
                                            src="{{ $photo->photo_jpg ? asset('storage/'.$photo->photo_jpg) : '' }}"
                                            alt="{{ $photo->title ?? 'Foto' }}"
                                            loading="lazy">
                                    </picture>
                                    <div class="gallery-grid-overlay"></div>
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </div>
                    </div>
                </section>
            </div>
            <div class="info mx-auto grid grid-cols-1 gap-8 lg:grid-cols-2 lg:items-center relative">
                <div class="logofooter flex flex-col items-center lg:items-start justify-center
                    hover:opacity-50">
                    <a href="#home"
                        class="flex justify-center lg:justify-start w-full"
                        data-target="home"
                        aria-label="318 Produtora">
                        @include('partials.logo-static')
                    </a>
                </div>
                <div class="flex flex-col items-center lg:items-end justify-center">
                    <a href="mailto:info@318produtora.com.br?subject=Contato%20via%20Site"
                        class="letter-slide-link font-oldstandard tracking-wider text-[#ff2600]"
                        data-text="info@318produtora.com.br"
                        style="text-decoration: none;">
                    </a>
                    <div class="footer-social-gap flex items-center">
                        @foreach ($socialLinks as $link)
                        <a
                            href="{{ $link->url ?: '#' }}"
                            class="social-icon-wrapper"
                            target="{{ $link->url ? '_blank' : '_self' }}"
                            rel="noopener noreferrer"
                            aria-label="{{ $link->platform }}">

                            @if($link->platform === 'instagram')
                            <svg class="social-icon-svg" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z" />
                            </svg>
                            @elseif($link->platform === 'youtube')
                            <svg class="social-icon-svg" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
                            </svg>
                            @elseif($link->platform === 'vimeo')
                            <svg class="social-icon-svg" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M22.396 7.164c-.093 2.026-1.507 4.8-4.245 8.32C15.323 19.161 12.93 21 11.003 21c-1.562 0-2.955-1.933-4.18-5.801L5.05 6.55c-.563-2.091-1.39-2.185-2.483-2.185H.813V2.428h2.641c2.148 0 3.738 1.488 4.766 4.464l1.378 5.438c.677 2.656 1.43 3.984 2.257 3.984 1.137 0 2.871-2.158 5.204-6.474.966-1.745 1.258-3.033.876-3.864-.475-1.03-1.631-.968-3.468-.621 1.054-3.23 3.65-4.845 7.784-4.845 2.518 0 3.96 1.83 4.145 6.134z" />
                            </svg>
                            @endif
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-6">
                <p class="registro font-pragext text-[#ff2600]">
                    ©2026 318 Produtora e Website Urutau®
                </p>
            </div>
        </footer>
    </main>
</div>

<div id="landing-loading-overlay" aria-hidden="true"></div>
<div id="landing-loading-logo-wrapper" aria-hidden="true">
    @include('partials.logo-loading-svg')
</div>
<div id="landing-loading-progress" aria-hidden="true">
    <div id="landing-loading-progress-track">
        <div id="landing-loading-progress-bar"></div>
    </div>
    <div id="landing-loading-progress-label">0%</div>
</div>
@endsection