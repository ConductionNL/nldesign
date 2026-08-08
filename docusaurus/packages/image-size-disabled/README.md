# Parser-free `image-size` compatibility package

Docusaurus imports `image-size/fromFile` unconditionally, but NL Design does
not use local Markdown images. This private package keeps that import stable
without installing any image parser. It returns an empty dimensions object,
which is an accepted result because Docusaurus treats width and height as
optional.

`check-assets.mjs` rejects local Markdown image nodes before every CI build.
Static theme assets such as the navbar logo do not pass through the Docusaurus
Markdown image transformer and remain supported.
