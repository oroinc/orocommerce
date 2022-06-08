# Oro\Bundle\ProductBundle\Entity\Product

## FIELDS

### productShippingOptions

The product shipping options for the product.

## SUBRESOURCES

### productShippingOptions

#### get_subresource

Retrieve the records for the product shipping options of a specific product record.

#### get_relationship

Retrieve a list of IDs for the product shipping options of a specific product record.

#### add_relationship

Set the product shipping options of a specific product record.

{@request:json_api} Example:

```JSON
{
  "data": [
    {
      "type": "productshippingoptions",
      "id": "64"
    },
    {
      "type": "productshippingoptions",
      "id": "67"
    }
  ]
}
```

{@/request}

#### update_relationship

Replace the product shipping options for a specific product.

{@request:json_api} Example:

```JSON
{
  "data": [
    {
      "type": "productshippingoptions",
      "id": "64"
    },
    {
      "type": "productshippingoptions",
      "id": "67"
    }
  ]
}

```

{@/request}

#### delete_relationship

Remove the product shipping options of a specific product record.

{@request:json_api} Example:

```JSON
{
  "data": [
    {
      "type": "productshippingoptions",
      "id": "64"
    },
    {
      "type": "productshippingoptions",
      "id": "67"
    }
  ]
}

```

{@/request}
