import { gsap } from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

/* ============================================================
   CONSTANTES E VARIÁVEIS GLOBAIS
   ============================================================ */
let goToSlide = null;

// IDs dos slides (usados na navegação e ativação)
const SECTION_IDS = ['publicidade', 'ooh', 'documentarios', 'natureza', 'rodape'];

// Configurações de animação
const ANIMATION_DEFAULTS = {
  slideDuration: 1.5,
  frameScale: 0.88,
  frameZ: -220,
  frameOpacity: 0.80,
};

/* ============================================================
   1. LOADING (TELA DE CARREGAMENTO)
   ============================================================ */

/**
 * Inicia o loading com barra de progresso e animação do logo.
 * Retorna uma Promise que resolve quando o loading termina.
 */
function startLandingLoading() {
  const overlay = document.getElementById('landing-loading-overlay');
  const logoWrapper = document.getElementById('landing-loading-logo-wrapper');
  const progress = document.getElementById('landing-loading-progress');
  const bar = document.getElementById('landing-loading-progress-bar');
  const label = document.getElementById('landing-loading-progress-label');
  const navbarLogo = document.getElementById('landing-navbar-logo');

  if (!overlay || !logoWrapper || !progress || !bar || !label || !navbarLogo) {
    // Remove elementos parciais e resolve imediatamente
    overlay?.remove();
    logoWrapper?.remove();
    progress?.remove();
    return Promise.resolve();
  }

  document.body.classList.add('landing-is-loading');
  animateLogoDraw();

  let pct = 0;
  let done = false;

  return new Promise((resolve) => {
    const cleanup = () => {
      if (done) return;
      done = true;

      overlay.style.opacity = '0';
      progress.style.opacity = '0';

      navbarLogo.classList.remove('opacity-0');
      document.body.classList.remove('landing-is-loading');

      setTimeout(() => {
        overlay.remove();
        logoWrapper.remove();
        progress.remove();
        resolve();
      }, 550);
    };

    /**
     * Animação de transição do logo para a navbar.
     */
    const goToNavbar = () => {
      const rect = navbarLogo.getBoundingClientRect();
      if (!rect.width || !rect.height) {
        cleanup();
        return;
      }

      progress.style.opacity = '0';

      requestAnimationFrame(() => {
        logoWrapper.style.top = `${Math.round(rect.top)}px`;
        logoWrapper.style.left = `${Math.round(rect.left)}px`;
        logoWrapper.style.width = `${Math.round(rect.width)}px`;
        logoWrapper.style.height = `${Math.round(rect.height)}px`;
        logoWrapper.style.transform = 'translate(0, 0)';
      });

      const remaining = new Set(['top', 'left', 'width', 'height', 'transform']);

      const onEnd = (e) => {
        remaining.delete(e.propertyName);
        if (remaining.size > 0) return;
        logoWrapper.removeEventListener('transitionend', onEnd);
        cleanup();
      };

      logoWrapper.addEventListener('transitionend', onEnd);

      // Fallback de segurança
      setTimeout(() => {
        logoWrapper.removeEventListener('transitionend', onEnd);
        cleanup();
      }, 1600);
    };

    /**
     * Atualiza a barra de progresso de forma randômica (simula carregamento).
     */
    const tick = () => {
      if (pct >= 100) {
        bar.style.width = '100%';
        label.textContent = '100%';
        setTimeout(goToNavbar, 700);
        return;
      }

      const step = pct < 65
        ? Math.random() * 6 + 1
        : Math.random() * 2.5 + 0.4;

      pct = Math.min(pct + step, 100);
      bar.style.width = `${pct}%`;
      label.textContent = `${Math.floor(pct)}%`;

      const delay = pct < 70
        ? 70 + Math.random() * 55
        : 110 + Math.random() * 90;

      setTimeout(tick, delay);
    };

    setTimeout(tick, 350);
  });
}

/**
 * Anima o traço do SVG do logo (efeito de desenho).
 */
