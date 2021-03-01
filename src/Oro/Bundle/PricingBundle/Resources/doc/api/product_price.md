# Oro\Bundle\PricingBundle\Entity\ProductPrice

## ACTIONS

### get

Retrieve a product price record.

{@inheritdoc}

### get_list

Retrieve a collection of product price records.

{@inheritdoc}

**Note:** It is required to provide the **priceList** filter with request.

### create

Create a new product price record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
    "data": {
        "type": "productprices",
        "attributes": {
            "quantity": 24,
            "currency": "USD",
            "value": 126.78
        },
        "relationships": {
            "priceList": {
                "data": {
                    "type": "pricelists",
                    "id": "1"
                }
            },
            "product": {
                "data": {
                    "type": "products",
                    "id": "1"
                }
            },
            "unit": {
                "data": {
                    "type": "productunits",
                    "id": "item"
                }
            }
        }
    }
}
```
{@/request}

### update

Edit a specific product price record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
    "data": {
        "id": "6390cd7b-7c1b-11e7-bda0-080027fb53ad-1",
        "type": "productprices",
        "attributes": {
            "quantity": 10,
            "currency": "USD",
            "value": 120
        },
        "relationships": {
            "product": {
                "data": {
                    "type": "products",
                    "id": "2"
                }
            },
            "unit": {
                "data": {
                    "type": "productunits",
                    "id": "set"
                }
            }
        }
    }
}
```
{@/request}

### delete

Delete a specific product price record.

{@inheritdoc}

### delete_list

Delete a collection of product price records.

{@inheritdoc}

**Note:** It is required to provide the **priceList** filter with request.

## FIELDS

### currency

The product price currency.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### quantity

The product quantity.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### value

The product price.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### priceList

Price list related to a product price.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### product

The product of a product price.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### unit

The unit of a product.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**
