# Oro\Bundle\PricingBundle\Entity\ProductPrice

## ACTIONS

### get

Get details of the product price by its ID. <br />

### get_list

Get the collection of ProductPrice details. A collection may contain all prices or may be filtered using standard <a href="https://www.oroinc.com/doc/orocommerce/current/dev-guide/integration#filters">filters</a>. <br />

**Note:** It is required to provide priceList filter with request.

### create

Create a new product price.

{@request:json_api}
Example:

`</api/productprices>`

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
                "data":{
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

Update product price identified by ID. <br />

{@request:json_api}
Example:

`</api/productprices/6390cd7b-7c1b-11e7-bda0-080027fb53ad-1>`

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

Delete a product price identified by ID. <br />

### delete_list

Delete a collection of product prices. A collection may contain all prices or may be filtered using standard <a href="https://www.oroinc.com/doc/orocommerce/current/dev-guide/integration#filters">filters</a>.

**Note:** It is required to provide priceList filter with request.

## FIELDS

### currency

The product price currency

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**Please note:**
*This field is **required** and must remain defined.*

### quantity

The product quantity

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**Please note:**
*This field is **required** and must remain defined.*

### value

The product price

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**Please note:**
*This field is **required** and must remain defined.*

### priceList

Price list related to a product price

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**Please note:**
*This field is **required** and must remain defined.*

### product

The product of a product price

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**Please note:**
*This field is **required** and must remain defined.*

### unit

The unit of a product

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**Please note:**
*This field is **required** and must remain defined.*
