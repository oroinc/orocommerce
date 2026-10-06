# OroStorefrontEmbeddedBundle

OroStorefrontEmbeddedBundle provides embedded fragments for a storefront application. An embedded fragment is a part of
an OroCommerce page that a different application shows.

This document uses these terms:

* The OroCommerce application is the application that contains this bundle. It renders the embedded fragments.
* The storefront application is a separate application. It shows the embedded fragments in its own pages.
* The server of the storefront application is the backend of the storefront application. It proxies (forwards) the
  requests of the storefront application to the OroCommerce application.

## Concept

The storefront application requests a fragment from `/embedded/fragment/{fragment}`. Each fragment has a target page.
The target page is the OroCommerce route that the configuration maps to the fragment. The OroCommerce application
renders the target page and returns the fragment markup as JSON. The fragment markup is the markup of the layout blocks
that the fragment renders.

## Making a Page Embeddable

Do these steps in the OroCommerce application:

1. Add the layout update `Resources/views/layouts/<theme>/<route_name>/embedded_blocks.yml`.
2. In this layout update, set the condition `conditions: 'context["is_embedded_fragment"]'`.
3. In this layout update, use `@add` to add the layout blocks that the fragment renders.
4. In `Resources/config/oro/storefront_embedded.yml`, map the fragment name to the route name, or to an expanded
   definition.

The fragment name is the key under `fragments`. It must match `embedded_\w+`.

In the short form, the value is the route name:

```yaml
oro_storefront_embedded:
    fragments:
        embedded_my_page: my_bundle_frontend_my_page
```

In the expanded form, the value has these options:

* `route_name` - the route name of the target page.
* `layout_block_ids` - the ids of the layout blocks that the fragment renders. If you do not set it, the fragment
  renders only the layout block that has the same id as the fragment name. The short form uses the same default.

```yaml
oro_storefront_embedded:
    fragments:
        embedded_my_page:
            route_name: my_bundle_frontend_my_page
            layout_block_ids: [block1, block2]
```

