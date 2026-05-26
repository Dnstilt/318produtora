import { gsap } from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';
import Swiper from 'swiper';
import { Autoplay, Navigation, Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

gsap.registerPlugin(ScrollTrigger);

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

function setupNavActive() {
    const navItems = Array.from(document.querySelectorAll('.js-nav-item'));
    const setActive = (id) => {
        navItems.forEach((el) => {
            const target = el.dataset.target;
            el.classList.toggle('is-active', target === id);
        });
    };

    ['publicidade', 'ooh', 'documentarios', 'natureza', 'rodape'].forEach((id) => {
        const el = document.getElementById(id);
        if (!el) return;
        ScrollTrigger.create({
            trigger: el,
            start: 'top center',
            end: 'bottom center',
            onEnter: () => setActive(id),
            onEnterBack: () => setActive(id),
        });
    });

    const links = Array.from(document.querySelectorAll('a.js-nav-item'));
    links.forEach((a) => {
        a.addEventListener('click', (e) => {
            const targetId = a.getAttribute('href')?.replace('#', '');
            const targetEl = targetId ? document.getElementById(targetId) : null;
            if (!targetEl) return;
            e.preventDefault();
            targetEl.scrollIntoView({ behavior: 'smooth' });
        });
    });
}

function setupScrollAnimations() {
    const frames = Array.from(document.querySelectorAll('.js-frame'));

    frames.forEach((frame, index) => {
        const textEl = frame.querySelector('.js-frame-text');
        if (textEl) {
            ScrollTrigger.create({
                trigger: frame,
                start: 'top top',
                end: '+=40vh',
                scrub: true,
                onUpdate: (self) => {
                    gsap.to(textEl, {
                        opacity: self.progress,
                        y: 30 * (1 - self.progress),
                        duration: 0,
                    });
                },
            });
        }

        const isLast = index === frames.length - 1;
        const nextFrame = !isLast ? frames[index + 1] : document.getElementById('rodape');

        if (!nextFrame) return;

        const tl = gsap.timeline({
            scrollTrigger: {
                trigger: frame,
                start: 'top top',
                end: '+=100vh',
                scrub: true,
                pin: true,
                pinSpacing: true,
                anticipatePin: 1,
            },
        });

        tl.to({}, { duration: 0.4 });

        if (!isLast) {
            tl.fromTo(
                nextFrame,
                { y: window.innerHeight },
                { y: 0, duration: 0.6, ease: 'none' },
                '<'
            );

            tl.to(
                frame,
                { rotate: -6, scale: 0.85, opacity: 0, duration: 0.6, ease: 'none' },
                '<'
            );
        } else {
            tl.to(frame, { y: -window.innerHeight, duration: 0.6, ease: 'none' }, '<');
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
    setupVideoLazyLoad();
    setupNavActive();
    setupScrollAnimations();
    setupFooterCarousel();
});
