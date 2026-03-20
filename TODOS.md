# To Do Liste für das Projekt

## 1. ~~Plugin "Shortcut" in Content Wizard~~ ✅

~~Ich möchte, dass das Plugin auch direkt installiert werden kann, ohne dass man im Plugin CE über Plugins eine Auswahl
treffen muss.~~

**Erledigt (2026-03-20):** Plugin als eigener CType `digitalpageflip_flipbook` registriert. Erscheint direkt im
New Content Element Wizard unter "Plugins" mit Icon und Beschreibung. Kein Umweg über generisches Plugin-CE mehr nötig.

## 2. ~~FlexForms~~ ✅

~~Ich bin kein großer Freund von FlexForms in der Datenbank. Deshalb möchte ich, dass die Auswahl des FlipBooks nicht
per FlexForms persistiert wird. Stattdessen möchte ich das in einen eigenen RecordType legen.~~

**Erledigt (2026-03-20):** FlexForm komplett entfernt. Flipbook-Auswahl wird jetzt als echtes TCA-Feld
`tx_digitalpageflip_flipbook` direkt auf `tt_content` gespeichert — kein serialisiertes XML mehr.
