import { gsap } from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';
import Swiper from 'swiper';
import { Autoplay, Navigation, Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

gsap.registerPlugin(ScrollTrigger);

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
    videoEl.play().catch(() => {});
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

    function goToSlide(index) {
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
                        overflow: 'hidden'
                    }, pos * 1.5);

                    tl.to(footer, { 
                        xPercent: 0, 
                        duration: 1.5, 
                        ease: "power2.inOut" 
                    }, pos * 1.5);
                    
                    // Unlock scroll after footer comes in
                    tl.set(document.body, { overflow: 'auto' }, (pos + 1) * 1.5);
                    // IMPORTANT: We only clear position-related props. 
                    // Do NOT clear xPercent/transform, otherwise it jumps back off-screen!
                    tl.set(footer, { clearProps: 'position,top,left,width,height,zIndex,overflow' }, (pos + 1) * 1.5);
                } else {
                    tl.to(nextEl, { 
                        yPercent: 0, 
                        duration: 1.5, 
                        ease: "power2.inOut" 
                    }, pos * 1.5);
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
                    tl.set(document.body, { overflow: 'hidden' }, pos * 1.5);
                    tl.set(footer, {
                        position: 'fixed',
                        top: 0,
                        left: 0,
                        width: '100vw',
                        height: '100vh',
                        zIndex: frames.length + 10,
                        xPercent: 0,
                        overflow: 'hidden'
                    }, pos * 1.5);

                    tl.to(footer, { 
                        xPercent: -100, 
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
            if (window.scrollY <= 0 && e.deltaY < 0) {
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
            if (window.scrollY <= 0) {
                const touchEndY = e.changedTouches[0].clientY;
                if (touchEndY > touchStartY) {
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

function setupFooterCarousel() {
    const el = document.querySelector('.js-swiper');
    if (!el) return;

    new Swiper(el, {
        modules: [Autoplay, Pagination, Navigation],
        loop: true,
        autoplay: { delay: 2500, disableOnInteraction: false },
        pagination: { el: el.querySelector('.swiper-pagination'), clickable: true },
        navigation: {
            nextEl: el.querySelector('.swiper-button-next'),
            prevEl: el.querySelector('.swiper-button-prev'),
        },
        slidesPerView: 1,
        spaceBetween: 12,
        breakpoints: {
            640: { slidesPerView: 2 },
            1024: { slidesPerView: 3 },
        },
    });
}

window.addEventListener('DOMContentLoaded', () => {
    startLandingLoading().then(() => {
        setupVideoLazyLoad();
        setupNavActive();
        setupScrollAnimations();
        setupFooterCarousel();
    });
});