function animateLogoDraw() {
  const svg = document.getElementById('landing-loading-logo-svg');
  if (!svg) return;

  const strokePaths = svg.querySelectorAll('#loading-stroke-layer path');
  const fillLayer = svg.querySelector('#loading-fill-layer');

  strokePaths.forEach((el) => {
    const length = el.getTotalLength();
    el.style.strokeDasharray = length;
    el.style.strokeDashoffset = length;
  });

  const tl = gsap.timeline();

  tl.to('#landing-loading-logo-svg .group-corners path', {
    strokeDashoffset: 0,
    duration: 0.5,
    stagger: 0.08,
    ease: 'power2.out',
  })
    .to('#landing-loading-logo-svg .group-numbers path', {
      strokeDashoffset: 0,
      duration: 0.9,
      stagger: 0.15,
      ease: 'power2.inOut',
    }, '-=0.1')
    .to('#landing-loading-logo-svg .group-letters path', {
      strokeDashoffset: 0,
      duration: 0.9,
      stagger: 0.06,
      ease: 'power2.inOut',
    }, '-=0.3')
    .to('#landing-loading-logo-svg .group-blades path', {
      strokeDashoffset: 0,
      duration: 0.5,
      stagger: 0.05,
      ease: 'power1.inOut',
    }, '-=0.4')
    .to(fillLayer, {
      opacity: 1,
      duration: 0.4,
      ease: 'power1.out',
    }, '+=0.1');

  return tl;
}

/* ============================================================
   2. VÍDEOS – LAZY LOAD E TROCA DE RESOLUÇÃO
   ============================================================ */

/**
 * Detecta se o dispositivo é mobile (largura < 768px).
 */
function isMobile() {
  return window.innerWidth < 768;
}

/**
 * Monta as URLs dos sources com base na variante (mobile/desktop).
 */
function buildSources(videoEl) {
  const variant = isMobile() ? 'mobile' : 'desktop';
  const webm = videoEl.dataset[`${variant}Webm`];
  const mp4 = videoEl.dataset[`${variant}Mp4`];
  return { webm, mp4 };
}

/**
 * Garante que os sources corretos estejam no vídeo e o inicia.
 */
function ensureSources(videoEl) {
  const { webm, mp4 } = buildSources(videoEl);
  if (!webm && !mp4) return;

  const existing = Array.from(videoEl.querySelectorAll('source')).map((s) => s.src);
  if (existing.includes(webm) || existing.includes(mp4)) return;

  videoEl.innerHTML = '';

  if (webm) {
    const source = document.createElement('source');
    source.src = webm;
    source.type = 'video/webm';
    videoEl.appendChild(source);
  }

  if (mp4) {
    const source = document.createElement('source');
    source.src = mp4;
    source.type = 'video/mp4';
    videoEl.appendChild(source);
  }

  videoEl.load();
  videoEl.play().catch(() => {});
}

/**
 * Configura lazy loading dos vídeos e troca automática entre desktop/mobile.
 */
function setupVideoLazyLoad() {
  const frames = Array.from(document.querySelectorAll('.js-frame'));
  const videos = frames.map((f) => f.querySelector('.js-frame-video')).filter(Boolean);

  // Pré-carrega vídeos com data-preload="auto"
  videos.forEach((videoEl) => {
    if (videoEl.dataset.preload === 'auto') {
      ensureSources(videoEl);
    }
  });

  // Intersection Observer para carregar vídeos ao entrar na viewport
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const videoEl = entry.target.querySelector('.js-frame-video');
        if (videoEl) ensureSources(videoEl);
      });
    },
    { root: null, rootMargin: '100% 0px', threshold: 0.01 }
  );

  frames.forEach((frame) => observer.observe(frame));

  // Troca de resolução ao redimensionar (mobile/desktop)
  let lastVariant = isMobile() ? 'mobile' : 'desktop';
  window.addEventListener('resize', () => {
    const nextVariant = isMobile() ? 'mobile' : 'desktop';
    if (nextVariant === lastVariant) return;
    lastVariant = nextVariant;

    videos.forEach((videoEl) => {
      if (videoEl.querySelector('source')) {
        videoEl.innerHTML = '';
        ensureSources(videoEl);
      }
    });
  });
}

/* ============================================================
   3. NAVEGAÇÃO – ATIVAÇÃO DO ITEM ATUAL
   ============================================================ */

/**
 * Marca o item de navegação correspondente ao índice ativo.
 */
function setupNavActive(activeIndex) {
  const navItems = Array.from(document.querySelectorAll('.js-nav-item'));
  const activeId = SECTION_IDS[activeIndex];

  navItems.forEach((el) => {
    const target = el.dataset.target;
    el.classList.toggle('is-active', target === activeId);
  });
}

