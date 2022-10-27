# Oro\Bundle\PricingBundle\Entity\PriceListToCustomer

## ACTIONS

### get

Retrieve a price list to customer relation record.

{@inheritdoc}

### get_list

Retrieve a collection of price list to customer relation records.

{@inheritdoc}

### create

Create a new price list to customer relation.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
    "data": {
        "type": "pricelisttocustomers",
        "attributes": {
            "sortOrder": 1,
            "mergeAllowed": false
        },
        "relationships": {
            "priceList": {
                "data": {
                    "type": "pricelists",
                    "id": "2"
                }
            },
            "customer": {
                "data": {
                    "type": "customers",
                    "id": "2"
                }
            }
        }
    }
}
```
{@/request}

### update

Edit a specific price list to customer relation record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
    "data": {
        "id": "1",
        "type": "pricelisttocustomers",
        "attributes": {
            "sortOrder": 5,
            "mergeAllowed": true
        },
        "relationships": {
            "priceList": {
                "data": {
                    "type": "pricelists",
                    "id": "3"
                }
            }
        }
    }
}
```
{@/request}

### delete

Delete a specific price list to customer relation record.

{@inheritdoc}

### delete_list

Delete a collection of price list to customer relation records.

{@inheritdoc}

## FIELDS

### mergeAllowed

Whether to allow merging of the current price list to other price lists for the current customer.

### sortOrder

The priority of the current price list in a scope of the current customer.

#### create

{@inheritdoc}

**The required field.**

### customer

The customer this relation is tied to.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### priceList

The price list this relation is tied to.

#### create

{@inheritdoc}

**The required field.**

### website

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

## SUBRESOURCES

### customer

#### get_subresource

Get full information about the customer tied to the current price list to customer relation.

#### get_relationship

Retrieve the ID of the customer tied to the current price list to customer relation.

### priceList

#### get_subresource

Get full information about the price list tied to the current price list to customer relation.

#### get_relationship

Retrieve the ID of the price list tied to the current price list to customer relation.
