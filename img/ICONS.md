# Amsterdam Design System Icons & Logos

This directory contains SVG icons and logos from the Amsterdam Design System.

## Icons (344 total)

Available in: `img/icons/`

- Airplane
- Apple
- AppleFill
- Area
- AreaFill
- ArrowBackward
- ArrowDown
- ArrowForward
- ArrowUp
- AwardRibbon
- AwardRibbonFill
- BabyBottle
- BabyBottleFill
- Ball
- BankCard
- BankCardFill
- BarChart
- BarChartFill
- Bed
- Bell

... and 324 more

## Logos (23 total)

Available in: `img/logos/`

- amsterdam
- denhaag
- dinkelland
- drechterland
- epe
- ggd-amsterdam
- hoorn
- leiden
- museum_weesp
- nijmegen
- noaberkracht
- noordwijk
- provincie-zuid-holland
- rijkshuisstijl
- rotterdam
- stadsarchief
- stadsbank-van-lening
- tilburg
- tubbergen
- utrecht
- vga-verzekeringen
- vng
- xxllnc

## Usage in Nextcloud

To use these icons in your Nextcloud app:

```php
// In your template or controller
\OCP\Util::addStyle('nldesign', 'icons');

// Then reference the icon
<img src="<?php p(\OC::$server->getURLGenerator()->imagePath('nldesign', 'icons/Search.svg')); ?>" alt="Search">
```

These URLs only resolve while the nldesign app is enabled. Consumers must ship a fallback icon or declare a dependency on `nldesign`.

## Naming stability (public API)

Icon and logo filenames are a public API consumed by other apps. Renaming or removing a bundled asset is a **breaking change**: record it in the changelog (old + new name) and update this file in the same change so the inventory regression test (`tests/Unit/IconAssetsTest.php`) keeps passing. Syncing a newer Amsterdam Design System release is an explicit, reviewed change.

## Documentation

View all icons at: https://designsystem.amsterdam/?path=/docs/brand-assets-icons--docs

## License

Icons from @amsterdam/design-system-assets (Mozilla Public License 2.0).
The icons are MPL-2.0; this notice must remain co-located with the assets.