/* ============================================================
   4. SCROLL ANIMATIONS (TRANSIÇÕES ENTRE SLIDES)
   ============================================================ */

/**
 * Configura toda a lógica de scroll com transições de slides.
 */
function setupScrollAnimations() {
  const frames = Array.from(document.querySelectorAll('.js-frame'));
  const footer = document.getElementById('rodape');
  const wrapper = document.getElementById('frames-wrapper');

  if (!wrapper || frames.length === 0) return;

  // ========== PREPARAÇÃO INICIAL ==========
  // Impede rolagem nativa e posiciona os frames
  window.scrollTo(0, 0);
  if ('scrollRestoration' in history) {
    history.scrollRestoration = 'manual';
  }
  document.body.style.overflow = 'hidden';

  frames.forEach((frame, index) => {
    gsap.set(frame, {
      zIndex: index + 1,
      transformOrigin: 'center center',
      transformPerspective: 1000,
    });

    if (index > 0) {
      gsap.set(frame, { yPercent: 100 });
    } else {
      gsap.set(frame, { scale: 1, opacity: 1, filter: 'none', yPercent: 0 });
    }
  });

  let currentIndex = 0;
  let isAnimating = false;
  const totalSlides = frames.length + (footer ? 1 : 0);

  // Estado inicial da navegação
  setupNavActive(currentIndex);

  // ========== FUNÇÃO PRINCIPAL: goToSlide ==========
  goToSlide = function (index) {
    if (isAnimating || index < 0 || index >= totalSlides || index === currentIndex) return;

    isAnimating = true;
    setupNavActive(index);

    const tl = gsap.timeline({
      onComplete: () => {
        isAnimating = false;
        currentIndex = index;
      },
    });

    if (index > currentIndex) {
      animateDown(tl, currentIndex, index, frames, footer);
    } else {
      animateUp(tl, currentIndex, index, frames, footer);
    }
  };

  // ========== SUB-FUNÇÕES DE ANIMAÇÃO ==========
  function animateDown(tl, from, to, frames, footer) {
    for (let i = from; i < to; i++) {
      const currentFrame = frames[i];
      const nextEl = i + 1 < frames.length ? frames[i + 1] : footer;
      const pos = i - from; // stagger position

      if (currentFrame) {
        tl.to(currentFrame, {
          scale: ANIMATION_DEFAULTS.frameScale,
          z: ANIMATION_DEFAULTS.frameZ,
          opacity: ANIMATION_DEFAULTS.frameOpacity,
          duration: ANIMATION_DEFAULTS.slideDuration,
          ease: 'power2.inOut',
        }, pos * ANIMATION_DEFAULTS.slideDuration);
      }

      if (nextEl === footer) {
        // Entrada do footer
        tl.set(footer, {
          position: 'fixed',
          top: 0,
          left: 0,
          width: '100vw',
          height: '100vh',
          zIndex: frames.length + 10,
          xPercent: -100,
          overflow: 'hidden',
          scrollTop: 0,
        }, pos * ANIMATION_DEFAULTS.slideDuration);

        tl.to(footer, {
          xPercent: 0,
          duration: ANIMATION_DEFAULTS.slideDuration,
          ease: 'power2.inOut',
        }, pos * ANIMATION_DEFAULTS.slideDuration);

        tl.set(footer, { overflowY: 'auto', overflowX: 'hidden' }, (pos + 1) * ANIMATION_DEFAULTS.slideDuration);
        tl.call(() => {
          if (window._revealFooterLines) window._revealFooterLines();
        }, [], (pos + 1) * ANIMATION_DEFAULTS.slideDuration + 0.1);

        // Oculta sidebar durante o footer
        const sidebar = document.getElementById('sidebar-nav');
        if (sidebar) {
          tl.to(sidebar, {
            opacity: 0,
            pointerEvents: 'none',
            duration: 0.5,
            ease: 'power2.inOut',
          }, pos * ANIMATION_DEFAULTS.slideDuration);
        }
      } else {
        // Próximo frame
        tl.to(nextEl, {
          yPercent: 0,
          duration: ANIMATION_DEFAULTS.slideDuration,
          ease: 'power2.inOut',
        }, pos * ANIMATION_DEFAULTS.slideDuration);

        tl.call(() => {
          if (nextEl !== footer) animateFrameText(nextEl);
          if (footer) footer.scrollTop = 0;
        }, [], pos * ANIMATION_DEFAULTS.slideDuration + 0.3);
      }
    }
  }

  function animateUp(tl, from, to, frames, footer) {
    for (let i = from; i > to; i--) {
      const currentEl = i === frames.length ? footer : frames[i];
      const prevFrame = frames[i - 1];
      const pos = from - i;

      if (currentEl === footer) {
        // Saída do footer
        tl.set(footer, { overflow: 'hidden' }, pos * ANIMATION_DEFAULTS.slideDuration);
        tl.call(() => {
          if (window._resetFooterLines) window._resetFooterLines();
        }, [], pos * ANIMATION_DEFAULTS.slideDuration);

        tl.to(footer, {
          xPercent: -100,
          duration: ANIMATION_DEFAULTS.slideDuration,
          ease: 'power2.inOut',
        }, pos * ANIMATION_DEFAULTS.slideDuration);

        // Restaura sidebar
        const sidebar = document.getElementById('sidebar-nav');
        if (sidebar) {
          tl.to(sidebar, {
            opacity: 1,
            pointerEvents: 'auto',
            duration: 0.5,
            ease: 'power2.inOut',
          }, pos * ANIMATION_DEFAULTS.slideDuration);
        }

        tl.to(prevFrame, {
          scale: 1,
          z: 0,
          opacity: 1,
          duration: ANIMATION_DEFAULTS.slideDuration,
          ease: 'power2.inOut',
        }, pos * ANIMATION_DEFAULTS.slideDuration);

        tl.call(() => {
          animateFrameText(prevFrame);
        }, [], pos * ANIMATION_DEFAULTS.slideDuration + 0.3);
      } else {
        // Voltando para frame anterior
        tl.to(currentEl, {
          yPercent: 100,
          duration: ANIMATION_DEFAULTS.slideDuration,
          ease: 'power2.inOut',
        }, pos * ANIMATION_DEFAULTS.slideDuration);

        tl.to(prevFrame, {
          scale: 1,
          z: 0,
          opacity: 1,
          duration: ANIMATION_DEFAULTS.slideDuration,
          ease: 'power2.inOut',
        }, pos * ANIMATION_DEFAULTS.slideDuration);

        tl.call(() => {
          animateFrameText(prevFrame);
        }, [], pos * ANIMATION_DEFAULTS.slideDuration + 0.3);
      }
    }
  }

  // ========== EVENTOS DE NAVEGAÇÃO (cliques nos links) ==========
  const navLinks = Array.from(document.querySelectorAll('a.js-nav-item'));
  navLinks.forEach((a) => {
    a.addEventListener('click', (e) => {
      e.preventDefault();
      const targetId = a.getAttribute('href')?.replace('#', '');
      const targetIndex = SECTION_IDS.indexOf(targetId);
      if (targetIndex !== -1 && targetIndex !== currentIndex) {
        if (currentIndex === totalSlides - 1 && targetIndex !== totalSlides - 1) {
          window.scrollTo(0, 0);
        }
        goToSlide(targetIndex);
      }
    });
  });

  // ========== EVENTO DE RODA (DESKTOP) ==========
  window.addEventListener('wheel', (e) => {
    if (currentIndex === totalSlides - 1) {
      if (footer && footer.scrollTop <= 0 && e.deltaY < 0) {
        const sidebar = document.getElementById('sidebar-nav');
        if (sidebar) sidebar.style.transform = '';
        e.preventDefault();
        goToSlide(currentIndex - 1);
      }
      return;
    }
    e.preventDefault();
    if (e.deltaY > 0) goToSlide(currentIndex + 1);
    else if (e.deltaY < 0) goToSlide(currentIndex - 1);
  }, { passive: false });

  // ========== EVENTOS DE TOQUE (MOBILE) ==========
  let touchStartY = 0;
  window.addEventListener('touchstart', (e) => {
    touchStartY = e.changedTouches[0].clientY;
  }, { passive: false });

  window.addEventListener('touchmove', (e) => {
    if (currentIndex === totalSlides - 1) {
      if (footer && footer.scrollTop <= 0) {
        const touchEndY = e.changedTouches[0].clientY;
        if (touchEndY > touchStartY) {
          const sidebar = document.getElementById('sidebar-nav');
          if (sidebar) sidebar.style.transform = '';
          e.preventDefault();
          goToSlide(currentIndex - 1);
        }
      }
      return;
    }
    e.preventDefault();
  }, { passive: false });

  window.addEventListener('touchend', (e) => {
    if (currentIndex === totalSlides - 1) return;
    const touchEndY = e.changedTouches[0].clientY;
    const distance = touchStartY - touchEndY;
    if (Math.abs(distance) > 30) {
      goToSlide(distance > 0 ? currentIndex + 1 : currentIndex - 1);
    }
  });
}

