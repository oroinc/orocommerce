# Oro\Bundle\ProductBundle\Entity\ProductVariantLink

## ACTIONS

### get

{@inheritdoc}

### get_list

{@inheritdoc}

### create

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

Update an existing product variable link record.

{@request:json_api}
Example:
  
```JSON
{
  "data": {
    "type": "productvariantlinks",
    "id": "1",
    "meta":{
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

{@inheritdoc}

### delete_list

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

Retrieve an ID of the configurable product

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

Retrieve an ID of the variant product

#### update_relationship

Update the product for a specific variant.

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
