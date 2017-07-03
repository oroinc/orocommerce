# Oro\Bundle\ProductBundle\Entity\Product

## ACTIONS

### get

{@inheritdoc}

### get_list

{@inheritdoc}

### create

Create a new Product record.
The created record is returned in the response. [See product create](#post--admin-api-products)

##### 1. Static options for product attributes

| Attribute| Options | Description |
|----------|---------|-------------|
| decrementQuantity | 1 / 0 | Yes / No |
| manageInventory | 1 / 0 | On Order Submission / Defined by Workflow |
| backOrder | 1 / 0 | Yes / No |
| productType | "simple" / "configurable" | |
| status | "enabled" / "disabled" |  |
| featured | true / false |  |
| newArrival | true / false |  |
| | | |

##### 2. Creating related entities together with the Product entity:

When creating a Product entity there are certain relations or associations with other entities
which require by default that you specify their type and id so that they are loaded.

But if you need to create a new entity from a relation, you have the option to do so, but you must
use the **"included"** section. See documentation about this section [here](https://www.orocrm.com/documentation/current/book/data-api#create-and-update-related-resources-together-with-a-primary-api-resource)

For example, if we look at the manageInventory field, in the **"data"** section

      "manageInventory": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "1abcd"
        }
      }

we have a temporary ID (recomended to be a GUID, as the documentation says) "1abcd" which is
used to identify the data used for creating this entity, data taken from the **"included"** section :

    {
      "type": "entityfieldfallbackvalues",
      "id": "1abcd",
      "attributes": {
        "fallback": "systemConfig",
        "scalarValue": null,
        "arrayValue": null
      }
    }

The same applies to other relation entities like "localizedfallbackvalues" type, which are used for
properties like "names", "descriptions", "shortDescriptions", and also meta fields. You can see in
the detailed example for product creation.

##### 3. Using ProductPrecisionUnits:

A ProductPrecisionUnit is a relation between the product and unit of quantity and other details like
conversion rates. Also, there is the concept of the primary ProductPrecisionUnit which is actually the
default precision unit, and it is mandatory.

There are a few restrictions and situations that the API caller should know:

- not sending the **"primaryUnitPrecision"** will make the first item in the **"unitPrecisions"** list become
the primary unit precision

- when sending the "primaryUnitPrecision" you need to specify the unit code, but it is mandatory that
this unit code is found between the items of the **"unitPrecisions"**

{@request:json_api}

Example:

`</admin/api/products>`

