# Checklist de Segurança para Aplicação 318 Produtora

## 1. Configuração Básica do Laravel
- [ ] APP_DEBUG desativado em produção
- [ ] APP_KEY seguro e único
- [ ] Arquivos sensíveis não versionados no Git (.env, storage/logs)
- [ ] Permissões de arquivo/diretório adequadas
- [ ] Proteção contra CSRF em todos os formulários

## 2. Autenticação e Autorização
- [ ] Senhas fortes armazenadas com hash seguro
- [ ] Limitação de tentativas de login (rate limiting)
- [ ] Logout seguro (invalidar sessão)
- [ ] Apenas admins podem acessar painel administrativo
- [ ] Validação e autorização em todas as rotas de admin

## 3. Validação de Dados
- [ ] Validação rigorosa em todas as entradas de usuário
- [ ] Sanitização de conteúdo HTML (evitar XSS)
- [ ] Limitação de tamanho de upload de arquivos
- [ ] Verificação de tipos MIME de arquivos carregados

## 4. Banco de Dados
- [ ] Uso de queries parametrizadas (Eloquent/Query Builder)
- [ ] Não exposição de detalhes de erro do banco em produção
- [ ] Senha de banco de dados forte
- [ ] Acesso remoto ao banco desativado (se aplicável)

## 5. Upload de Arquivos
- [ ] Armazenamento de arquivos em diretório não acessível diretamente pela web
- [ ] Validação de extensões e tipos MIME
- [ ] Nomeação segura de arquivos (não usar nomes originais)
- [ ] Verificação de malware/arquivos perigosos (se possível)

## 6. Segurança de Sessão e Cookies
- [ ] Cookies seguros (HTTPS apenas)
- [ ] Same-Site cookies ativados
- [ ] Expiração de sessão configurada adequadamente

## 7. Headers de Segurança
- [ ] X-Frame-Options para evitar clickjacking
- [ ] X-XSS-Protection
- [ ] X-Content-Type-Options
- [ ] Content-Security-Policy (CSP)
- [ ] Strict-Transport-Security (HSTS)

## 8. Dependências
- [ ] Dependências PHP/Composer atualizadas (sem vulnerabilidades conhecidas)
- [ ] Dependências JS/NPM atualizadas
- [ ] Verificação regular de vulnerabilidades (ex: composer audit, npm audit)

## 9. Hospedagem Compartilhada (Hostgator)
- [ ] Arquivos sensíveis (storage/, vendor/, .env) protegidos contra acesso público
- [ ] index.php como único ponto de entrada público
- [ ] Proteção do diretório .git
- [ ] PHP atualizado na hospedagem
