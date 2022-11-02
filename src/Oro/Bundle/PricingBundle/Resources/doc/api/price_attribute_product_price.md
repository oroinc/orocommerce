# Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice

## ACTIONS

### get

Retrieve a product price attribute value record.

{@inheritdoc}

### get_list

Retrieve a collection of product price attribute value records.

{@inheritdoc}

### create

Create a new product price attribute value record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

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

Edit a specific product price attribute value record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

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

Delete a specific product price attribute value record.

{@inheritdoc}

### delete_list

Delete a collection of product price attribute value records.

{@inheritdoc}

## FIELDS

### currency

#### create

{@inheritdoc}

**The required field.**

### value

#### create

{@inheritdoc}

**The required field.**

### priceList

#### create

{@inheritdoc}

**The required field.**

### product

#### create

{@inheritdoc}

**The required field.**

### unit

#### create

{@inheritdoc}

**The required field.**

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

```JSON
{
  "data": {
    "type": "productunits",
    "id": "item"
  }
}
```
{@/request}
