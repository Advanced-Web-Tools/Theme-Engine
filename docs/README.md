# ThemeEngine documentation

Developer documentation for ThemeEngine 1.0.0. The [project readme](../readme.md) introduces the product and its user experience.

| Guide | Use it for |
| --- | --- |
| [Building a theme](theme-building.md) | Creating a package, declaring pages and routes, implementing content providers, adding settings, styling the layout, and checking previews. |
| [API usage](api.md) | Accessing the shared engine, editing theme configuration, using presets, rendering previews, and handling events. |
| [Installation and development](development.md) | Requirements, architecture, storage, cache, dashboard behavior, and development builds. |

Start with the theme-building guide for a complete URPC JSON example. Dynamic JSON blocks use descriptor renderers; PHP section providers are an optional alternative for generating section sources. Use the API guide when integrating another AWT package or extending rendering behavior.

All commands assume the repository root unless stated otherwise. ThemeEngine relies on AWT and URPC; the examples run within their package environment rather than a standalone PHP application.
