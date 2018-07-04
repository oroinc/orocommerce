# Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback

## ACTIONS

### get

Retrieve a price list customer fallback record.

{@inheritdoc}

### get_list

Retrieve a collection of price list customer fallback records.

{@inheritdoc}

### create

Create a new price list customer fallback.

`fallback` value should be one of: `0` or `1`.

Fallback `0` maps to Customer Group fallback. Fallback `1`  maps to Current Customer Only fallback.

{@inheritdoc}

{@request:json_api}
Example:

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

Edit a specific price list customer fallback record.

`fallback` value should be one of: `0` or `1`.

Fallback `0` maps to Customer Group fallback. Fallback `1`  maps to Current Customer Only fallback.

{@inheritdoc}

{@request:json_api}
Example:

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

Delete a specific price list customer fallback record.

{@inheritdoc}

### delete_list

Delete a collection of price list customer fallback records.

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
