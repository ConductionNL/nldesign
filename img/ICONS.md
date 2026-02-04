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

## Logos (6 total)

Available in: `img/logos/`

- amsterdam
- ggd-amsterdam
- museum_weesp
- stadsarchief
- stadsbank-van-lening
- vga-verzekeringen

## Usage in Nextcloud

To use these icons in your Nextcloud app:

```php
// In your template or controller
\OCP\Util::addStyle('nldesign', 'icons');

// Then reference the icon
<img src="<?php p(\OC::$server->getURLGenerator()->imagePath('nldesign', 'icons/MagnifyingGlass.svg')); ?>" alt="Search">
```

## Documentation

View all icons at: https://designsystem.amsterdam/?path=/docs/brand-assets-icons--docs

## License

Icons from @amsterdam/design-system-assets (Mozilla Public License 2.0)
