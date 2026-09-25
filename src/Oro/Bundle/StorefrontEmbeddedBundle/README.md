# OroStorefrontEmbeddedBundle

OroStorefrontEmbeddedBundle allows a foreign host application, such as a headless storefront, to render a real
OroCommerce storefront page inside its own page, with working datagrids, filters, dialogs and forms. It adds
embedded layout blocks over the views you make embeddable, an endpoint that serves such a block by id, and a
browser runtime that boots Oro's page-components inside the host document.

## Endpoints

* `GET|POST /embedded/fragment/{block}` - renders the storefront page behind `{block}` as a JSON fragment. GET
  returns the block markup plus, on the first render of a fragment, the boot metadata the embedded runtime needs;
  POST forwards a form submit to the target page and returns either the re-rendered block (validation errors) or
  the target's own redirect.
* `POST /embedded/session` - exchanges a storefront OAuth2 Bearer access token for a storefront session (a
  customer user token) or a customer visitor cookie (a guest token), so a host on a different origin can render
  session-based fragments. Answers `204 No Content` with `Cache-Control: no-store, private`.
* `GET /embedded/theme-assets` - lists the built theme files (public static files) a host may serve from its own
  origin instead of relaying every byte through its proxy.

Both `/embedded/session` and `/embedded/theme-assets` are reachable while storefront guest access is disabled
(`Oro\Bundle\StorefrontEmbeddedBundle\GuestAccess\EmbeddedGuestAccessAllowedUrlsProvider`); `/embedded/fragment/*`
is not, so a guest fragment request is refused exactly like a direct visit to the page it embeds would be.

## Making a Page Embeddable

1. Add a layout update `Resources/views/layouts/<theme>/<route_name>/embedded_blocks.yml` to the bundle that owns
   the page, gated on `conditions: 'context["embedded"]'`, that `@add`s an `embedded_*` block.
2. Add a `blocks:` entry to that bundle's own `Resources/config/oro/storefront_embedded.yml`, mapping the block id
   to the route name. Every bundle's file is merged and validated at compile time by
   `Oro\Bundle\StorefrontEmbeddedBundle\DependencyInjection\StorefrontEmbeddedConfiguration`; a block id declared
   for two different routes fails the container build.
3. Optionally list, in the same file, the routes whose client urls the host has to proxy (`proxied_routes`) and
   the routes that answer with a file rather than a page (`file_routes`, or `any_block` for one that applies to
   every block).

```yaml
oro_storefront_embedded:
    blocks:
        embedded_my_page: my_bundle_frontend_my_page

    proxied_routes:
        embedded_my_page:
            - my_bundle_frontend_some_ajax_route

    file_routes:
        embedded_my_page:
            - my_bundle_frontend_download_route
```

## Request and Response Headers

The host marks every request it makes to Oro, the fragment itself and the AJAX the fragment's content causes,
with these headers:

* `X-Oro-Embedded` - present on every request the host makes; asset, file, and image urls are served as absolute
  Oro urls only while this header is present.
* `X-Oro-Embedded-Proxy-Prefix` - the path prefix the host serves Oro through, so a url the browser must read
  cross-origin is pointed back at the host instead.
* `X-Oro-Embedded-Asset-Roots` - a comma-separated list of root-relative paths the host serves from its own
  origin, which Oro must leave untouched.
* `X-Localization-ID`, `X-Currency` - honored on the fragment request itself only; they override the customer
  user's saved localization and currency for that one render.

On the fragment endpoint, Oro answers with `X-Oro-Embedded-Path` (the path of the target page, so the host can
keep its own address bar in sync) and a `Vary` header naming every one of the above headers the response depends
on, so a shared cache in front of Oro never mixes a variant rendered for one set of headers into another.

## Boot Metadata

A first fragment render (not a hash-navigation reload) adds this object to the JSON body, next to the block
markup and `flashMessages`:

```json
{
    "buildBase": "/build/<theme>/",
    "runtimeUrl": "/build/<theme>/embedded_app.js?v=<version>",
    "runtimeIntegrity": "",
    "styles": ["/build/<theme>/css/critical.css?v=<version>", "/build/<theme>/css/styles.css?v=<version>"],
    "printStyles": ["/build/<theme>/css/styles-print.css?v=<version>"],
    "proxiedRoutes": ["..."],
    "filePaths": ["/attachment/", "/pdf-document/"]
}
```

Every url is left relative when the host serves that path itself (`X-Oro-Embedded-Asset-Roots`), and made
absolute against Oro otherwise.

## Extension Points

The bundle's own services carry stable, documented ids so a project bundle can decorate them:

* `oro_storefront_embedded.request.embedded_request_checker` - tells whether the current request is embedded,
  the host's proxy prefix, and its asset roots; every other service in the bundle asks it.
* `oro_storefront_embedded.asset.embedded_request_asset_packages` (decorates `assets.packages`),
  `oro_storefront_embedded.provider.embedded_request_file_url` (decorates `oro_attachment.provider.file_url`),
  `oro_storefront_embedded.provider.embedded_request_imagine_url` (decorates
  `oro_attachment.imagine.provider.url`), `oro_storefront_embedded.tools.embedded_request_url_helper` (decorates
  `oro_ui.tools.url_helper`) - make root-relative urls absolute for an embedded request.
* `oro_storefront_embedded.fragment.embedded_fragment_target_resolver`,
  `oro_storefront_embedded.fragment.embedded_fragment_access_checker`,
  `oro_storefront_embedded.fragment.embedded_fragment_renderer`,
  `oro_storefront_embedded.fragment.embedded_fragment_response_decorator` - the fragment pipeline
  `EmbeddedFragmentController` calls in order: resolve the block to its target page, check the feature, ACL and
  CSRF rules of that page, render it through an internal sub-request, then decorate the response.
* `oro_storefront_embedded.security.storefront_access_token_resolver`,
  `oro_storefront_embedded.security.embedded_session_opener` - validate the Bearer token and open the storefront
  session `EmbeddedSessionController` calls in order.
* `oro_storefront_embedded.provider.embedded_route_paths` - the cache-warmed source of the boot metadata's
  `proxiedRoutes` and `filePaths`, rebuilt whenever the container's warm cache is rebuilt.
* `oro_storefront_embedded.provider.embedded_theme_assets` - lists the built theme files, cached per theme set
  and asset build version in the `oro_storefront_embedded.cache.theme_assets` pool.
