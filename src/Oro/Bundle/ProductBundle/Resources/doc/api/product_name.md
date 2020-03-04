# Oro\Bundle\ProductBundle\Entity\ProductName

## ACTIONS

### get

Retrieve a specific ProductName record.

{@inheritdoc}

### get_list

Retrieve a collection of ProductName records.

{@inheritdoc}

### create

Create a new ProductName record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productnames",
    "attributes": {
      "fallback": null,
      "string": "Name"
    },
    "relationships": {
      "localization": {
        "data": {
          "type":"localizations",
          "id":"1"
        }
      },
      "product": {
        "data": {
          "type":"products",
          "id":"1"
        }
      }
    }
  }
}
```
{@/request}

### update

Edit a specific ProductName record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productnames",
    "id" : "1",
    "attributes": {
      "fallback": null,
      "string": "Name"
    },
    "relationships": {
      "localization": {
        "data": {
          "type":"localizations",
          "id":"1"
        }
      },
      "product": {
        "data": {
          "type":"products",
          "id":"1"
        }
      }
    }
  }
}
```
{@/request}

## FIELDS

## SUBRESOURCES

### localization

#### get_subresource

Retrieve a record of localization assigned to a specific ProductName record.

#### get_relationship

Retrieve ID of localization record assigned to a specific ProductName record.

#### update_relationship

Replace localization assigned to a specific ProductName record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "localizations",
    "id": "1"
  }
}
```
{@/request}

### product

#### get_subresource

Retrieve a record of product assigned to a specific ProductName record.

#### get_relationship

Retrieve ID of product record assigned to a specific ProductName record.

#### update_relationship

Replace product assigned to a specific ProductName record.

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
