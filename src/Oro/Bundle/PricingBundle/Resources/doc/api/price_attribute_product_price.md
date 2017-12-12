# Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice

## ACTIONS

### get

Get details of the product price attribute value by its ID.

{@inheritdoc}

### get_list

Get the collection of product price attribute value details. A collection may contain all values or may be filtered using standard <a href="https://www.oroinc.com/doc/orocommerce/current/dev-guide/integration#filters">filters</a>.

{@inheritdoc}

### create

Add a new product price attribute value.

{@inheritdoc}

{@request:json_api}
Example:

`</api/priceattributeproductprices>`

```JSON
{
    "data": {
        "type": "priceattributeproductprices",
        "attributes": {
    		"currency": "USD",
    		"value": "24.3"
    	},
    	"relationships": {
            "priceList": {
                "data": {
                    "type": "priceattributepricelists",
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
                    "id": "set"
                }
            }
        }
    }
}
```
{@/request}

### update

Update product price attribute value identified by ID.

{@inheritdoc}

{@request:json_api}
Example:

`</api/priceattributeproductprices/1>`

```JSON
{
    "data": {
        "id": "1",
        "type": "priceattributeproductprices",
        "attributes": {
    		"currency": "USD",
    		"value": "35"
    	},
    	"relationships": {
            "priceList": {
                "data": {
                    "type": "priceattributepricelists",
                    "id": "2"
                }
            },
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

Delete a product price attribute value identified by ID.

{@inheritdoc}

### delete_list

Delete a collection of product price attribute values. A collection may contain all values or may be filtered using standard <a href="https://www.oroinc.com/doc/orocommerce/current/dev-guide/integration#filters">filters</a>.

{@inheritdoc}

## FIELDS

### currency

#### create

{@inheritdoc}

**The required field**

### value

#### create

{@inheritdoc}

**The required field**

### priceList

#### create

{@inheritdoc}

**The required field**

### product

#### create

{@inheritdoc}

**The required field**

### unit

#### create

{@inheritdoc}

**The required field**


## SUBRESOURCES

### priceList

#### get_subresource

Get full information about the product price attribute related to a specific product price attribute value.

#### get_relationship

Retrieve the ID of the product price attribute configured for a specific product price attribute value.

#### update_relationship

Replace the product price attribute attached to a specific product price attribute value.

{@request:json_api}
Example:

`</api/priceattributeproductprices/1/relationships/priceList>`
```JSON
{
  "data": {
    "type": "priceattributepricelists",
    "id": "1"
  }
}
```
{@/request}

### product

#### get_subresource

Get full information about the product related to a specific product price attribute value.

#### get_relationship

Retrieve the ID of the product configured for a specific product price attribute value.

#### update_relationship

Replace the product attached to a specific product price attribute value.

{@request:json_api}
Example:

`</api/priceattributeproductprices/1/relationships/product>`
```JSON
{
  "data": {
    "type": "products",
    "id": "1"
  }
}
```
{@/request}

### unit

#### get_subresource

Get full information about the product unit related to a specific product price attribute value.

#### get_relationship

Retrieve the ID of the product unit configured for a specific product price attribute value.

#### update_relationship

Replace the product unit attached to a specific product price attribute value.

{@request:json_api}
Example:

`</api/priceattributeproductprices/1/relationships/unit>`
```JSON
{
  "data": {
    "type": "productunits",
    "id": "item"
  }
}
```
{@/request}
