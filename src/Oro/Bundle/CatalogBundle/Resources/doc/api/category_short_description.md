# Oro\Bundle\CatalogBundle\Entity\CategoryShortDescription

## ACTIONS

### get

Retrieve a specific category short description record.

{@inheritdoc}

### get_list

Retrieve a collection of category short description records.

{@inheritdoc}

### create

Create a new category short description record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "categoryshortdescriptions",
    "attributes": {
      "fallback": null,
      "text": "Short Description"
    },
    "relationships": {
      "localization": {
        "data": {
          "type": "localizations",
          "id": "1"
        }
      },
      "category": {
        "data": {
          "type": "categories",
          "id": "1"
        }
      }
    }
  }
}
```
{@/request}

### update

Edit a specific category short description record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "categoryshortdescriptions",
    "id" : "1",
    "attributes": {
      "fallback": null,
      "text": "Short Description"
    },
    "relationships": {
      "localization": {
        "data": {
          "type": "localizations",
          "id": "1"
        }
      },
      "category": {
        "data": {
          "type": "categories",
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

Retrieve a record of localization assigned to a specific category short description record.

#### get_relationship

Retrieve ID of localization record assigned to a specific category short description record.

#### update_relationship

Replace localization assigned to a specific category short description record.

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

### category

#### get_subresource

Retrieve a record of category assigned to a specific category short description record.

#### get_relationship

Retrieve ID of category record assigned to a specific category short description record.

#### update_relationship

Replace category assigned to a specific category short description record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "categories",
    "id": "1"
  }
}
```
{@/request}
