@extends('layouts.landing')

@section('content')
    <div id="landing-root">
        <div class="fixed left-0 top-0 z-50 h-screen w-[90px] px-3 py-6 xl:w-[130px]">
            <div class="flex flex-col gap-6">
                <a href="#publicidade" class="block items-center justify-center opacity-80">
                    <img id="landing-navbar-logo" src="{{ asset(config('app.logo_path')) }}" alt="318 Produtora" class="h-[56px] w-[80px] opacity-0 drop-shadow-[2px_2px_3px_rgba(0,0,0,1)] sm:h-[64px] sm:w-[92px] lg:h-[72px] lg:w-[110px] xl:h-[80px] xl:w-[120px]" />
                </a>

                <nav class="font-patua flex flex-col items-start gap-3">
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

                    <div class="pointer-events-none absolute inset-x-0 bottom-16 flex justify-center px-6">
                        <div class="js-frame-text w-full max-w-[850px] text-center text-[#f8f8f8] drop-shadow-[2px_2px_3px_rgba(0,0,0,1)] text-2xl md:text-4xl lg:text-5xl">
                            {{ $section?->description_text }}
                        </div>
                    </div>
                    </section>
                @endforeach
            </div>

            <footer id="rodape" class="relative min-h-screen w-screen bg-black">
            <div class="px-6 pt-16">
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
                                        src="{{ $photo->photo_jpeg ? asset('storage/'.$photo->photo_jpeg) : '' }}"
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
                <x-application-logo class="h-10 w-10 fill-current text-white drop-shadow-[2px_2px_3px_rgba(0,0,0,1)]" />

                <div class="flex flex-wrap items-center justify-center gap-6 text-sm">
                    <a class="underline-offset-4 hover:underline" href="mailto:contato@site.com">Fale Conosco</a>
                    <a class="underline-offset-4 hover:underline" href="{{ url('/termos-de-uso') }}">Termos de Uso</a>
                    <a class="underline-offset-4 hover:underline" href="{{ url('/politica-de-privacidade') }}">Política de Privacidade</a>
                </div>

                <div class="flex items-center justify-center gap-4">
                    @foreach ($socialLinks as $link)
                        <a
                            href="{{ $link->url ?: '#' }}"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/60 text-white/90 hover:text-white"
                            target="{{ $link->url ? '_blank' : '_self' }}"
                            rel="noopener noreferrer"
                            aria-label="{{ $link->platform }}"
                        >
                            <span class="h-4 w-4 rounded-sm bg-white/70"></span>
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
