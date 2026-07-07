# NL Design — Overheidsfunctionaliteiten

> Functiepagina voor Nederlandse overheidsorganisaties.
> Gebruik deze checklist om te toetsen aan uw Programma van Eisen.

**Product:** NL Design
**Categorie:** Overheidstheming & NL Design System integratie
**Licentie:** EUPL-1.2 (vrije open source)
**Leverancier:** Conduction B.V.
**Platform:** Nextcloud (self-hosted / on-premise / cloud)

## Legenda

| Status | Betekenis |
|--------|-----------|
| Beschikbaar | Functionaliteit is beschikbaar in de huidige versie |
| Gepland | Functionaliteit staat op de roadmap |
| Via platform | Functionaliteit wordt geleverd door Nextcloud |
| Op aanvraag | Beschikbaar als maatwerk |
| N.v.t. | Niet van toepassing voor dit product |

---

## 1. Functionele eisen

### Theming & Huisstijl

| # | Eis | Status | Toelichting |
|---|-----|--------|-------------|
| F-01 | NL Design System token-ondersteuning | Beschikbaar | Design tokens van NL Design System community |
| F-02 | Meerdere token sets (gemeenten, provincies) | Beschikbaar | VNG, Den Haag, Rotterdam, Utrecht, etc. |
| F-03 | Token set selectie per organisatie | Beschikbaar | Dropdown in admin settings |
| F-04 | Aangepaste token sets uploaden | Beschikbaar | Eigen huisstijl als token set (CSS of W3C Design Tokens JSON) — zie [Custom token sets](features/custom-token-sets.md) |
| F-05 | Live preview van theming | Beschikbaar | Direct zichtbare wijzigingen |
| F-06 | Nextcloud Theming synchronisatie | Beschikbaar | Design tokens synchroniseren naar Nextcloud thema |
| F-07 | CSS-variabelen architectuur | Beschikbaar | Geen hardgecodeerde kleuren |
| F-08 | Componenttokens | Beschikbaar | Tokens voor knoppen, formulieren, navigatie, etc. |

### Toegankelijkheid

| # | Eis | Status | Toelichting |
|---|-----|--------|-------------|
| F-09 | WCAG 2.1 AA-conforme kleuren | Beschikbaar | Contrastverhouding gegarandeerd |
| F-10 | Focusindicatoren | Beschikbaar | Zichtbare focus-states via tokens |
| F-11 | Responsief ontwerp | Beschikbaar | Mobile-first via design tokens |
| F-12 | Lettertypeconfiguratie | Beschikbaar | Font-tokens per organisatie |

### App-compatibiliteit

| # | Eis | Status | Toelichting |
|---|-----|--------|-------------|
| F-13 | Theming voor alle Conduction-apps | Beschikbaar | OpenRegister, Procest, Pipelinq, etc. |
| F-14 | Nextcloud-brede theming | Beschikbaar | Alle Nextcloud-pagina's gestyled |
| F-15 | Toggle per app (aan/uit) | Beschikbaar | Per-app theming via "Theming per app" in de adminsectie — zie [features/toggles.md](features/toggles.md#theming-per-app) |

---

## 2. Technische eisen

| # | Eis | Status | Toelichting |
|---|-----|--------|-------------|
| T-01 | On-premise / self-hosted | Beschikbaar | Nextcloud-app |
| T-02 | Open source | Beschikbaar | EUPL-1.2, Codeberg |
| T-03 | CSS Custom Properties | Beschikbaar | Standaard W3C-mechanisme |
| T-04 | NL Design System community-tokens | Beschikbaar | Officiële community-tokens |
| T-05 | Geen JavaScript-overhead | Beschikbaar | Pure CSS-theming |

---

## 3. Beveiligingseisen

| # | Eis | Status | Toelichting |
|---|-----|--------|-------------|
| B-01 | Alleen admin kan theming wijzigen | Beschikbaar | Nextcloud admin-rechten |
| B-02 | Geen data-opslag | Beschikbaar | Alleen visuele theming, geen gegevensverwerking |

---

## 4. Privacyeisen (AVG/GDPR)

| # | Eis | Status | Toelichting |
|---|-----|--------|-------------|
| P-01 | Geen persoonsgegevens | N.v.t. | Theming bevat geen PII |

---

## 5. Toegankelijkheidseisen

| # | Eis | Status | Toelichting |
|---|-----|--------|-------------|
| A-01 | WCAG 2.1 AA | Beschikbaar | Kern-functionaliteit van NL Design |
| A-02 | EN 301 549 | Beschikbaar | Via WCAG AA |
| A-03 | Contrastverhouding minimaal 4.5:1 (tekst) | Beschikbaar | Via design tokens afgedwongen |
| A-04 | Contrastverhouding minimaal 3:1 (grote tekst/UI) | Beschikbaar | Via design tokens afgedwongen |
| A-05 | Zichtbare focusindicatoren | Beschikbaar | Focus-tokens voor alle interactieve elementen |
| A-06 | Responsief ontwerp (320px – 2560px) | Beschikbaar | Via responsive tokens |
| A-07 | Aanpasbare lettergrootte | Beschikbaar | Font-size tokens |
| A-08 | Hoog contrast modus | Gepland | High-contrast token set |

---

## 6. Integratiestandaarden

| # | Eis | Status | Toelichting |
|---|-----|--------|-------------|
| I-01 | NL Design System (officiële standaard) | Beschikbaar | Volledige integratie met community-tokens |
| I-02 | Rijkshuisstijl | Beschikbaar | Tokens beschikbaar voor rijksoverheidsorganisaties |
| I-03 | Gemeentelijke huisstijlen | Beschikbaar | Meerdere gemeenten ondersteund |
| I-04 | VNG token set | Beschikbaar | Standaard VNG-tokens |
| I-05 | Nextcloud Theming API | Beschikbaar | Synchronisatie met native theming |

---

## 7. Beheer en onderhoud

| # | Eis | Status | Toelichting |
|---|-----|--------|-------------|
| BO-01 | Nextcloud App Store | Beschikbaar | Installatie via App Store |
| BO-02 | Automatische updates | Beschikbaar | Via Nextcloud app-updater |
| BO-03 | Admin settings pagina | Beschikbaar | Token set selectie en configuratie |
| BO-04 | Documentatie | Beschikbaar | Feature docs beschikbaar |
| BO-05 | Open source community | Beschikbaar | Codeberg Issues |
| BO-06 | Professionele ondersteuning (SLA) | Op aanvraag | Via Conduction B.V. |

---

## 8. Onderscheidende kenmerken

| Kenmerk | Toelichting |
|---------|-------------|
| **NL Design System native** | Enige Nextcloud-app die volledig NL Design System ondersteunt |
| **Eén-klik huisstijl** | Selecteer uw organisatie-tokens en de hele omgeving past zich aan |
| **Alle apps gestyled** | Niet alleen Nextcloud, ook Procest, Pipelinq, OpenRegister, etc. |
| **Community-tokens** | Onderhouden door de NL Design System community |
| **WCAG AA gegarandeerd** | Contrastverhouding en focus-states ingebouwd in tokens |
| **Geen vendor lock-in** | Open standaard, open source, geen afhankelijkheid |
