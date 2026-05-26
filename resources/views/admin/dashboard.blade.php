<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
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

                            @if ($section->processing_status === 'error' && $section->processing_error)
                                <div class="mt-2 text-sm text-red-600">
                                    {{ $section->processing_error }}
                                </div>
                            @endif

                            <form class="mt-4 space-y-3" method="POST" action="{{ url('/admin/sections/'.$section->id) }}">
                                @csrf
                                @method('PUT')
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Texto descritivo
                                </label>
                                <textarea
                                    name="description_text"
                                    rows="4"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                >{{ old('description_text', $section->description_text) }}</textarea>
                                <button
                                    type="submit"
                                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                                >
                                    Salvar texto
                                </button>
                            </form>

                            <form class="mt-5 space-y-3" method="POST" action="{{ url('/admin/sections/'.$section->id.'/video') }}" enctype="multipart/form-data">
                                @csrf
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Upload do vídeo original
                                </label>
                                <input
                                    type="file"
                                    name="video"
                                    class="block w-full text-sm text-gray-700 dark:text-gray-200"
                                    required
                                >
                                <button
                                    type="submit"
                                    class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600"
                                >
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
                ])->values();
            @endphp

            <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800" x-data="photoManager({{ $photosPayload->toJson() }})">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Fotos do Carrossel</h3>
                    <form method="POST" action="{{ url('/admin/photos') }}" enctype="multipart/form-data" class="flex items-center gap-3">
                        @csrf
                        <input type="file" name="photo" required class="block w-full text-sm text-gray-700 dark:text-gray-200">
                        <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                            Adicionar
                        </button>
                    </form>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    <template x-for="photo in photos" :key="photo.id">
                        <div
                            class="rounded-md border border-gray-200 p-2 dark:border-gray-700"
                            draggable="true"
                            @dragstart="onDragStart(photo.id)"
                            @dragover.prevent
                            @drop="onDrop(photo.id)"
                        >
                            <div class="aspect-[3/2] overflow-hidden rounded bg-gray-100 dark:bg-gray-900">
                                <img
                                    class="h-full w-full object-cover"
                                    :src="photo.photo_webp ? ('/storage/' + photo.photo_webp) : ''"
                                    alt=""
                                >
                            </div>
                            <div class="mt-2 flex items-center justify-between gap-2">
                                <div class="text-xs text-gray-600 dark:text-gray-300">#<span x-text="photo.id"></span></div>
                                <form method="POST" :action="'/admin/photos/' + photo.id" @submit="onDeleteSubmit(photo.id)">
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
                        <form method="POST" action="{{ url('/admin/social-links/'.$link->id) }}" class="rounded-md border border-gray-200 p-4 dark:border-gray-700">
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
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                >
                                <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                    Salvar
                                </button>
                            </div>
                        </form>
                    @endforeach
                </div>
            </section>

            <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Páginas Estáticas</h3>

                <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <form method="POST" action="{{ url('/admin/pages/termos') }}" class="rounded-md border border-gray-200 p-4 dark:border-gray-700">
                        @csrf
                        @method('PUT')
                        <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">Termos de Uso</div>
                        <textarea class="js-editor mt-3 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" name="content" rows="12">{{ old('content', $pageTermos?->content) }}</textarea>
                        <button type="submit" class="mt-3 inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                            Salvar
                        </button>
                    </form>

                    <form method="POST" action="{{ url('/admin/pages/privacidade') }}" class="rounded-md border border-gray-200 p-4 dark:border-gray-700">
                        @csrf
                        @method('PUT')
                        <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">Política de Privacidade</div>
                        <textarea class="js-editor mt-3 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" name="content" rows="12">{{ old('content', $pagePrivacidade?->content) }}</textarea>
                        <button type="submit" class="mt-3 inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                            Salvar
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>

    @vite(['resources/js/admin.js'])
</x-app-layout>
