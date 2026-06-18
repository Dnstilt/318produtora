import { gsap } from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

let goToSlide = null;

function startLandingLoading() {
    const overlay = document.getElementById('landing-loading-overlay');
    const logoWrapper = document.getElementById('landing-loading-logo-wrapper');
    const progress = document.getElementById('landing-loading-progress');
    const bar = document.getElementById('landing-loading-progress-bar');
    const label = document.getElementById('landing-loading-progress-label');
    const navbarLogo = document.getElementById('landing-navbar-logo');

    if (!overlay || !logoWrapper || !progress || !bar || !label || !navbarLogo) {
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

            setTimeout(() => {
                logoWrapper.removeEventListener('transitionend', onEnd);
                cleanup();
            }, 1600);
        };

        const tick = () => {
            if (pct >= 100) {
                bar.style.width = '100%';
                label.textContent = '100%';
                setTimeout(goToNavbar, 700);
                return;
            }

            const step = pct < 65 ? Math.random() * 6 + 1 : Math.random() * 2.5 + 0.4;
            pct = Math.min(pct + step, 100);
            bar.style.width = `${pct}%`;
            label.textContent = `${Math.floor(pct)}%`;
            const delay = pct < 70 ? 70 + Math.random() * 55 : 110 + Math.random() * 90;
            setTimeout(tick, delay);
        };

        setTimeout(tick, 350);
    });
}

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

function isMobile() {
    return window.innerWidth < 768;
}

function buildSources(videoEl) {
    const variant = isMobile() ? 'mobile' : 'desktop';
    const webm = videoEl.dataset[`${variant}Webm`];
    const mp4 = videoEl.dataset[`${variant}Mp4`];

    return { webm, mp4 };
}

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
    videoEl.play().catch(() => { });
}

