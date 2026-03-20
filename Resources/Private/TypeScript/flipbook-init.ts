import { PageFlip } from 'page-flip';

document.querySelectorAll<HTMLElement>('.flipbook-viewer__book').forEach((container) => {
  const pages: string[] = JSON.parse(container.dataset.flipbookPages || '[]');

  if (pages.length === 0) {
    return;
  }

  const uid = container.id.replace('flipbook-', '');
  const width = parseInt(container.dataset.flipbookWidth || '550', 10);
  const height = parseInt(container.dataset.flipbookHeight || '880', 10);
  const swipeDistance = parseInt(container.dataset.flipbookSwipeDistance || '30', 10);
  const aspectRatio = height / width;

  const isMobile = window.innerWidth < 768;

  // Create DOM-based pages for native browser image rendering
  pages.forEach((src, index) => {
    const pageEl = document.createElement('div');
    pageEl.className = 'flipbook-viewer__page';

    const img = document.createElement('img');
    img.src = src;
    img.alt = `Seite ${index + 1}`;
    img.loading = index < 4 ? 'eager' : 'lazy';
    img.draggable = false;

    pageEl.appendChild(img);
    container.appendChild(pageEl);
  });

  // Calculate max dimensions from available space
  const stage = container.closest('.flipbook-viewer__stage') as HTMLElement | null;
  const availableWidth = stage ? stage.clientWidth : window.innerWidth;
  const pagesVisible = isMobile ? 1 : 2;
  const maxPageWidth = Math.floor(availableWidth / pagesVisible);
  const maxPageHeight = Math.floor(maxPageWidth * aspectRatio);

  const pageFlip = new PageFlip(container, {
    width,
    height,
    size: 'stretch' as any,
    minWidth: 280,
    maxWidth: maxPageWidth,
    minHeight: 400,
    maxHeight: maxPageHeight,
    showCover: true,
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

  // Load from DOM elements → native browser rendering quality
  const pageElements = container.querySelectorAll('.flipbook-viewer__page');
  pageFlip.loadFromHTML(pageElements as NodeListOf<HTMLElement>);

  // Override: force ALL pages to soft density (showCover sets first/last to hard)
  try {
    const flipPages = (pageFlip as any).flipController?.pages
      ?? (pageFlip as any).pages
      ?? (pageFlip as any).pageCollection?.pages;

    if (flipPages) {
      const allPages = typeof flipPages.getPages === 'function' ? flipPages.getPages() : flipPages;
      if (Array.isArray(allPages)) {
        allPages.forEach((p: any) => {
          if (typeof p.setDensity === 'function') {
            p.setDensity('soft');
          }
        });
      }
    }
  } catch (_) {
    // Graceful fallback: if internal API changes, pages stay as-is
  }

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

  // Sidebar: jump to specific page
  const gotoButtons = document.querySelectorAll<HTMLButtonElement>(
    `[data-flipbook-goto-page][data-flipbook-uid="${uid}"]`
  );
  gotoButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const page = parseInt(btn.dataset.flipbookGotoPage || '0', 10);
      pageFlip.turnToPage(page);
    });
  });

  pageFlip.on('flip', () => updatePager());

  // Keyboard navigation
  viewer?.addEventListener('keydown', (e: Event) => {
    const key = (e as KeyboardEvent).key;
    if (key === 'ArrowLeft') pageFlip.flipPrev();
    else if (key === 'ArrowRight') pageFlip.flipNext();
  });

  updatePager();
});
