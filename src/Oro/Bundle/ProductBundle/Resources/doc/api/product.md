# Oro\Bundle\ProductBundle\Entity\Product

## ACTIONS

### get

{@inheritdoc}

### get_list

{@inheritdoc}

### create

Create a new Product record.
The created record is returned in the response.

Static options for product attributes

| Attribute| Options | Description |
|----------|---------|-------------|
| decrementQuantity | 1 / 0 | Yes / No |
| manageInventory | 1 / 0 | On Order Submission / Defined by Workflow |
| backOrder | 1 / 0 | Yes / No |
| productType | "simple" / "configurable" | |
| status | "enabled" / "disabled" |  |
| featured | true / false |  |
| newArrival | true / false |  |

{@inheritdoc}


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
    }
  ]
}
```
{@/request}

### update

Edit a specific Product record.

{@inheritdoc}

{@request:json_api}
Example:

`</admin/api/products/12>`

```JSON
{
  "data":
    {
      "type": "products",
      "attributes": {
        "sku": "test-api",
        "status": "enabled",
        "variantFields": [],
        "createdAt": "2017-06-13T07:12:06Z",
        "updatedAt": "2017-06-13T07:12:31Z",
        "productType": "simple",
        "featured": true
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
        "primaryUnitPrecision":{
          "unit_code": "set"
        },
        "unitPrecisions": {
          "data": [
            {
              "type": "productunitprecisions",
              "unit_code": "each"
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