function setupVideoLazyLoad() {
    const frames = Array.from(document.querySelectorAll('.js-frame'));
    const videos = frames.map((f) => f.querySelector('.js-frame-video')).filter(Boolean);

    videos.forEach((videoEl) => {
        if (videoEl.dataset.preload === 'auto') {
            ensureSources(videoEl);
        }
    });

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

function setupNavActive(activeIndex) {
    const navItems = Array.from(document.querySelectorAll('.js-nav-item'));
    const ids = ['publicidade', 'ooh', 'documentarios', 'natureza', 'rodape'];
    const activeId = ids[activeIndex];

    navItems.forEach((el) => {
        const target = el.dataset.target;
        el.classList.toggle('is-active', target === activeId);
    });
}

function setupScrollAnimations() {
    const frames = Array.from(document.querySelectorAll('.js-frame'));
    const footer = document.getElementById('rodape');
    const wrapper = document.getElementById('frames-wrapper');

    if (!wrapper || frames.length === 0) return;

    // Reset scroll to top on load to avoid being stuck in the middle
    window.scrollTo(0, 0);
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }

    // Lock native scrolling for the body
    document.body.style.overflow = 'hidden';

    // Stack all frames correctly
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

    // Initial nav active state
    setupNavActive(currentIndex);

    goToSlide = function (index) {
        if (isAnimating || index < 0 || index >= totalSlides || index === currentIndex) return;

        isAnimating = true;
        setupNavActive(index); // Update sidebar immediately

        const tl = gsap.timeline({
            onComplete: () => {
                isAnimating = false;
                currentIndex = index;
            }
        });

        // Going Down
        if (index > currentIndex) {
            for (let i = currentIndex; i < index; i++) {
                const currentFrame = frames[i];
                const nextEl = i + 1 < frames.length ? frames[i + 1] : footer;
                const pos = i - currentIndex; // stagger position

                if (currentFrame) {
                    tl.to(currentFrame, {
                        scale: 0.88,
                        z: -220,
                        opacity: 0.80,
                        duration: 1.5,
                        ease: "power2.inOut"
                    }, pos * 1.5); // sequence them if jumping multiple slides
                }

                if (nextEl === footer) {
                    tl.set(footer, {
                        position: 'fixed',
                        top: 0,
                        left: 0,
                        width: '100vw',
                        height: '100vh',
                        zIndex: frames.length + 10,
                        xPercent: -100,
                        overflow: 'hidden',
                        scrollTop: 0
                    }, pos * 1.5);

                    tl.to(footer, {
                        xPercent: 0,
                        duration: 1.5,
                        ease: "power2.inOut"
                    }, pos * 1.5);

                    // Make footer scrollable internally, leave it fixed
                    tl.set(footer, { overflowY: 'auto', overflowX: 'hidden' }, (pos + 1) * 1.5);
                    tl.call(() => {
                        if (window._revealFooterLines) window._revealFooterLines();
                    }, [], (pos + 1) * 1.5 + 0.1);
                    // Make Sidebar scroll with the footer content
                    const sidebar = document.getElementById('sidebar-nav');
                    if (sidebar) {
                        tl.to(sidebar, {
                            opacity: 0,
                            pointerEvents: 'none',
                            duration: 0.5,
                            ease: "power2.inOut"
                        }, pos * 1.5);
                    }
                } else {
                    tl.to(nextEl, {
                        yPercent: 0,
                        duration: 1.5,
                        ease: "power2.inOut"
                    }, pos * 1.5);
                    tl.call(() => {
                        if (nextEl !== footer) animateFrameText(nextEl);
                        footer.scrollTop = 0;
                    }, [], pos * 1.5 + 0.3);
                }
            }
        }
        // Going Up
        else if (index < currentIndex) {
            for (let i = currentIndex; i > index; i--) {
                const currentEl = i === frames.length ? footer : frames[i];
                const prevFrame = frames[i - 1];
                const pos = currentIndex - i; // stagger position

                if (currentEl === footer) {
                    tl.set(footer, { overflow: 'hidden' }, pos * 1.5);
                    tl.call(() => {
                        if (window._resetFooterLines) window._resetFooterLines();
                    }, [], pos * 1.5);
                    tl.to(footer, {
                        xPercent: -100,
                        duration: 1.5,
                        ease: "power2.inOut"
                    }, pos * 1.5);

                    // Reset sidebar
                    const sidebar = document.getElementById('sidebar-nav');
                    if (sidebar) tl.to(sidebar, {
                        opacity: 1,
                        pointerEvents: 'auto',
                        duration: 0.5,
                        ease: "power2.inOut"
                    }, pos * 1.5);


                    tl.to(prevFrame, {
                        scale: 1,
                        z: 0,
                        opacity: 1,
                        duration: 1.5,
                        ease: "power2.inOut"
                    }, pos * 1.5);
                    tl.call(() => {
                        animateFrameText(prevFrame);
                    }, [], pos * 1.5 + 0.3);
                } else {
                    tl.to(currentEl, {
                        yPercent: 100,
                        duration: 1.5,
                        ease: "power2.inOut"
                    }, pos * 1.5);

                    tl.to(prevFrame, {
                        scale: 1,
                        z: 0,
                        opacity: 1,
                        duration: 1.5,
                        ease: "power2.inOut"
                    }, pos * 1.5);
                    tl.call(() => {
                        animateFrameText(prevFrame);
                    }, [], pos * 1.5 + 0.3);
                }
            }
        }
    }

    // Handle Navbar Clicks manually since native scroll is locked
    const navLinks = Array.from(document.querySelectorAll('a.js-nav-item'));
    const ids = ['publicidade', 'ooh', 'documentarios', 'natureza', 'rodape'];
    navLinks.forEach((a) => {
        a.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = a.getAttribute('href')?.replace('#', '');
            const targetIndex = ids.indexOf(targetId);
            if (targetIndex !== -1 && targetIndex !== currentIndex) {
                // If they are in the footer, jump to top first if needed, or just let goToSlide handle it
                if (currentIndex === totalSlides - 1 && targetIndex !== totalSlides - 1) {
                    window.scrollTo(0, 0); // Reset native scroll before animating out of footer
                }
                goToSlide(targetIndex);
            }
        });
    });

    // Wheel event listener for Desktop
    window.addEventListener('wheel', (e) => {
        if (currentIndex === totalSlides - 1) {
            // Check if user is scrolling up AND at the top of the footer content
            if (footer && footer.scrollTop <= 0 && e.deltaY < 0) {
                // Remove fixed sidebar when going back to videos
                const sidebar = document.getElementById('sidebar-nav');
                if (sidebar) sidebar.style.transform = '';

                e.preventDefault();
                goToSlide(currentIndex - 1);
            }
            return;
        }

        e.preventDefault();

        if (e.deltaY > 0) {
            goToSlide(currentIndex + 1);
        } else if (e.deltaY < 0) {
            goToSlide(currentIndex - 1);
        }
    }, { passive: false });

    // Touch event listeners for Mobile
    let touchStartY = 0;
    window.addEventListener('touchstart', (e) => {
        touchStartY = e.changedTouches[0].clientY;
    }, { passive: false });

    window.addEventListener('touchmove', (e) => {
        if (currentIndex === totalSlides - 1) {
            if (footer && footer.scrollTop <= 0) {
                const touchEndY = e.changedTouches[0].clientY;
                if (touchEndY > touchStartY) {
                    // Remove fixed sidebar when going back to videos
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
            if (distance > 0) {
                goToSlide(currentIndex + 1);
            } else {
                goToSlide(currentIndex - 1);
            }
        }
    });
}

function splitFrameTexts() {
    document.querySelectorAll('.js-frame-text').forEach((wrapper) => {
        wrapper.querySelectorAll('h2, p').forEach((el) => {
            const words = el.innerText.trim().split(/\s+/);
            el.innerHTML = words
                .map(w => `<span class="inline-block will-change-transform">${w}</span>`)
                .join(' ');
        });
        // Esconde imediatamente — sem animar ainda
        gsap.set(wrapper.querySelectorAll('span'), { opacity: 0, y: 24 });
    });
}

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

function setupFooterTextReveal() {
    const lines = document.querySelectorAll('.footer-line');
    if (!lines.length) return;

    function resetLines() {
        lines.forEach(el => {
            el.style.transition = 'transform 0s';
            el.classList.remove('go');
        });
    }

    function animateLines() {
        lines.forEach(el => {
            const delay = parseInt(el.dataset.delay) || 0;
            setTimeout(() => {
                el.style.transition = '';
                el.classList.add('go');
            }, delay + 300);
        });
    }

    // Expõe para ser chamada pelo goToSlide
    window._revealFooterLines = () => {
        resetLines();
        setTimeout(animateLines, 50);
    };

    window._resetFooterLines = resetLines;
}

function setupFooterLogoClick() {
    const logoLink = document.querySelector('a[data-target="publicidade"][aria-label="318 Produtora"]');
    if (!logoLink) return;

    logoLink.addEventListener('click', (e) => {
        e.preventDefault();
        if (goToSlide) goToSlide(0);
    });
}

function initLetterSlide() {
    document.querySelectorAll('.letter-slide-link[data-text]').forEach(link => {
        const text = link.dataset.text;
        let html = '';
        [...text].forEach((char, i) => {
            if (char === '-' || char === ' ') {
                html += `<span style="display:inline-block;width:0.3em;"></span>`;
                return;
            }
            const delay = i * 35;
            html += `<span class="letter-slide-char">` +
                `<span class="letter-slide-inner" style="transition-delay:${delay}ms">` +
                `<span class="letter-slide-top">${char}</span>` +
                `<span class="letter-slide-bottom">${char}</span>` +
                `</span>` +
                `</span>`;
        });
        link.innerHTML = html;
    });
}

document.addEventListener('DOMContentLoaded', function () {

    const bracket = document.getElementById('cursor-bracket');
    const ring = document.getElementById('cursor-ring');

    bracket.innerHTML = '<span></span><span></span>';

    const isTouch = window.matchMedia('(pointer: coarse)').matches ||
        !window.matchMedia('(hover: hover)').matches ||
        ('ontouchstart' in window && !window.matchMedia('(pointer: fine)').matches);

    if (isTouch) return;

    let mx = -200, my = -200;
    let rx = -200, ry = -200;
    let cursorInitialized = false;

    /* ─── VELOCIDADE DO TRAILING ────────────────────────────
       0.05 = muito lento / 0.15 = médio / 0.3 = quase direto
    ──────────────────────────────────────────────────────── */
    const lerpSpeed = 0.12;

    function lerp(a, b, t) { return a + (b - a) * t; }

    function animate() {
        rx = lerp(rx, mx, lerpSpeed);
        ry = lerp(ry, my, lerpSpeed);
        ring.style.left = rx + 'px';
        ring.style.top = ry + 'px';
        requestAnimationFrame(animate);
    }

    document.addEventListener('mousemove', function (e) {
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

    document.querySelectorAll('a, button, [role="button"], input, label').forEach(function (el) {
        el.addEventListener('mouseenter', function () {
            bracket.style.width = '28px';
            bracket.style.height = '28px';
            ring.style.width = '44px';
            ring.style.height = '44px';
            ring.style.opacity = '0.8';
        });
        el.addEventListener('mouseleave', function () {
            bracket.style.width = '';
            bracket.style.height = '';
            ring.style.width = '';
            ring.style.height = '';
            ring.style.opacity = '';
        });
    });
});

function setupGalleryCarousel() {
    const wrap = document.getElementById('carousel-wrap');
    const track = document.getElementById('carousel-track');
    if (!wrap || !track) return;

    const nameEl = document.getElementById('carousel-name');
    const curEl = document.getElementById('carousel-cur');
    const totEl = document.getElementById('carousel-tot');
    const allSlides = Array.from(track.querySelectorAll('.carousel-slide'));
    const N = parseInt(totEl?.textContent || '0');
    if (N === 0) return;

    const GAP = 6, PAD = 28;
    function getActiveW() { return wrap.offsetWidth > 768 ? 720 : 280; }
    function getSideW() { return wrap.offsetWidth > 768 ? 60 : 24; }
    function getPAD() { return 28; }

    let activeReal = 0, activeAbs = N; // começa na cópia do meio
    let trackX = 0, targetX = 0, velX = 0;
    let dragging = false, startMX = 0, startTX = 0;
    let lastMX = 0, lastT = 0, didDrag = false, jumping = false;

    function snapX(absIdx) {
        const ACTIVE_W = getActiveW();
        const SIDE_W = getSideW();
        const PAD = getPAD();

        let left = PAD;
        for (let i = 0; i < absIdx; i++) {
            left += (i === activeAbs ? ACTIVE_W : SIDE_W) + GAP;
        }
        return -(left - (wrap.offsetWidth / 2 - ACTIVE_W / 2));
    }

    function setActive(absIdx, animate) {
        const prev = activeAbs;
        activeAbs = absIdx;
        activeReal = absIdx % N;

        allSlides.forEach((s, i) => {
            const ov = s.querySelector('.carousel-overlay');
            if (i === absIdx) {
                s.classList.remove('is-side'); s.classList.add('is-active');
                ov.style.transition = animate ? 'opacity .55s cubic-bezier(.77,0,.18,1)' : 'none';
                ov.style.opacity = '0';
            } else if (i === prev && animate) {
                s.classList.remove('is-active'); s.classList.add('is-side');
                ov.style.transition = 'opacity .45s cubic-bezier(.77,0,.18,1)';
                ov.style.opacity = '1';
            } else {
                s.classList.remove('is-active'); s.classList.add('is-side');
                ov.style.transition = 'none';
                ov.style.opacity = '1';
            }
        });

        if (animate && nameEl) {
            nameEl.style.opacity = '0';
            nameEl.style.transform = 'translateY(5px)';
            setTimeout(() => {
                nameEl.textContent = allSlides[N + activeReal]?.dataset.title || '';
                nameEl.style.opacity = '1';
                nameEl.style.transform = 'translateY(0)';
                if (curEl) curEl.textContent = activeReal + 1;
            }, 160);
        } else if (nameEl) {
            nameEl.textContent = allSlides[N + activeReal]?.dataset.title || '';
            if (curEl) curEl.textContent = activeReal + 1;
        }
    }

    function maybeJump() {
        if (jumping) return;
        if (activeAbs < N || activeAbs >= N * 2) {
            jumping = true;
            const newAbs = N + activeReal;
            const dx = snapX(newAbs) - snapX(activeAbs);
            activeAbs = newAbs;
            trackX += dx; targetX += dx;
            track.style.transition = 'none';
            track.style.transform = `translateX(${trackX}px)`;
            setTimeout(() => { jumping = false; }, 50);
        }
    }

    function snapNearest() {
        let best = activeAbs, bestDist = Infinity;
        allSlides.forEach((_, i) => {
            const d = Math.abs(targetX - snapX(i));
            if (d < bestDist) { bestDist = d; best = i; }
        });
        const newReal = best % N;
        const preferred = N + newReal;
        setActive(preferred, true);
        targetX = snapX(preferred);
    }

    allSlides.forEach((s, i) => {
        s.addEventListener('click', () => {
            if (didDrag) return;
            const newReal = parseInt(s.dataset.idx);
            setActive(N + newReal, true);
            targetX = snapX(N + newReal);
        });
    });

    wrap.addEventListener('mousedown', e => {
        dragging = true; didDrag = false;
        startMX = e.clientX; startTX = targetX;
        lastMX = e.clientX; lastT = Date.now(); velX = 0;
        wrap.classList.add('is-dragging');
        e.preventDefault();
    });
    window.addEventListener('mousemove', e => {
        if (!dragging) return;
        const dx = e.clientX - startMX;
        if (Math.abs(dx) > 4) didDrag = true;
        targetX = startTX + dx;
        const now = Date.now();
        if (now - lastT > 0) velX = (e.clientX - lastMX) * 0.6;
        lastMX = e.clientX; lastT = now;
    });
    window.addEventListener('mouseup', () => {
        if (!dragging) return;
        dragging = false; wrap.classList.remove('is-dragging');
        if (didDrag) { targetX += velX * 4; snapNearest(); }
    });

    window.addEventListener('resize', () => {
        targetX = snapX(activeAbs);
        trackX = targetX;
        track.style.transform = `translateX(${trackX}px)`;
    });

    wrap.addEventListener('touchstart', e => {
        const t = e.touches[0];
        dragging = true; didDrag = false;
        startMX = t.clientX; startTX = targetX;
        lastMX = t.clientX; lastT = Date.now(); velX = 0;
    }, { passive: true });
    window.addEventListener('touchmove', e => {
        if (!dragging) return;
        const t = e.touches[0];
        const dx = t.clientX - startMX;
        if (Math.abs(dx) > 4) didDrag = true;
        targetX = startTX + dx;
        const now = Date.now();
        if (now - lastT > 0) velX = (t.clientX - lastMX) * 0.6;
        lastMX = t.clientX; lastT = now;
    }, { passive: true });
    window.addEventListener('touchend', () => {
        if (!dragging) return;
        dragging = false;
        if (didDrag) { targetX += velX * 4; snapNearest(); }
    });

    function tick() {
        if (!dragging) {
            trackX += (targetX - trackX) * 0.11;
            if (Math.abs(trackX - targetX) < 0.05) trackX = targetX;
        } else {
            trackX = targetX;
        }
        track.style.transform = `translateX(${trackX}px)`;
        if (!dragging && !jumping && Math.abs(trackX - snapX(N + activeReal)) < 1) maybeJump();
        requestAnimationFrame(tick);
    }

    setActive(N, false);
    targetX = snapX(N);
    trackX = targetX;
    track.style.transform = `translateX(${trackX}px)`;
    tick();
}

window.addEventListener('DOMContentLoaded', () => {
    splitFrameTexts();
    setupVideoLazyLoad();
    setupNavActive(0);
    setupScrollAnimations();
    setupFooterTextReveal();
    setupFooterLogoClick();
    setupGalleryCarousel();
    initLetterSlide();

    startLandingLoading().then(() => {
        const firstFrame = document.querySelector('.js-frame');
        if (firstFrame) animateFrameText(firstFrame);
    });
});
