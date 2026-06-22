<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="admin-wrap space-y-8">
            @if (session('success'))
            <div class="rounded-md bg-green-50 px-4 py-3 text-green-800">
                {{ session('success') }}
            </div>
            @endif

            @if (session('error'))
            <div class="rounded-md bg-red-50 px-4 py-3 text-red-800">
                {{ session('error') }}
            </div>
            @endif

            <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Seções de Vídeo</h3>
                <div id="js-video-processing-banner" class="mt-3 hidden rounded-md bg-amber-50 px-4 py-3 text-amber-900"></div>
                <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    @foreach ($sections as $section)
                    <div class="rounded-md border border-gray-200 p-4 dark:border-gray-700">
                        <div class="flex items-center justify-between gap-3">
                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                {{ $section->slug }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">
                                Status:
                                <span class="js-section-status font-semibold" data-section-id="{{ $section->id }}">
                                    {{ $section->processing_status }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Atualizado em: {{ $section->updated_at?->format('Y-m-d H:i:s') ?? '—' }} · Texto: {{ mb_strlen((string) $section->description_text) }} caracteres
                        </div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Vídeos:
                            {{ $section->video_public_id ?? '—' }}
                        </div>

                        @if ($section->processing_status === 'error' && $section->processing_error)
                        <div class="mt-2 text-sm text-red-600">
                            {{ $section->processing_error }}
                        </div>
                        @endif

                        <form class="mt-4 space-y-3 js-admin-form" method="POST" action="{{ url('/admin/sections/'.$section->id) }}" data-loading-text="Salvando...">
                            @csrf
                            @method('PUT')
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Título
                                </label>
                                <input
                                    type="text"
                                    name="title"
                                    value="{{ old('title', $section->title) }}"
                                    class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Texto descritivo
                                </label>
                                <textarea
                                    name="description_text"
                                    rows="4"
                                    class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">{{ old('description_text', $section->description_text) }}</textarea>
                            </div>

                            <button
                                type="submit"
                                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                Salvar textos
                            </button>
                        </form>

                        <form class="mt-5 space-y-3 js-admin-form js-video-upload-form" method="POST" action="{{ url('/admin/sections/'.$section->id.'/video') }}" enctype="multipart/form-data" data-loading-text="Enviando...">
                            @csrf
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                Upload do vídeo original
                            </label>
                            <input
                                type="file"
                                name="video"
                                class="block w-full text-sm text-gray-700 dark:text-gray-200"
                                required>
                            <button
                                type="submit"
                                class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600">
                                Enviar e processar
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
            </section>

            @php
            $photosPayload = collect($photos)->map(fn ($p) => [
            'id' => $p->id,
            'photo_webp' => $p->photo_webp,
            'title' => $p->title,
            ])->values();
            @endphp

            <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Fotos do Carrossel e Textos do Rodapé</h3>
                </div>
                
                <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <form method="POST" action="{{ url('/admin/pages/rodape_titulo') }}" class="rounded-md border border-gray-200 p-4 dark:border-gray-700 js-admin-form" data-loading-text="Salvando...">
                        @csrf
                        @method('PUT')
                        <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">Título do Rodapé (h1)</div>
                        <input type="text" class="mt-3 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" name="content" value="{{ old('content', $pageRodapeTitulo?->content) }}">
                        <button type="submit" class="mt-3 inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                            Salvar Título
                        </button>
                    </form>

                    <form method="POST" action="{{ url('/admin/pages/rodape_subtitulo') }}" class="rounded-md border border-gray-200 p-4 dark:border-gray-700 js-admin-form" data-loading-text="Salvando...">
                        @csrf
                        @method('PUT')
                        <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">Subtítulo do Rodapé (h4)</div>
                        <textarea class="mt-3 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" name="content" rows="3">{{ old('content', $pageRodapeSubtitulo?->content) }}</textarea>
                        <button type="submit" class="mt-3 inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                            Salvar Subtítulo
                        </button>
                    </form>
                </div>

                <div class="mt-8 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100">Gerenciar Fotos</h4>
                    <form method="POST" action="{{ url('/admin/photos') }}" enctype="multipart/form-data"
                        class="flex items-center gap-3 js-admin-form" data-loading-text="Adicionando...">
                        @csrf
                        <input type="file" name="photo" accept="image/*" required
                            class="block w-full text-sm text-gray-700 dark:text-gray-200">
                        <input type="text" name="title" required maxlength="120" placeholder="Título da foto"
                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        <button type="submit"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                            Adicionar Foto
                        </button>
                    </form>
                </div>
            </section>
            <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800" x-data="photoManager({{ $photosPayload->toJson() }})">
                <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    <template x-for="photo in photos" :key="photo.id">
                        <div
                            class="rounded-md border border-gray-200 p-2 dark:border-gray-700"
                            draggable="true"
                            @dragstart="onDragStart(photo.id)"
                            @dragover.prevent
                            @drop="onDrop(photo.id)">
                            <div class="aspect-[3/2] overflow-hidden rounded bg-gray-100 dark:bg-gray-900">
                                <img
                                    class="h-full w-full object-cover"
                                    :src="photo.photo_webp ? ('/storage/' + photo.photo_webp) : ''"
                                    alt="">
                            </div>
                            <div class="mt-2 flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="text-xs text-gray-600 dark:text-gray-300">#<span x-text="photo.id"></span></div>
                                    <div class="truncate text-xs text-gray-700 dark:text-gray-200" x-text="photo.title || ''"></div>
                                </div>
                                <form method="POST" :action="'/admin/photos/' + photo.id" class="js-admin-form" data-loading-text="Excluindo..." @submit.prevent="onDeleteSubmit($event, photo.id)">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Excluir</button>
                                </form>
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Links das Redes Sociais</h3>
                <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
                    @foreach ($socialLinks as $link)
                    <form method="POST" action="{{ url('/admin/social-links/'.$link->id) }}" class="rounded-md border border-gray-200 p-4 dark:border-gray-700 js-admin-form" data-loading-text="Salvando...">
                        @csrf
                        @method('PUT')
                        <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            {{ $link->platform }}
                        </div>
                        <div class="mt-3 flex items-center gap-3">
                            <input
                                type="url"
                                name="url"
                                value="{{ old('url', $link->url) }}"
                                placeholder="https://..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                Salvar
                            </button>
                        </div>
                    </form>
                    @endforeach
                </div>
            </section>

        </div>
    </div>

    @vite(['resources/js/admin.js'])
</x-app-layout>