A fragment request can also render blocks that are not in `layout_block_ids`. Listeners of an event add these blocks,
and other bundles can add their own listeners. See [Fragment Blocks and Route
Parameters](#fragment-blocks-and-route-parameters).

If two bundles declare the same fragment name, the definition from the bundle that loads last replaces the other
definition fully. OroCommerce does not merge the configuration of the two definitions. As a result, the override must
set `route_name` again. The override does not keep the `layout_block_ids` of the earlier definition. If the override
does not set `layout_block_ids`, the default applies.

A bundle with a higher priority number in its `Resources/config/oro/bundles.yml` file loads later.
OroStorefrontEmbeddedBundle has priority 70. To override one of its fragments, your bundle must have a priority number
higher than 70.

The embedded runtime (see [Boot Metadata](#boot-metadata)) treats links that start with `/attachment/`, `/pdf-document/`
or `/customer/order/pdf-document/` as files.

## Fragment Blocks and Route Parameters

`Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedFragmentUrlResolver::resolveFragmentUrl()` makes the URL of the
target page of a fragment. Its service id is `oro_storefront_embedded.request.embedded_fragment_url_resolver`. The
method does not need the fragment request, so you can call it from other code. Before it generates the URL, it
dispatches `Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResolveEvent`. The `$context` argument goes to the
listeners of this event.

The resolver sets the `layout_block_ids` route parameter to the block ids of the event. This value replaces a
`layout_block_ids` parameter that the caller sent.

The resolver throws these exceptions:

* `\Symfony\Component\HttpKernel\Exception\NotFoundHttpException` - the fragment or its route does not exist.
* `\Symfony\Component\HttpKernel\Exception\BadRequestHttpException` - the route parameters are not valid for the route.
* `\LogicException` - the block list is empty after the event. The router drops an empty `layout_block_ids` parameter,
  and then the target page renders in full.

To find if a fragment exists, or to read the definition of a fragment, use
`Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedFragmentRegistry`. Its service id is
`oro_storefront_embedded.request.embedded_fragment_registry`. The getters of the registry throw
`\InvalidArgumentException` for an unknown fragment. Before you call a getter, call `hasFragment()`.

### The Event

A listener of `EmbeddedFragmentResolveEvent` can change the block list and the route parameters. `addBlockId()` appends
a block to the block list. `setBlockIds()` replaces the full list.

For a fragment request, the context contains the `Request` object under the
`EmbeddedFragmentResolveEvent::CONTEXT_REQUEST` key. If another caller does not put a `Request` into the context, the
event has no request, and `getContextValue()` returns `null`.

### The Default Listeners

Two listeners add the default blocks:

* `EmbeddedFragmentJsModulesConfigBlockListener` - adds `layout_js_modules_config`.
* `EmbeddedFragmentTitleListener::onFragmentResolve()` - adds `title`.

### Route Parameters

The route parameters go to the router without a filter. This applies to the parameters of the caller and to the
parameters that the listeners set. For a fragment request, `EmbeddedFragmentRequestListener` removes the query
parameters with a name that starts with `_` before it calls the resolver. Other callers, and listeners that put
untrusted input into `setRouteParameters()`, must filter the input themselves.

### Example

This listener adds a block and a route parameter to one fragment:

```php
namespace Acme\Bundle\DemoBundle\EventListener;

use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResolveEvent;
use Symfony\Component\HttpFoundation\Request;

class EmbeddedMyPageFragmentListener
{
    public function onFragmentResolve(EmbeddedFragmentResolveEvent $event): void
    {
        if ($event->getFragmentName() !== 'embedded_my_page') {
            return;
        }

        $request = $event->getContextValue(EmbeddedFragmentResolveEvent::CONTEXT_REQUEST);
        if (!$request instanceof Request) {
            return;
        }

        $event->addBlockId('my_page_sidebar');

        $routeParameters = $event->getRouteParameters();
        $routeParameters['view'] = 'compact';
        $event->setRouteParameters($routeParameters);
    }
}
```

Register the listener in `Resources/config/services.yml` of your bundle:

```yaml
services:
    acme_demo.event_listener.embedded_my_page_fragment:
        class: Acme\Bundle\DemoBundle\EventListener\EmbeddedMyPageFragmentListener
        tags:
            - { name: kernel.event_listener, event: Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResolveEvent, method: onFragmentResolve }
```

The tag has no priority, so the priority is 0. The listener runs after the two default listeners, so `my_page_sidebar`
is the last block.

## Embedded Fragment Requests

An embedded fragment request is a request to `/embedded/fragment/{fragment}` that `EmbeddedFragmentRequestListener`
rewrote to the target page of the fragment. The listener puts the fragment name into the request attribute
`_embedded_fragment_name`.

To find if a request is an embedded fragment request, use
`Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker`. Its service id is
`oro_storefront_embedded.request.embedded_request_checker`. Do not read the attribute directly.

* `isEmbeddedFragmentRequest()` returns `true` if the request is an embedded fragment request.
* `getEmbeddedFragmentName()` returns the fragment name, or `null` if the request is not an embedded fragment request.

Both methods have one optional argument, `$request`. If you give `null` or no argument, the methods check the main
request. If there is no main request, `isEmbeddedFragmentRequest()` returns `false` and `getEmbeddedFragmentName()`
returns `null`.

## Fragment Response Content

`Oro\Bundle\StorefrontEmbeddedBundle\EventListener\Kernel\EmbeddedFragmentResponseListener` completes the response of a
fragment request. It listens to `kernel.response`. It dispatches
`Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResponseEvent` only when all of these conditions are true:

* The request is the main request.
* The request is an embedded fragment request. See [Embedded Fragment Requests](#embedded-fragment-requests).
* The response is successful (status code 2xx).
* The response body is layout JSON. Layout JSON is a JSON array or a JSON object.

If one of the conditions is false, the response stays as it is. If the body is not valid JSON, the listener writes an
error message to the log. If the body is valid JSON but is not a JSON array or a JSON object, the listener writes a
debug message to the log.

The listener dispatches the event for all request methods. If a listener of the event must run only for some request
methods, the listener must read the request method itself.

The response content is the decoded layout JSON as an array. The event holds the response content. After the event, the
listener writes the response content back to the response body. The body keeps the hex escaping of `JsonResponse`. As a
result, the markup in the body is safe to put inline in a script.

If the listener cannot encode the response content as JSON, it writes an error to the log and keeps the original layout
body. For example, a listener can set `NAN`, `INF` or a string that is not valid UTF-8. In this case, the flash messages
of this request are lost. The flash messages listener took them from the session, but the original body does not contain
them.

### The Event

`getContent()` returns the full response content, and `setContent()` replaces it. `getContentValue($name)` returns
`null` if the response content has no such key. To find a key with a `null` value, use `hasContentValue($name)`.
`setContentValue()` adds a key, or replaces the value of an existing key. `removeContentValue()` removes a key.

The context contains the `Request` object under the `EmbeddedFragmentResponseEvent::CONTEXT_REQUEST` key.

The event does not give the `Response` object. The response content replaces the response body, so a listener of the
event must change only the response content.

If your code needs the status code or the headers of the response, do not use this event. Listen to `kernel.response`
instead. Use `EmbeddedRequestChecker::isEmbeddedFragmentRequest()` to find if the request is an embedded fragment
request.

### The Default Listeners

Five listeners complete the response content:

* `EmbeddedFragmentTitleListener::onFragmentResponse()` sets `title`. It does this only on a GET request. The value is
  the plain text of the rendered `title` block. If the rendered value is missing or is not a string, the listener sets
  `''`.
* `EmbeddedFragmentFlashMessagesResponseListener` sets `flashMessages`. It does this only on a GET request. It takes the
  flash messages from the session, grouped by type. The session then no longer has these messages, so each message shows
  one time. If the request has no session with a flash bag, the value is an empty list. On a request with another
  method, the messages stay in the session. The next GET request shows them.
* `EmbeddedFragmentSubtreeUrlResponseListener` sets `subtreeUrl`. It does this for all request methods. The value is the
  path of the target page.
* `EmbeddedFragmentJsMetadataResponseListener` sets `runtimeUrl` and `runtimeIntegrity`. It does this only on a GET
  request that is not a hash navigation reload. If the response content already has a key with the same name, the new
  value replaces it.
* `EmbeddedFragmentStylesMetadataResponseListener` sets `styles` and `printStyles`. It does this only on a GET request
  that is not a hash navigation reload. If the response content already has a key with the same name, the new value
  replaces it.

Together, the last two listeners add the boot metadata. The boot metadata tells the storefront application how to load
the embedded runtime. See [Boot Metadata](#boot-metadata).

All five listeners have priority 0. The order of the keys in the response content is not part of the contract. Do not
depend on it.

The priority of your listener controls which values it sees:

* If your listener has a priority higher than 0, it runs before the default listeners. The default listeners can replace
  the values that it sets for their keys.
* If your listener has priority 0, it runs before or after the default listeners. The order depends on the registration
  order of the services. Use priority 0 only for keys that the default listeners do not set.
* If your listener has a negative priority, it runs after the default listeners. It sees all their values. Use a
  negative priority to change or remove a value of a default listener.

### Example

This listener adds a value to the response content of one fragment. On a GET request, it also changes the title:

```php
namespace Acme\Bundle\DemoBundle\EventListener;

use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResponseEvent;
use Symfony\Component\HttpFoundation\Request;

class EmbeddedMyPageResponseListener
{
    public function onFragmentResponse(EmbeddedFragmentResponseEvent $event): void
    {
        if ($event->getFragmentName() !== 'embedded_my_page') {
            return;
        }

        $event->setContentValue('viewMode', 'compact');

        $request = $event->getContextValue(EmbeddedFragmentResponseEvent::CONTEXT_REQUEST);
        if (!$request instanceof Request || !$request->isMethod(Request::METHOD_GET)) {
            return;
        }

        $title = $event->getContentValue('title');
        if (\is_string($title) && $title !== '') {
            $event->setContentValue('title', $title . ' - My Page');
        }
    }
}
```

Register the listener in `Resources/config/services.yml` of your bundle:

```yaml
services:
    acme_demo.event_listener.embedded_my_page_response:
        class: Acme\Bundle\DemoBundle\EventListener\EmbeddedMyPageResponseListener
        tags:
            - { name: kernel.event_listener, event: Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResponseEvent, method: onFragmentResponse, priority: -10 }
```

The listener changes `title`, so it must run after `EmbeddedFragmentTitleListener`. For this reason, the tag sets a
negative priority.

## Endpoints

* `GET|HEAD|POST|PUT /embedded/fragment/{fragment}` returns the fragment as JSON. If the fragment name is not
  configured under `fragments`, the endpoint returns `404 Not Found`. A request with another method returns
  `405 Method Not Allowed`. A successful response also contains the values that the listeners of
  `EmbeddedFragmentResponseEvent` add. See [Fragment Response Content](#fragment-response-content).
* `GET /embedded/session` is the target of the OAuth2 session transfer. This transfer opens an embedded session. The
  endpoint returns `204 No Content`.
* `POST /embedded/preferences` stores the localization and the currency of the embedded session.
* `GET /embedded/theme-assets` lists the built theme files.

If guest access to the OroCommerce storefront is disabled, guests can open only `/embedded/theme-assets`.

## Boot Metadata

The storefront application must use the boot metadata to load the embedded runtime. The embedded runtime is a JavaScript
file that starts the OroCommerce page components inside the page of the storefront application. The boot metadata has
this structure:

```json
{
    "runtimeUrl": "/build/<theme>/embedded_app.js?v=<version>",
    "runtimeIntegrity": "",
    "styles": ["/build/<theme>/css/critical.css?v=<version>", "/build/<theme>/css/styles.css?v=<version>"],
    "printStyles": ["/build/<theme>/css/styles-print.css?v=<version>"]
}
```
