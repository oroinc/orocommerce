# Oro\Bundle\PricingBundle\Entity\PriceListToCustomer

## ACTIONS

### get

Get details of the price list to customer relation by its ID.

{@inheritdoc}

### get_list

Get the collection of price list to customer details. A collection may contain all relations or may be filtered using standard <a href="https://www.oroinc.com/doc/orocommerce/current/dev-guide/integration#filters">filters</a>.

{@inheritdoc}

### create

Create a new price list to customer relation.

{@inheritdoc}

{@request:json_api}
Example:

`</api/pricelisttocustomers>`

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
                    "type":"pricelists",
                    "id": "2"
                }
            },
            "customer": {
                "data": {
                    "type":"customers",
                    "id": "2"
                }
            }
        }
    }
}
```
{@/request}

### update

Update details of the price list to customer relation identified by ID.

{@inheritdoc}

{@request:json_api}
Example:

`</api/pricelisttocustomers/1>`
 
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
                    "type":"pricelists",
                    "id": "3"
                }
            }
        }
    }
}
```
{@/request}

### delete

Delete a price list to customer relation identified by ID.

{@inheritdoc}

### delete_list

Delete a collection of price list to customer relations. A collection may contain all relations or may be filtered using standard <a href="https://www.oroinc.com/doc/orocommerce/current/dev-guide/integration#filters">filters</a>.

{@inheritdoc}

## FIELDS

### id

#### update

{@inheritdoc}

**The required field**

### mergeAllowed

Whether to allow merging of the current price list to other price lists for the current customer.

### sortOrder

The priority of the current price list in a scope of the current customer.

#### create

{@inheritdoc}

**The required field**

### customer

The customer this relation is tied to.

#### create

{@inheritdoc}

**The required field**

### priceList

The price list this relation is tied to.

#### create

{@inheritdoc}

**The required field**

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
