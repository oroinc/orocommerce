# Oro\Bundle\RedirectBundle\Api\Model\Route

## ACTIONS

### get

Check storefront URL path route and map it to correspondent API resource.

Route identifier should contain URL path where all slash characters replaced with colon.

Example: route identifier for the URL path `/navigation-root/products/medical` is `:navigation-root:products:medical`.

## FIELDS

### url

The relative URL of the route.

### isSlug

Indicates whether the route is a slug.

### redirectUrl

The relative URL of a route for which this slug is an alias.

### redirectStatusCode

The HTTP status code that is used to redirect to a route for which this slug is an alias, e.g. **301**, **302**, etc.

### resourceType

A string represents the type of a resource.

### apiUrl

The relative URL of an API resource that be used to get the data.
