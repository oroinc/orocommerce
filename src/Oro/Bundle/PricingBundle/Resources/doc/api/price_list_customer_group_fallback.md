# Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback

## ACTIONS

### get

Get details of the price list customer group fallback by its ID.

{@inheritdoc}

### get_list

Get the collection of price list customer group fallbacks. A collection may contain all fallbacks or may be filtered using standard <a href="https://www.oroinc.com/doc/orocommerce/current/dev-guide/integration#filters">filters</a>.

{@inheritdoc}

### create

Create a new price list customer group fallback.

`fallback` value should be one of: `0` or `1`.

Fallback `0` maps to Website fallback. Fallback `1`  maps to Current Customer Group Only fallback.

{@inheritdoc}

{@request:json_api}
Example:

`</api/pricelistcustomergroupfallbacks>`

```JSON
{
    "data": {
        "type": "pricelistcustomergroupfallbacks",
        "attributes": {
            "fallback": 1
        },
        "relationships": {
            "customerGroup": {
                "data": {
                    "type":"customer_groups",
                    "id": "1"
                }
            }
        }
    }
}
```
{@/request}

### update

Update details of the price list customer group fallback identified by ID.

`fallback` value should be one of: `0` or `1`.

Fallback `0` maps to Website fallback. Fallback `1`  maps to Current Customer Group Only fallback.

{@inheritdoc}

{@request:json_api}
Example:

`</api/pricelistcustomergroupfallbacks/1>`
 
```JSON
{
    "data": {
        "id": "1",
        "type": "pricelistcustomergroupfallbacks",
        "attributes": {
            "fallback": 0
        }
    }
}
```
{@/request}

### delete

Delete a price list customer group fallback identified by ID.

{@inheritdoc}

### delete_list

Delete a collection of price list customer group fallbacks. A collection may contain all fallbacks or may be filtered using standard <a href="https://www.oroinc.com/doc/orocommerce/current/dev-guide/integration#filters">filters</a>.

{@inheritdoc}

## FIELDS

### id

#### update

{@inheritdoc}

**The required field**

### fallback

Possible values: 1, 0. 0 - fallback to a website configuration. 1 - fallback to the current customer group only.

#### create

{@inheritdoc}

**The required field**

### customerGroup

The customer group this fallback is tied to.

#### create

{@inheritdoc}

**The required field**

## SUBRESOURCES

### customerGroup

#### get_subresource

Get full information about the customer group tied to the current price list customer group fallback.

#### get_relationship

Retrieve the ID of the customer group tied to the current price list customer group fallback.
