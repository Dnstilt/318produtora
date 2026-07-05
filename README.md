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

   composer install

2. Copie e ajuste o ambiente:

copy .env.example .env
php artisan key:generate


3. Configure MySQL no `.env`:

   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

4. Crie tabelas e seed do admin:

php artisan migrate --seed

5. Link do storage público:

php artisan storage:link


6. Frontend (Tailwind via Vite):

npm install
npm run build


7. Inicie o servidor:

php artisan serve


## Acesso admin

- URL: `/admin/login`
- Email: `admin@site.com`
- Senha: `Admin@1234`

Não sobe o seeder em produção, se subir crie um novo admin para usar no seeder e apague.

## Fila (processamento de vídeo)

O upload de vídeo dispara o job `ProcessVideoJob` via queue. Use `QUEUE_CONNECTION=database` e execute:

php artisan queue:work


## FFmpeg

Por padrão o app usa:

API do cloudnary para transformações de vídeo tanto para pc como para mobile. Foi escolhida essa arquitetura pois para vps compartilhada não tem acesso a instalar o ffmpeg localmente. Se usar vps própria, pode instalar o ffmpeg e ajustar no `.env`: assim como criar a variável de ambiente `FFMPEG_BINARY` e `FFPROBE_BINARY`. Também modificar controller `AdminControllerController` para direcionar ao service e fazer as conversões.

- `FFMPEG_BINARY=ffmpeg`
- `FFPROBE_BINARY=ffprobe`

Se estiverem fora do PATH, ajuste no `.env` com o caminho completo para os executáveis.

## Armazenamento de mídia

- Vídeos convertidos: `storage/app/public/videos/`
- Fotos convertidas: `storage/app/public/photos/`

318 Produtora — Laravel 11

Sistema com landing page e área administrativa para upload de vídeos e fotos, com conversão automática via Cloudinary.

Arquitetura de mídia

O processamento de vídeo não usa FFmpeg local — toda conversão (compressão, formatos WebM/MP4) é feita pela API da Cloudinary. Essa escolha existe porque o projeto roda em hospedagem compartilhada (Hostgator), onde não há acesso para instalar FFmpeg no servidor.

Cada seção da landing page recebe dois vídeos independentes, enviados separadamente no admin:


Vídeo desktop (horizontal) — usado em telas de computador e tablet.
Vídeo mobile (vertical) — usado em smartphones, sem nenhum recorte/crop automático a partir do vídeo desktop.


Cada um tem seu próprio fluxo de upload, fila de processamento e status (processing_status/mobile_processing_status), e não interferem um no outro.

No frontend, a página sempre tenta servir o arquivo já baixado no próprio servidor primeiro; se ele ainda não existir localmente (processamento em andamento ou falhou), cai automaticamente para a URL de transformação direta da Cloudinary como fallback.

Requisitos

PHP 8.2+
Composer
Node.js + NPM
MySQL

Conta Cloudinary (plano Free suporta o projeto, respeitando os limites abaixo)
Extensão PHP gd (ou imagick) para o Intervention Image
Extensão PHP pdo_sqlite habilitada (necessária apenas para rodar a suíte de testes automatizados — veja seção Testes)

Instalação

Instale dependências PHP:

composer install

Copie e ajuste o ambiente:

copy .env.example .env
php artisan key:generate


Configure MySQL no .env:

DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD



Configure a Cloudinary no .env:

CLOUDINARY_URL (ou CLOUDINARY_CLOUD_NAME, conforme o config/cloudinary.php)


Crie tabelas e seed do admin:

php artisan migrate --seed


Link do storage público:


php artisan storage:link


Frontend (Tailwind via Vite):


npm install
npm run build


Inicie o servidor:

php artisan serve

Acesso admin


URL: /admin/login
Email: admin@site.com
Senha: Admin@1234


Não suba o seeder de admin em produção. Se subir por engano, crie imediatamente um novo usuário admin com credenciais próprias e apague (ou desative) o usuário do seeder.

Fila (processamento de vídeo)

Cada upload de vídeo dispara um job assíncrono:


Vídeo desktop → ProcessVideoJob
Vídeo mobile → ProcessMobileVideoJob


Ambos são independentes entre si — processar um não bloqueia nem afeta o outro. Use QUEUE_CONNECTION=database e mantenha um worker ativo:

php artisan queue:work

Sem um worker rodando, os uploads ficam presos em pending indefinidamente (tanto em desenvolvimento local quanto em produção).

Limite de tamanho dos vídeos

A conta Cloudinary no plano Free tem um limite de 100MB por arquivo de vídeo. Os dois formulários de upload (desktop e mobile) validam esse limite antes de enviar o arquivo para processamento — uploads acima de 100MB são rejeitados com uma mensagem clara, evitando falhas silenciosas na chamada à API.

Se a conta for migrada para um plano pago com limite maior, ajuste a regra max:102400 (em KB) nos arquivos:


app/Http/Requests/UploadSectionVideoRequest.php
app/Http/Requests/UploadSectionMobileVideoRequest.php

Sincronização para produção (rsync)

Após o download das variantes processadas pela Cloudinary, os Jobs (ProcessVideoJob/ProcessMobileVideoJob) podem sincronizar os arquivos para o diretório público servido pelo Hostgator via rsync. Isso só acontece quando ambas as condições são verdadeiras:


O ambiente da aplicação é production (APP_ENV=production).
As variáveis de origem e destino estão configuradas no .env:


   RSYNC_VIDEOS_SOURCE=/caminho/completo/de/origem/
   RSYNC_VIDEOS_DEST=/caminho/completo/de/destino/

Em desenvolvimento local (Windows, Linux ou Mac), o rsync nunca é executado, mesmo que essas variáveis estejam preenchidas — o guard de ambiente tem prioridade. Isso evita que caminhos de produção (Linux) quebrem o Job ao rodar localmente.

Se no futuro for necessário um ambiente de staging que também sincronize arquivos, ajuste a checagem de ambiente em ambos os Jobs.

Armazenamento de mídia


Vídeos convertidos: storage/app/public/videos/

Prefixo desktop_ para variantes desktop, mobile_ para variantes mobile — os dois fluxos de limpeza de arquivos antigos respeitam esses prefixos e não se sobrepõem.

Fotos convertidas: storage/app/public/photos/

Testes automatizados

O projeto conta com uma suíte de testes (PHPUnit) cobrindo Form Requests, Controllers, Services, Jobs de vídeo e o serviço de conversão via Cloudinary. Nenhum teste realiza chamadas reais à API da Cloudinary, nem executa o rsync de fato — tudo é mockado ou interceptado via Http::fake()/Storage::fake()/Queue::fake().

Pré-requisito: a extensão pdo_sqlite precisa estar habilitada no PHP, pois os testes rodam contra um banco SQLite em memória (:memory:), totalmente isolado do banco de desenvolvimento/produção.

Para verificar se já está habilitada:
php -m | findstr sqlite   # Windows
php -m | grep sqlite      # Linux/Mac

Se não aparecer pdo_sqlite e sqlite3, descomente as respectivas linhas no php.ini e reinicie o terminal/servidor PHP.

Para rodar a suíte:

php artisan test

