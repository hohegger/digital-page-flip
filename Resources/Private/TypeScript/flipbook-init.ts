import { PageFlip } from 'page-flip';

document.querySelectorAll<HTMLElement>('.flipbook-viewer__book').forEach((container) => {
  const pages: string[] = JSON.parse(container.dataset.flipbookPages || '[]');

  if (pages.length === 0) {
    return;
  }

  const uid = container.id.replace('flipbook-', '');
  const width = parseInt(container.dataset.flipbookWidth || '550', 10);
  const height = parseInt(container.dataset.flipbookHeight || '880', 10);
  const showCover = container.dataset.flipbookShowCover !== '0';
  const swipeDistance = parseInt(container.dataset.flipbookSwipeDistance || '30', 10);

  const isMobile = window.innerWidth < 768;

  const pageFlip = new PageFlip(container, {
    width,
    height,
    size: 'stretch' as any,
    minWidth: 280,
    maxWidth: width,
    minHeight: 400,
    maxHeight: height,
    showCover,
    mobileScrollSupport: true,
    swipeDistance,
    maxShadowOpacity: 0.5,
    autoSize: true,
    usePortrait: isMobile,
    drawShadow: true,
    flippingTime: 800,
    startZIndex: 0,
    startPage: 0,
    clickEventForward: true,
  });

  pageFlip.loadFromImages(pages);

  // Navigation
  const viewer = container.closest('.flipbook-viewer');
  const prevBtn = document.querySelector<HTMLButtonElement>(`[data-flipbook-prev="${uid}"]`);
  const nextBtn = document.querySelector<HTMLButtonElement>(`[data-flipbook-next="${uid}"]`);
  const currentPageEl = viewer?.querySelector<HTMLElement>('[data-flipbook-current]');
  const totalEl = viewer?.querySelector<HTMLElement>('[data-flipbook-total]');

  if (totalEl) {
    totalEl.textContent = String(pages.length);
  }

  const updatePager = (): void => {
    const current = pageFlip.getCurrentPageIndex();
    const total = pages.length;

    if (currentPageEl) {
      currentPageEl.textContent = String(current + 1);
    }
    if (prevBtn) {
      prevBtn.disabled = current <= 0;
    }
    if (nextBtn) {
      nextBtn.disabled = current >= total - 1;
    }
  };

  prevBtn?.addEventListener('click', () => pageFlip.flipPrev());
  nextBtn?.addEventListener('click', () => pageFlip.flipNext());

  pageFlip.on('flip', () => updatePager());

  // Keyboard navigation
  viewer?.addEventListener('keydown', (e: Event) => {
    const key = (e as KeyboardEvent).key;
    if (key === 'ArrowLeft') pageFlip.flipPrev();
    else if (key === 'ArrowRight') pageFlip.flipNext();
  });

  updatePager();
});
