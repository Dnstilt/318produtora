@extends('layouts.landing')

@section('content')
    <div id="landing-root">
        <div class="fixed left-0 top-0 z-50 h-screen w-[90px] px-3 py-6 xl:w-[130px]">
            <div class="flex flex-col gap-6">
                <a href="#publicidade" class="block items-center justify-center opacity-80 hover:opacity-100 transition-opacity">
                    <img id="landing-navbar-logo" src="{{ asset(config('app.logo_path')) }}" alt="318 Produtora" class="h-[56px] w-[80px] opacity-0 drop-shadow-[2px_2px_3px_rgba(0,0,0,1)] sm:h-[64px] sm:w-[92px] lg:h-[72px] lg:w-[110px] xl:h-[80px] xl:w-[120px]" />
                </a>

                <nav class="font-unineue flex flex-col items-start gap-3">
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
                            class="nav-item js-nav-item group relative flex h-10 items-center justify-center text-[#FF4F00] text-bold drop-shadow-[2px_2px_3px_rgba(0,0,0,1)]"
                            data-target="{{ $item['id'] }}"
                        >
                            <span class="nav-icon flex h-9 w-9 items-center justify-center sm:h-10 sm:w-10 lg:h-11 lg:w-11 xl:h-12 xl:w-12">
                                <span class="h-4 w-4 rounded-full border border-[#FF4F00] drop-shadow-[2px_2px_3px_rgba(0,0,0,1)] sm:h-5 sm:w-5 lg:h-5 lg:w-5 xl:h-6 xl:w-6"></span>
                            </span>
                            <span class="nav-label px-2 tracking-wide text-xl">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>
        </div>

        <main class="relative">
            @php
                $frames = [
                    ['id' => 'publicidade', 'slug' => 'publicidade', 'preload' => 'auto'],
                    ['id' => 'ooh', 'slug' => 'ooh', 'preload' => 'none'],
                    ['id' => 'documentarios', 'slug' => 'documentarios', 'preload' => 'none'],
                    ['id' => 'natureza', 'slug' => 'natureza', 'preload' => 'none'],
                ];
            @endphp

            <div id="frames-wrapper" class="relative h-screen w-screen overflow-hidden">
                @foreach ($frames as $frame)
                    @php($section = $sections[$frame['slug']] ?? null)
                    <section
                        id="{{ $frame['id'] }}"
                        class="js-frame absolute inset-0 h-screen w-screen overflow-hidden"
                        data-frame="{{ $frame['id'] }}"
                    >
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
                            data-mobile-mp4="{{ $section?->video_mp4_mobile ? asset('storage/'.$section->video_mp4_mobile) : '' }}"
                        ></video>

                    <div class="pointer-events-none absolute inset-0 flex flex-col justify-end items-center pb-16 px-6 md:justify-center md:items-end md:pb-0 md:pr-16 lg:pr-24">
                        <div class="js-frame-text w-full max-w-[850px] text-center md:text-right drop-shadow-[2px_2px_3px_rgba(0,0,0,1)]">
                            @if($section?->title)
                                <h2 class="text-4xl md:text-6xl lg:text-7xl font-pragextobl text-[#FF4F00] mb-2 md:mb-4">
                                    {{ $section->title }}
                                </h2>
                            @endif
                            @if($section?->description_text)
                                <p class="font-pragext text-[#FF4F00] text-xl md:text-3xl lg:text-4xl">
                                    {{ $section->description_text }}
                                </p>
                            @endif
                        </div>
                    </div>
                    </section>
                @endforeach
            </div>

            <footer id="rodape" class="relative min-h-screen w-screen bg-black">
            <div class="px-6 pt-16 sm:pt-24">
                @if($rodapeTitulo)
                    <h1 class="text-3xl text-end md:text-5xl lg:text-6xl font-bold text-[#FF4F00] opacity-80 drop-shadow-[2px_2px_3px_rgba(0,0,0,1)] mb-4 font-pragextobl">
                        {{ $rodapeTitulo }}
                    </h1>
                @endif
                @if($rodapeSubtitulo)
                    <h4 class="text-lg md:text-xl font-pragext text-end text-gray-300 mb-10 max-w-3xl mx-auto">
                        {{ $rodapeSubtitulo }}
                    </h4>
                @endif
                <div class="swiper js-swiper">
                    <div class="swiper-wrapper">
                        @foreach (collect($photos)->take(12) as $photo)
                            <div class="swiper-slide">
                                <picture>
                                    @if ($photo->photo_avif)
                                        <source srcset="{{ asset('storage/'.$photo->photo_avif) }}" type="image/avif">
                                    @endif
                                    @if ($photo->photo_webp)
                                        <source srcset="{{ asset('storage/'.$photo->photo_webp) }}" type="image/webp">
                                    @endif
                                    <img
                                        src="{{ $photo->photo_jpg ? asset('storage/'.$photo->photo_jpg) : '' }}"
                                        alt=""
                                        class="h-[420px] w-full object-cover"
                                        loading="lazy"
                                    >
                                </picture>
                            </div>
                        @endforeach
                    </div>

                    <div class="swiper-button-prev"></div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>

            <div class="mx-auto mt-12 flex max-w-5xl flex-col items-center gap-6 px-6 pb-16 text-center">
                <a href="#publicidade" class="block items-center justify-center opacity-80 hover:opacity-100 transition-opacity">
                    <img id="landing-footer-logo" src="{{ asset(config('app.logo_path')) }}" alt="318 Produtora" class="h-[56px] w-[80px] drop-shadow-[2px_2px_3px_rgba(0,0,0,1)] sm:h-[64px] sm:w-[92px] lg:h-[72px] lg:w-[110px] xl:h-[80px] xl:w-[120px]" />
                </a>
                <div class="flex flex-wrap items-center justify-center gap-6 text-sm">
                    <a class="underline-offset-4 hover:underline" href="mailto:contato@site.com">Fale Conosco</a>
                    <a class="underline-offset-4 hover:underline" href="{{ url('/termos-de-uso') }}">Termos de Uso</a>
                    <a class="underline-offset-4 hover:underline" href="{{ url('/politica-de-privacidade') }}">Política de Privacidade</a>
                </div>

                <div class="flex items-center justify-center gap-4">
                    @foreach ($socialLinks as $link)
                        <a
                            href="{{ $link->url ?: '#' }}"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#FF4F00]/60 text-[#FF4F00]/90 hover:text-[#FF4F00] hover:border-[#FF4F00] transition-colors"
                            target="{{ $link->url ? '_blank' : '_self' }}"
                            rel="noopener noreferrer"
                            aria-label="{{ $link->platform }}"
                        >
                            @if($link->platform === 'instagram')
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                            @elseif($link->platform === 'youtube')
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                            @elseif($link->platform === 'vimeo')
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22.396 7.164c-.093 2.026-1.507 4.8-4.245 8.32C15.323 19.161 12.93 21 11.003 21c-1.562 0-2.955-1.933-4.18-5.801L5.05 6.55c-.563-2.091-1.39-2.185-2.483-2.185H.813V2.428h2.641c2.148 0 3.738 1.488 4.766 4.464l1.378 5.438c.677 2.656 1.43 3.984 2.257 3.984 1.137 0 2.871-2.158 5.204-6.474.966-1.745 1.258-3.033.876-3.864-.475-1.03-1.631-.968-3.468-.621 1.054-3.23 3.65-4.845 7.784-4.845 2.518 0 3.96 1.83 4.145 6.134z"/></svg>
                            @elseif($link->platform === 'whatsapp')
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.52 3.449C18.24 1.245 15.24 0 12.045 0 5.463 0 .104 5.334.101 11.895c-.002 2.094.546 4.143 1.588 5.945L.057 24l6.305-1.642c1.736.945 3.693 1.442 5.676 1.444h.004c6.58 0 11.942-5.336 11.945-11.897.001-3.17-1.228-6.155-3.467-8.456zm-8.473 19.34h-.003c-1.77 0-3.513-.473-5.04-1.365l-.36-.21-3.743.975.996-3.626-.233-.368C2.65 16.035 2.11 14.015 2.112 11.897c.002-5.456 4.464-9.897 9.943-9.897 2.655 0 5.148 1.026 7.025 2.888 1.876 1.862 2.91 4.341 2.908 6.985-.003 5.458-4.467 9.896-9.941 9.916zm5.453-7.408c-.299-.148-1.768-.867-2.043-.966-.275-.098-.475-.148-.674.148-.2.296-.773.966-.948 1.164-.175.197-.35.221-.65.074-.299-.148-1.261-.461-2.404-1.474-.89-.788-1.49-1.761-1.665-2.057-.175-.296-.019-.456.131-.604.135-.133.299-.345.449-.518.15-.173.2-.296.299-.493.1-.197.05-.37-.025-.518-.075-.148-.674-1.611-.923-2.206-.243-.579-.491-.5-.674-.509-.174-.009-.374-.011-.574-.011-.2 0-.524.074-.799.37-.275.296-1.049 1.011-1.049 2.466 0 1.455 1.074 2.861 1.224 3.058.15.197 2.1 3.181 5.087 4.455.712.304 1.267.485 1.699.621.714.225 1.365.193 1.878.117.575-.085 1.768-.716 2.018-1.408.25-.692.25-1.285.175-1.408-.075-.124-.275-.198-.574-.347z"/></svg>
                            @endif
                        </a>
                    @endforeach
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
