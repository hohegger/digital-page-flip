---
paths:
  - "Resources/Public/**/*.js"
  - "Resources/Public/**/*.css"
  - "Resources/Private/Templates/**/*.html"
  - "Resources/Private/Partials/**/*.html"
  - "Resources/Private/Layouts/**/*.html"
---

# Frontend-Regeln

## JavaScript
- Vanilla JS (ES Modules), kein jQuery
- StPageFlip als einzige externe Dependency
- const als Default, let nur bei Reassignment, niemals var
- data-Attribute für JS-Konfiguration (data-flipbook-pages, data-flipbook-config)
- Event Delegation wo sinnvoll

## CSS
- Minimales Styling, nur das Nötigste für den Flipbook-Container
- BEM-Namenskonvention: .digital-page-flip, .digital-page-flip__page
- Mobile First: Basis-Styles für kleine Screens, min-width Breakpoints
- Custom Properties für konfigurierbare Werte (--dpf-max-width, --dpf-aspect-ratio)
- Keine CSS-Frameworks, Extension soll neutral bleiben

## Fluid Templates
- Layouts/Default.html als Basis-Layout
- data-Attribute statt Inline-JS in Templates
- ViewHelper für JSON-Datenaufbereitung (FlipbookDataViewHelper)
- Keine Logik in Templates — nur Darstellung

## Accessibility
- Flipbook-Container: role="region", aria-label="Blätterkatalog: {title}"
- Tastaturnavigation: Pfeiltasten für Seitenwechsel
- prefers-reduced-motion: Blätteranimation deaktivieren
- Alternativtext für Seitenbilder (Seitennummer)