/* ============================================================
   5. TEXTOS – SPLIT E ANIMAÇÃO DE ENTRADA
   ============================================================ */

/**
 * Divide cada texto (.js-frame-text) em palavras envoltas em <span>.
 */
function splitFrameTexts() {
  document.querySelectorAll('.js-frame-text').forEach((wrapper) => {
    wrapper.querySelectorAll('h2, p').forEach((el) => {
      const words = el.innerText.trim().split(/\s+/);
      el.innerHTML = words
        .map((w) => `<span class="inline-block will-change-transform">${w}</span>`)
        .join(' ');
    });
    // Oculta as palavras inicialmente
    gsap.set(wrapper.querySelectorAll('span'), { opacity: 0, y: 24 });
  });
}

/**
 * Anima a entrada das palavras de um frame específico.
 */
function animateFrameText(frameEl) {
  const spans = frameEl.querySelectorAll('.js-frame-text span');
  if (!spans.length) return;
  gsap.killTweensOf(spans);
  gsap.set(spans, { opacity: 0, y: 24 });
  gsap.to(spans, {
    opacity: 1,
    y: 0,
    duration: 1.2,
    ease: 'power2.out',
    stagger: 0.15,
    delay: 1,
  });
}

/* ============================================================
   6. FOOTER – REVELAÇÃO DE TEXTOS, LOGO E E-MAIL
   ============================================================ */

