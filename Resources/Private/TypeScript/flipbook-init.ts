import { PageFlip } from 'page-flip';

// ---------------------------------------------------------------------------
// Viewer Offset: Abstand vom Viewport-Oberkante zum Viewer-Element
// ---------------------------------------------------------------------------

/**
 * Berechnet den Offset vom oberen Viewport-Rand zum Viewer-Element.
 * Wird als CSS Custom Property gesetzt, damit die CSS-Hoehe korrekt ist.
 */
function updateViewerOffsets(): void {
  document.querySelectorAll<HTMLElement>('.flipbook-viewer').forEach((viewer) => {
    const rect = viewer.getBoundingClientRect();
    viewer.style.setProperty('--viewer-offset', `${Math.max(0, Math.round(rect.top))}px`);
  });
}

// Initiale Berechnung
updateViewerOffsets();

// iOS Safari: Offset aktualisieren wenn sich der Viewport aendert
// (Adressleiste ein-/ausblenden, Tastatur, Orientierung)
// Debounced um Layout Thrashing zu vermeiden — visualViewport.resize
// feuert bei jedem Frame waehrend der iOS Toolbar-Animation
let offsetTimeout: ReturnType<typeof setTimeout>;
const debouncedOffsetUpdate = (): void => {
  clearTimeout(offsetTimeout);
  offsetTimeout = setTimeout(updateViewerOffsets, 100);
};

if (window.visualViewport) {
  window.visualViewport.addEventListener('resize', debouncedOffsetUpdate);
} else {
  window.addEventListener('resize', debouncedOffsetUpdate);
}

// ---------------------------------------------------------------------------
// Flipbook Initialisierung
// ---------------------------------------------------------------------------

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

  /**
   * Berechnet die maximalen Seitenabmessungen aus dem verfuegbaren Platz.
   * Beruecksichtigt sowohl Breite als auch Hoehe des Stage-Containers.
   */
  function calcMaxDimensions(): { maxPageWidth: number; maxPageHeight: number } {
    const stage = container.closest('.flipbook-viewer__stage') as HTMLElement | null;
    const pagesVisible = isMobile ? 1 : 2;

    const availableWidth = stage ? stage.clientWidth : window.innerWidth;
    const availableHeight = stage ? stage.clientHeight : window.innerHeight;

    // Fit by width
    const maxByWidth = Math.floor(availableWidth / pagesVisible);
    // Fit by height (derive page width from available height)
    const maxByHeight = Math.floor(availableHeight / aspectRatio);
    // Use the smaller to ensure the book fits both dimensions
    const maxPageWidth = Math.min(maxByWidth, maxByHeight);
    const maxPageHeight = Math.floor(maxPageWidth * aspectRatio);

    return { maxPageWidth, maxPageHeight };
  }

  const { maxPageWidth, maxPageHeight } = calcMaxDimensions();

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

  // Load from DOM elements -> native browser rendering quality
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

  // ---------------------------------------------------------------------------
  // ResizeObserver: maxWidth/maxHeight dynamisch aktualisieren
  // ---------------------------------------------------------------------------
  // Warum ResizeObserver statt window.resize?
  // 1. page-flip hat bereits einen eigenen window.resize Handler (UI.ts Zeile 66),
  //    der intern update() aufruft — aber mit den ALTEN maxWidth/maxHeight Werten.
  // 2. ResizeObserver auf dem Stage-Element reagiert praeziser auf Container-Aenderungen.
  // 3. Der Debounce stellt sicher, dass die Settings aktualisiert sind, BEVOR
  //    pageFlip.update() aufgerufen wird.
  //
  // Interne API verifiziert fuer page-flip v2.x (nodlim/page-flip):
  //   - PageFlip.ts:29 → "private readonly setting: FlipSetting" (Singular!)
  //   - Render erhaelt dieselbe Objekt-Referenz (PageFlip.ts:106)
  //   - UI.ts:59-62 → autoSize setzt container.style.maxWidth einmalig
  // ---------------------------------------------------------------------------
  const stage = container.closest('.flipbook-viewer__stage') as HTMLElement | null;

  if (stage) {
    let resizeTimeout: ReturnType<typeof setTimeout>;

    const observer = new ResizeObserver(() => {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(() => {
        const dims = calcMaxDimensions();

        // page-flip Settings-Objekt aktualisieren (interne API, abgesichert mit try/catch)
        try {
          const setting = (pageFlip as any).setting;
          if (setting) {
            setting.maxWidth = dims.maxPageWidth;
            setting.maxHeight = dims.maxPageHeight;
          }

          // autoSize setzt bei Init einen inline style auf dem Container:
          //   container.style.maxWidth = setting.maxWidth * 2 + 'px'  (UI.ts Zeile 61)
          // Dieser inline Style wird NICHT automatisch aktualisiert.
          container.style.maxWidth = dims.maxPageWidth * (isMobile ? 1 : 2) + 'px';
        } catch (_) {
          // Graceful fallback: page-flip internes API hat sich geaendert
        }

        // page-flip neu rendern: setzt boundsRect = null → calculateBoundsRect()
        // liest die aktualisierten setting.maxWidth/maxHeight
        pageFlip.update();
      }, 150);
    });

    observer.observe(stage);
  }

  // ---------------------------------------------------------------------------
  // Navigation
  // ---------------------------------------------------------------------------
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
