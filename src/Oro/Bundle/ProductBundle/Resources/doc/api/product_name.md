# Oro\Bundle\ProductBundle\Entity\ProductName

## ACTIONS

### get

Retrieve a specific product name record.

{@inheritdoc}

### get_list

Retrieve a collection of product name records.

{@inheritdoc}

### create

Create a new product name record.

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
          "type": "localizations",
          "id": "1"
        }
      },
      "product": {
        "data": {
          "type": "products",
          "id": "1"
        }
      }
    }
  }
}
```
{@/request}

### update

Edit a specific product name record.

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
          "type": "localizations",
          "id": "1"
        }
      },
      "product": {
        "data": {
          "type": "products",
          "id": "1"
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

Retrieve a record of localization assigned to a specific product name record.

#### get_relationship

Retrieve ID of localization record assigned to a specific product name record.

#### update_relationship

Replace localization assigned to a specific product name record.

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

Retrieve a record of product assigned to a specific product name record.

#### get_relationship

Retrieve ID of product record assigned to a specific product name record.

#### update_relationship

Replace product assigned to a specific product name record.

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
