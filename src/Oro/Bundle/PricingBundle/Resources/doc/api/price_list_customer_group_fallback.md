# Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback

## ACTIONS

### get

Retrieve a price list customer group fallback record.

{@inheritdoc}

### get_list

Retrieve a collection of price list customer group fallback records.

{@inheritdoc}

### create

Create a new price list customer group fallback.

`fallback` value should be one of: `0` or `1`.

Fallback `0` maps to Website fallback. Fallback `1`  maps to Current Customer Group Only fallback.

{@inheritdoc}

{@request:json_api}
Example:

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
                    "type":"customergroups",
                    "id": "1"
                }
            }
        }
    }
}
```
{@/request}

### update

Edit a specific price list customer group fallback record.

`fallback` value should be one of: `0` or `1`.

Fallback `0` maps to Website fallback. Fallback `1`  maps to Current Customer Group Only fallback.

{@inheritdoc}

{@request:json_api}
Example:

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

Delete a specific price list customer group fallback record.

{@inheritdoc}

### delete_list

Delete a collection of price list customer group fallback records.

{@inheritdoc}

## FIELDS

### id

#### update

{@inheritdoc}

**The required field.**

### fallback

Possible values: 1, 0. 0 - fallback to a website configuration. 1 - fallback to the current customer group only.

#### create

{@inheritdoc}

**The required field.**

### customerGroup

The customer group this fallback is tied to.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### website

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

## SUBRESOURCES

### customerGroup

#### get_subresource

Get full information about the customer group tied to the current price list customer group fallback.

#### get_relationship

Retrieve the ID of the customer group tied to the current price list customer group fallback.
