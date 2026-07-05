# 318 Produtora — Laravel 11

Sistema com landpage e área admin para upload de vídeos e fotos além de suas conversões.

## Requisitos

- PHP 8.2+
- Composer
- Node.js + NPM
- MySQL
- FFmpeg + FFprobe instalados e acessíveis no PATH
- Extensão PHP `gd` (ou `imagick`) para o Intervention Image

## Instalação

1. Instale dependências PHP:

   ```bash
   composer install
   ```

2. Copie e ajuste o ambiente:

   ```bash
   copy .env.example .env
   php artisan key:generate
   ```

3. Configure MySQL no `.env`:

   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

4. Crie tabelas e seed do admin:

   ```bash
   php artisan migrate --seed
   ```

5. Link do storage público:

   ```bash
   php artisan storage:link
   ```

6. Frontend (Tailwind via Vite):

   ```bash
   npm install
   npm run build
   ```

7. Inicie o servidor:

   ```bash
   php artisan serve
   ```

## Acesso admin

- URL: `/admin/login`
- Email: `admin@site.com`
- Senha: `Admin@1234`

Não sobe o seeder em produção, se subir crie um novo admin para usar no seeder e apague.

## Fila (processamento de vídeo)

O upload de vídeo dispara o job `ProcessVideoJob` via queue. Use `QUEUE_CONNECTION=database` e execute:

```bash
php artisan queue:work
```

## FFmpeg

Por padrão o app usa:

API do cloudnary para transformações de vídeo tanto para pc como para mobile. Foi escolhida essa arquitetura pois para vps compartilhada não tem acesso a instalar o ffmpeg localmente. Se usar vps própria, pode instalar o ffmpeg e ajustar no `.env`: assim como criar a variável de ambiente `FFMPEG_BINARY` e `FFPROBE_BINARY`. Também modificar controller `AdminControllerController` para direcionar ao service e fazer as conversões.

- `FFMPEG_BINARY=ffmpeg`
- `FFPROBE_BINARY=ffprobe`

Se estiverem fora do PATH, ajuste no `.env` com o caminho completo para os executáveis.

## Armazenamento de mídia

- Vídeos convertidos: `storage/app/public/videos/`
- Fotos convertidas: `storage/app/public/photos/`

