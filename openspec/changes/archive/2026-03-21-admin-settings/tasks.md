# Tasks: admin-settings

## 1. Panel Registration
- [x] 1.1 Register in theming section with priority 50
- [x] 1.2 Return TemplateResponse with tokenSets, currentTokenSet, hideSlogan, showMenuLabels

## 2. Template
- [x] 2.1 Token set dropdown with data-design-system attribute
- [x] 2.2 Hide slogan checkbox with localized label
- [x] 2.3 Show menu labels checkbox with localized label
- [x] 2.4 Live preview box
- [x] 2.5 Token editor mount point
- [x] 2.6 External links to nldesign.app and nldesignsystem.nl
- [x] 2.7 XSS prevention via p(json_encode())

## 3. Unit Tests (ADR-009)
- [x] 3.1 Test Admin::getSection() returns 'theming'
- [x] 3.2 Test Admin::getPriority() returns 50

## 4. Documentation (ADR-010)
- [x] 4.1 Feature documentation at docs/features/admin-settings.md (exists)

## 5. Internationalization (ADR-005)
- [x] 5.1 All user-visible text uses $l->t() for localization
