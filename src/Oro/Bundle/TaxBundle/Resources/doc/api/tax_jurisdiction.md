# Oro\Bundle\TaxBundle\Entity\TaxJurisdiction

## ACTIONS

### get

Retrieve a specific tax jurisdiction record.

{@inheritdoc}

### get_list

Retrieve a collection of tax jurisdiction records.

{@inheritdoc}

### create

Create a new tax jurisdiction record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "taxjurisdictions",
    "attributes": {
      "code": "SOME_TAX_JURISDICTION",
      "description": "Some tax jurisdiction description",
      "regionText": null,
      "zipCodes": [
        {"from": "90011", "to":  null},
        {"from": "90201", "to":  "90280"}
      ]
    },
    "relationships": {
      "country": {
        "data": {
          "type": "countries",
          "id": "US"
        }
      },
      "region": {
        "data": {
          "type": "regions",
          "id": "US-CA"
        }
      }
    }
  }
}
```
{@/request}

### update

Edit a specific tax jurisdiction record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "taxjurisdictions",
    "id": "1",
    "attributes": {
      "code": "SOME_TAX_JURISDICTION",
      "description": "Some tax jurisdiction description"
    },
    "relationships": {
      "country": {
        "data": {
          "type": "countries",
          "id": "US"
        }
      },
      "region": {
        "data": {
          "type": "regions",
          "id": "US-CA"
        }
      }
    }
  }
}
```
{@/request}

### delete

Delete a specific tax jurisdiction record.

{@inheritdoc}

### delete_list

Delete a collection of tax jurisdiction records.

{@inheritdoc}

## FIELDS

### country

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### zipCodes

A list of zip codes of the tax jurisdiction.

Each element of the array is an object with two properties, **from** and **to**.

The **from** property is a string containing a zip code (when **to** property is **null**) or the zip code range start.

The **to** property is a string containing the zip code range end.

Example of data: **\[{"from": "90011", "to": null}, {"from": "90201", "to": "90280"}\]**

## SUBRESOURCES

### country

#### get_subresource

Retrieve a record of the country associated with a specific tax jurisdiction record.

#### get_relationship

Retrieve the ID of the country associated with a specific tax jurisdiction record.

#### update_relationship

Replace the country associated with a specific tax jurisdiction record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "countries",
    "id": "US"
  }
}
```
{@/request}

### region

#### get_subresource

Retrieve a record of the region associated with a specific tax jurisdiction record.

#### get_relationship

Retrieve the ID of the region associated with a specific tax jurisdiction record.

#### update_relationship

Replace the region associated with a specific tax jurisdiction record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "regions",
    "id": "US-NY"
  }
}
```
{@/request}