/**
 * Configura a revelação das letras do título e subtítulo do footer.
 */
function setupFooterTextReveal() {
  const titleEl = document.getElementById('footer-title');
  const subtitleEl = document.getElementById('footer-subtitle');
  if (!titleEl && !subtitleEl) return;

  // Utilitários de easing
  const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);
  const easeOutQuad = (t) => 1 - (1 - t) * (1 - t);

  /**
   * Divide o texto em letras com ordem aleatória de revelação.
   */
  function splitIntoLetters(el) {
    const text = el.textContent.trim();
    const order = [...text].map((_, i) => i);
    for (let i = order.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [order[i], order[j]] = [order[j], order[i]];
    }
    const revealRank = new Array(text.length);
    order.forEach((charIndex, rank) => { revealRank[charIndex] = rank; });

    el.innerHTML = '';
    const spans = [];
    [...text].forEach((ch, i) => {
      const span = document.createElement('span');
      span.className = 'reveal-letter';
      span.textContent = ch === ' ' ? '\u00A0' : ch;
      span.style.transformOrigin = 'center bottom';
      span.dataset.rank = revealRank[i];
      el.appendChild(span);
      spans.push(span);
    });
    return spans;
  }

  /**
   * Anima um grupo de spans (letras) com blur e escala.
   */
  function animateGroup(spans, totalDuration, startTime) {
    const total = spans.length;
    if (total === 0) return;

    function frame(now) {
      const elapsed = now - startTime;
      let allDone = true;

      spans.forEach((span) => {
        const rank = parseInt(span.dataset.rank, 10);
        const letterStart = (rank / total) * (totalDuration * 0.6);
        const letterDuration = totalDuration * 0.45;
        let t = (elapsed - letterStart) / letterDuration;
        t = Math.max(0, Math.min(1, t));
        if (t < 1) allDone = false;

        const tBlur = Math.min(1, t / 0.45);
        const easedBlur = easeOutQuad(tBlur);
        span.style.opacity = easedBlur.toFixed(3);
        span.style.filter = `blur(${(1 - easedBlur) * 14}px)`;

        const easedScale = easeOutCubic(t);
        span.style.transform = `scale(${2.4 - easedScale * 1.4})`;
      });

      if (!allDone) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
  }

  function playReveal() {
    const now = performance.now();
    if (titleEl) {
      const titleSpans = splitIntoLetters(titleEl);
      animateGroup(titleSpans, 2200, now);
    }
    if (subtitleEl) {
      const subtitleSpans = splitIntoLetters(subtitleEl);
      animateGroup(subtitleSpans, 2200, now + 500);
    }
  }

  function resetReveal() {
    [titleEl, subtitleEl].forEach((el) => {
      if (!el) return;
      el.querySelectorAll('.reveal-letter').forEach((span) => {
        span.style.opacity = '0';
        span.style.filter = 'blur(14px)';
        span.style.transform = 'scale(2.4)';
      });
    });
  }

  window._revealFooterLines = playReveal;
  window._resetFooterLines = resetReveal;
}

