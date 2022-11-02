# Oro\Bundle\ProductBundle\Entity\ProductVariantLink

## ACTIONS

### get

Retrieve a specific product variant link record.

{@inheritdoc}

### get_list

Retrieve a collection of product variant link records.

{@inheritdoc}

### create

Create a new product variant link record.

The created record is returned in the response.

{@inheritdoc}

When creating a variant link it need to be between a configurable product and a simple product within
the same attribute family which holds a custom attribute that is active on the configurable product.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productvariantlinks",
    "attributes": {
      "visible": true
    },
    "relationships": {
      "parentProduct": {
        "data": {
          "type": "products",
          "id": "configurable-product-id"
        }
      },
      "product": {
        "data": {
          "type": "products",
          "id": "simple-product-id"
        }
      }
    }
  }
}
```
 {@/request}

### update

Edit a specific product variant link record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productvariantlinks",
    "id": "1",
    "meta": {
      "update": true
    },
    "attributes": {
      "visible": true
    },
    "relationships": {
      "parentProduct": {
        "data": {
          "type": "products",
          "id": "configurable-product-id"
        }
      },
      "product": {
        "data": {
          "type": "products",
          "id": "simple-product-id"
        }
      }
    }
  }
}
```
 {@/request}

### delete

Delete a specific product variant link record.

{@inheritdoc}

### delete_list

Delete a collection of product variant link records.

{@inheritdoc}

## FIELDS

### parentProduct

The configurable product that has the variants.

### product

The product that will be the variant of a configurable product.

### visible

The visibility of the variant.

## SUBRESOURCES

### parentProduct

#### get_subresource

Retrieve the configurable product configured for a specific variant.

#### get_relationship

Retrieve the ID of the configurable product for a specific variant.

#### update_relationship

Replace the configurable product for a specific variant.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "products",
    "id": "configurable-product-id"
  }
}
```
{@/request}

### product

#### get_subresource

Retrieve the product that is set as a variant.

#### get_relationship

Retrieve the ID of the product that is set as a variant.

#### update_relationship

Replace the product for a specific variant.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "products",
    "id": "simple-product-id"
  }
}
```
{@/request}
