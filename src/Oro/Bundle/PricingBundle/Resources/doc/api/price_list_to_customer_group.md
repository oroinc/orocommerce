# Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup

## ACTIONS

### get

Get details of the price list to customer group relation by its ID.

{@inheritdoc}

### get_list

Get the collection of price list to customer group details. A collection may contain all relations or may be filtered using standard <a href="https://www.oroinc.com/doc/orocommerce/current/dev-guide/integration#filters">filters</a>.

{@inheritdoc}

### create

Create a new price list to customer group relation.

{@inheritdoc}

{@request:json_api}
Example:

`</api/pricelisttocustomergroups>`

```JSON
{
    "data": {
        "type": "pricelisttocustomergroups",
        "attributes": {
            "sortOrder": 1,
            "mergeAllowed": false
        },
        "relationships": {
            "priceList": {
                "data": {
                    "type":"pricelists",
                    "id": "1"
                }
            },
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

Update details of the price list to customer group relation identified by ID.

{@inheritdoc}

{@request:json_api}
Example:

`</api/pricelisttocustomergroups/1>`
 
```JSON
{
    "data": {
        "id": "1",
        "type": "pricelisttocustomergroups",
        "attributes": {
            "sortOrder": 5,
            "mergeAllowed": true
        },
        "relationships": {
            "priceList": {
                "data": {
                    "type":"pricelists",
                    "id": "1"
                }
            }
        }
    }
}
```
{@/request}

### delete

Delete a price list to customer group relation identified by ID.

{@inheritdoc}

### delete_list

Delete a collection of price list to customer group relations. A collection may contain all relations or may be filtered using standard <a href="https://www.oroinc.com/doc/orocommerce/current/dev-guide/integration#filters">filters</a>.

{@inheritdoc}

## FIELDS

### id

#### update

{@inheritdoc}

**The required field**

### mergeAllowed

Whether to allow merging of the current price list to other price lists for the current customer group.

### sortOrder

The priority of the current price list in a scope of the current customer group.

#### create

{@inheritdoc}

**The required field**

### customerGroup

The customer group this relation is tied to.

#### create

{@inheritdoc}

**The required field**

### priceList

The price list this relation is tied to.

#### create

{@inheritdoc}

**The required field**

## SUBRESOURCES

### customerGroup

#### get_subresource

Get full information about the customer group tied to the current price list to customer group relation.

#### get_relationship

Retrieve the ID of the customer group tied to the current price list to customer group relation.

### priceList

#### get_subresource

Get full information about the price list tied to the current price list to customer group relation.

#### get_relationship

Retrieve the ID of the price list tied to the current price list to customer group relation.