/**
 * Faz o clique no logo do footer voltar ao primeiro slide.
 */
function setupFooterLogoClick() {
  const logoLink = document.querySelector('a[data-target="publicidade"][aria-label="318 Produtora"]');
  if (!logoLink) return;

  logoLink.addEventListener('click', (e) => {
    e.preventDefault();
    if (goToSlide) goToSlide(0);
  });
}

/**
 * Inicializa o efeito "letter slide" no e-mail do footer.
 */
function initLetterSlide() {
  document.querySelectorAll('.letter-slide-link[data-text]').forEach((link) => {
    const text = link.dataset.text;
    let html = '';
    [...text].forEach((char, i) => {
      if (char === '-' || char === ' ') {
        html += `<span style="display:inline-block;width:0.3em;"></span>`;
        return;
      }
      const delay = i * 35;
      html += `<span class="letter-slide-char">
                <span class="letter-slide-inner" style="transition-delay:${delay}ms">
                  <span class="letter-slide-top">${char}</span>
                  <span class="letter-slide-bottom">${char}</span>
                </span>
              </span>`;
    });
    link.innerHTML = html;
  });
}

/* ============================================================
   7. CURSOR PERSONALIZADO (APENAS DESKTOP)
   ============================================================ */

function initCustomCursor() {
  const bracket = document.getElementById('cursor-bracket');
  const ring = document.getElementById('cursor-ring');
  if (!bracket || !ring) return;

  bracket.innerHTML = '<span></span><span></span>';

  const isTouch = window.matchMedia('(pointer: coarse)').matches ||
    !window.matchMedia('(hover: hover)').matches ||
    ('ontouchstart' in window && !window.matchMedia('(pointer: fine)').matches);

  if (isTouch) return;

  let mx = -200, my = -200;
  let rx = -200, ry = -200;
  let cursorInitialized = false;

  const LERP_SPEED = 0.12;

  function lerp(a, b, t) { return a + (b - a) * t; }

  function animate() {
    rx = lerp(rx, mx, LERP_SPEED);
    ry = lerp(ry, my, LERP_SPEED);
    ring.style.left = rx + 'px';
    ring.style.top = ry + 'px';
    requestAnimationFrame(animate);
  }

  document.addEventListener('mousemove', (e) => {
    if (!cursorInitialized) {
      cursorInitialized = true;
      bracket.style.display = 'block';
      ring.style.display = 'block';
      animate();
    }
    mx = e.clientX;
    my = e.clientY;
    bracket.style.left = mx + 'px';
    bracket.style.top = my + 'px';
  });

  // Efeito de hover em elementos interativos
  document.querySelectorAll('a, button, [role="button"], input, label').forEach((el) => {
    el.addEventListener('mouseenter', () => {
      bracket.style.transform = 'translate(-50%, -50%) scale(1.4)'; 
      ring.style.transform = 'translate(-50%, -50%) scale(1.4)';
      ring.style.opacity = '0.8';
    });
    el.addEventListener('mouseleave', () => {
      bracket.style.transform = '';
      ring.style.transform = '';
      ring.style.opacity = '';
    });
  });
}

/* ============================================================
   8. INICIALIZAÇÃO (DOMContentLoaded)
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
  initCustomCursor();
});

window.addEventListener('DOMContentLoaded', () => {
  splitFrameTexts();
  setupVideoLazyLoad();
  setupNavActive(0);
  setupScrollAnimations();
  setupFooterTextReveal();
  setupFooterLogoClick();
  initLetterSlide();

  startLandingLoading().then(() => {
    const firstFrame = document.querySelector('.js-frame');
    if (firstFrame) animateFrameText(firstFrame);
  });
});