```JSON
{
  "data":
  {
    "type": "products",
    "attributes": {
      "sku": "test-api-3",
      "status": "enabled",
      "variantFields": [],
      "productType": "simple",
      "featured": true,
      "newArrival": false
    },
    "relationships": {
      "owner": {
        "data": {
          "type": "businessunits",
          "id": "1"
        }
      },
      "organization": {
        "data": {
          "type": "organizations",
          "id": "1"
        }
      },
      "names": {
        "data": [
          {
            "type": "localizedfallbackvalues",
            "id": "names-1"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "names-2"
          }
        ]
      },
      "slugPrototypes": {
        "data": [
          {
            "type": "localizedfallbackvalues",
            "id": "slug-id-1"
          }
        ]
      },
      "taxCode": {
        "data": {
          "type": "producttaxcodes",
          "id": "2"
        }
      },
      "attributeFamily": {
        "data": {
          "type": "attributefamilies",
          "id": "1"
        }
      },
      "primaryUnitPrecision":{
        "unit_code": "set"
      },
      "unitPrecisions": {
        "data": [
          {
            "type": "productunitprecisions",
            "unit_code": "set",
            "unit_precision": "0",
            "conversion_rate": "2",
            "sell": "1"
          },
          {
            "type": "productunitprecisions",
            "unit_code": "each",
            "unit_precision": "0",
            "conversion_rate": "2",
            "sell": "1"
          },
          {
            "type": "productunitprecisions",
            "unit_code": "item",
            "unit_precision": "0",
            "conversion_rate": "2",
            "sell": "1"
          }
        ]
      },
      "inventory_status": {
        "data": {
          "type": "prodinventorystatuses",
          "id": "out_of_stock"
        }
      },
      "manageInventory": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "1abcd"
        }
      },
      "inventoryThreshold": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "2abcd"
        }
      },
      "minimumQuantityToOrder": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "3abcd"
        }
      },
      "maximumQuantityToOrder": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "4abcd"
        }
      },
      "decrementQuantity": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "5abcd"
        }
      },
      "backOrder": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "6abcd"
        }
      }
    }
  },
  "included":[
    {
      "type": "entityfieldfallbackvalues",
      "id": "1abcd",
      "attributes": {
        "fallback": "systemConfig",
        "scalarValue": null,
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "2abcd",
      "attributes": {
        "fallback": null,
        "scalarValue": "31",
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "3abcd",
      "attributes": {
        "fallback": "systemConfig",
        "scalarValue": null,
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "4abcd",
      "attributes": {
        "fallback": null,
        "scalarValue": "12",
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "5abcd",
      "attributes": {
        "fallback": null,
        "scalarValue": "1",
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "6abcd",
      "attributes": {
        "fallback": null,
        "scalarValue": "0",
        "arrayValue": null
      }
    },
    {
      "type": "localizedfallbackvalues",
      "id": "names-1",
      "attributes": {
        "fallback": null,
        "string": "Test product",
        "text": null
      },
      "relationships": {
        "localization": {
          "data": null
        }
      }
    },
    {
      "type": "localizedfallbackvalues",
      "id": "names-2",
      "attributes": {
        "fallback": null,
        "string": "Product in spanish",
        "text": null
      },
      "relationships": {
        "localization": {
          "data": {
            "type": "localizations",
            "id": "6"
          }
        }
      }
    },
    {
      "type": "localizedfallbackvalues",
      "id": "slug-id-1",
      "attributes": {
        "fallback": null,
        "string": "test-prod-slug",
        "text": null
      },
      "relationships": {
        "localization": {
          "data": null
        }
      }
    }
  ]
}
```
{@/request}

### update

Edit a specific Product record. [See product create](#post--admin-api-products) documentation for examples
and explanations.

Other details

##### 1. Using ProductPrecisionUnits:

Besides what it is mentioned in the create Product section above, for the ProductPrecisionUnits
there are a few more restrictions and situations that the API caller should know:

- inside an item of the **"unitPrecisions"** list, if we have the "id" field, the only mandatory
field remaining mandatory is the "unit_code"
the primary unit precision

- if we send in the list of **"unitPrecisions"** a new "unit_code" that is not found among the
list of unit precisions on the Product main entity, then it will be created and added to the list

- if in the **"primaryUnitPrecision"** we send a new "unit_code", other then what it is found on
the current product primary unit precision, then the current primary unit precision will be replaced
with the newly provided one


##### 2. Updating "localizedfallbackvalues" (localized fields) and "entityfieldfallbackvalues" (options with fallbacks) types


{@request:json_api}
Example:

`</admin/api/products/12>`

```JSON
{
  "data":
  {
    "type": "products",
    "id": "68",
    "attributes": {
      "sku": "test-api-31",
      "status": "enabled",
      "variantFields": [],
      "productType": "simple",
      "featured": true,
      "newArrival": false
    },
    "relationships": {
      "owner": {
        "data": {
          "type": "businessunits",
          "id": "1"
        }
      },
      "organization": {
        "data": {
          "type": "organizations",
          "id": "1"
        }
      },
      "taxCode": {
        "data": {
          "type": "producttaxcodes",
          "id": "2"
        }
      },
      "attributeFamily": {
        "data": {
          "type": "attributefamilies",
          "id": "1"
        }
      },
      "inventory_status": {
        "data": {
          "type": "prodinventorystatuses",
          "id": "out_of_stock"
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

### sku

#### create

{@inheritdoc}

**Required field**

### decrementQuantity

#### create

{@inheritdoc}

**Required field**

### category

#### create

{@inheritdoc}

Specify the category of the product