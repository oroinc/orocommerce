# Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback

## ACTIONS

### get

Get details of the price list customer fallback by its ID.

{@inheritdoc}

### get_list

Get the collection of price list customer fallbacks. A collection may contain all fallbacks or may be filtered using standard <a href="https://www.oroinc.com/doc/orocommerce/current/dev-guide/integration#filters">filters</a>.

{@inheritdoc}

### create

Create a new price list customer fallback.

`fallback` value should be one of: `0` or `1`.

Fallback `0` maps to Customer Group fallback. Fallback `1`  maps to Current Customer Only fallback.

{@inheritdoc}

{@request:json_api}
Example:

`</api/pricelistcustomerfallbacks>`

```JSON
{
    "data": {
        "type": "pricelistcustomerfallbacks",
        "attributes": {
            "fallback": 1
        },
        "relationships": {
            "customer": {
                "data": {
                    "type":"customers",
                    "id": "1"
                }
            }
        }
    }
}
```
{@/request}

### update

Update details of the price list customer fallback identified by ID.

`fallback` value should be one of: `0` or `1`.

Fallback `0` maps to Customer Group fallback. Fallback `1`  maps to Current Customer Only fallback.

{@inheritdoc}

{@request:json_api}
Example:

`</api/pricelistcustomerfallbacks/1>`
 
```JSON
{
    "data": {
        "id": "1",
        "type": "pricelistcustomerfallbacks",
        "attributes": {
            "fallback": 0
        }
    }
}
```
{@/request}

### delete

Delete a price list customer fallback identified by ID.

{@inheritdoc}

### delete_list

Delete a collection of price list customer fallbacks. A collection may contain all fallbacks or may be filtered using standard <a href="https://www.oroinc.com/doc/orocommerce/current/dev-guide/integration#filters">filters</a>.

{@inheritdoc}

## FIELDS

### id

#### update

{@inheritdoc}

**The required field**

### fallback

Possible values: 1, 0. 0 - fallback to a customer group configuration. 1 - fallback to the current customer only.

#### create

{@inheritdoc}

**The required field**

### customer

The customer this fallback is tied to.

#### create

{@inheritdoc}

**The required field**

## SUBRESOURCES

### customer

#### get_subresource

Get full information about the customer tied to the current price list customer fallback.

#### get_relationship

Retrieve the ID of the customer tied to the current price list customer fallback.
