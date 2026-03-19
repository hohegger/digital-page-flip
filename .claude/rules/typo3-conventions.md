---
paths:
  - "Classes/**/*.php"
  - "Configuration/**/*.php"
  - "ext_localconf.php"
  - "ext_emconf.php"
---

# TYPO3 Konventionen

## Namespace
- Vendor: Kit
- Extension: DigitalPageFlip
- Pattern: \Kit\DigitalPageFlip\{Category}\{ClassName}

## Extbase
- Controller: final class, extends ActionController
- Actions geben ResponseInterface zurück (return $this->htmlResponse())
- Models: AbstractEntity, getter/setter, typisierte Properties
- Repositories: extends Repository, Custom Queries via QueryBuilder

## TCA
- Labels über LLL:EXT:digital_page_flip/Resources/Private/Language/locallang_db.xlf
- ctrl-Sektion: immer tstamp, crdate, delete, enablecolumns
- FAL-Felder: type => 'file', allowed => 'pdf' bzw. 'common-image-types'

## Plugin-Registrierung
- ext_localconf.php: ExtensionUtility::configurePlugin()
- PLUGIN_TYPE_CONTENT_ELEMENT verwenden
- FlexForm für Plugin-Konfiguration

## v14-Kompatibilität
- Keine @annotations für Extbase, nur PHP Attributes vorbereiten
- Kein TypoScriptFrontendController direkt verwenden
- AssetCollector für CSS/JS Einbindung
