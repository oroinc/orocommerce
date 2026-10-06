# OroStorefrontEmbeddedBundle

OroStorefrontEmbeddedBundle provides embedded layout blocks for a storefront application. An embedded layout block
is a part of an OroCommerce page that a different application shows.

This document uses these terms:

* The OroCommerce application is the application that contains this bundle. It renders the embedded layout blocks.
* The storefront application is a separate application. It shows the embedded layout blocks in its own pages.
* The server of the storefront application is the backend of the storefront application. It proxies (forwards) the
  requests of the storefront application to the OroCommerce application.

## Concept

The storefront application requests a block from `/embedded/fragment/{block}`. Each block has a target page. The
target page is the OroCommerce route that the configuration maps to the block. The OroCommerce application renders
the target page in a sub-request (an internal request in the same application). Then it returns the block markup as
JSON.

## Making a Page Embeddable

Do these steps in the OroCommerce application:

1. Add the layout update `Resources/views/layouts/<theme>/<route_name>/embedded_blocks.yml`.
2. In this layout update, set the condition `conditions: 'context["embedded"]'`.
3. In this layout update, use `@add` to add an `embedded_*` block.
4. In `Resources/config/oro/storefront_embedded.yml`, map the block id to the route name.

Do not declare one block id for two different routes. If you do, the container build fails.

```yaml
oro_storefront_embedded:
    blocks:
        embedded_my_page: my_bundle_frontend_my_page
```

The embedded runtime treats links that start with `/attachment/`, `/pdf-document/` or `/customer/order/pdf-document/`
as files.

## Endpoints

* `GET|POST /embedded/fragment/{block}` returns the block as JSON. On the first render, the response also
  contains the boot metadata. The boot metadata is described below. A POST request sends a form submission to the
  target page.
* `GET /embedded/session` is the target of the OAuth2 session transfer. This transfer opens an embedded session.
  The endpoint answers `204 No Content`.
* `POST /embedded/preferences` stores the localization and the currency of the embedded session.
* `GET /embedded/theme-assets` lists the built theme files.

If guest access to the OroCommerce storefront is disabled, guests can open only `/embedded/theme-assets`.

## Boot Metadata

The storefront application must use the boot metadata to load the embedded runtime. The embedded runtime is a
JavaScript file that starts the OroCommerce page components inside the page of the storefront application. The boot
metadata has this structure:

```json
{
    "runtimeUrl": "/build/<theme>/embedded_app.js?v=<version>",
    "runtimeIntegrity": "",
    "styles": ["/build/<theme>/css/critical.css?v=<version>", "/build/<theme>/css/styles.css?v=<version>"],
    "printStyles": ["/build/<theme>/css/styles-print.css?v=<version>"]
}
```
