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
| productType | "simple" / "configurable" | Yes / No |



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
        "unitPrecisions": {
          "data": [
            {
              "type": "productunitprecisions",
              "id": "1"
            },
            {
              "type": "productunitprecisions",
              "id": "65"
            }
          ]
        },
        "primaryUnitPrecision": {
          "data": {
            "type": "productunitprecisions",
            "id": "1"
          }
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
            "attributes": {
              "fallback": "systemConfig",
              "scalarValue": null,
              "arrayValue": null
            }
          }
        },
        "inventoryThreshold": {
          "data": {
            "type": "entityfieldfallbackvalues",
            "attributes": {
              "fallback": null,
              "scalarValue": "31",
              "arrayValue": null
            }
          }
        },
        "minimumQuantityToOrder": {
          "data": {
            "type": "entityfieldfallbackvalues",
            "attributes": {
              "fallback": "systemConfig",
              "scalarValue": null,
              "arrayValue": null
            }
          }
        },
        "maximumQuantityToOrder": {
          "data": {
            "type": "entityfieldfallbackvalues",
            "attributes": {
              "fallback": null,
              "scalarValue": "12",
              "arrayValue": null
            }
          }
        },
        "decrementQuantity": {
          "data": {
            "type": "entityfieldfallbackvalues",
            "attributes": {
              "fallback": null,
              "scalarValue": "1",
              "arrayValue": null
            }
          }
        },
        "backOrder": {
          "data": {
            "type": "entityfieldfallbackvalues",
            "attributes": {
              "fallback": null,
              "scalarValue": "0",
              "arrayValue": null
            }
          }
        }
      }
    }
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
        "unitPrecisions": {
          "data": [
            {
              "type": "productunitprecisions",
              "id": "1"
            },
            {
              "type": "productunitprecisions",
              "id": "65"
            }
          ]
        },
        "primaryUnitPrecision": {
          "data": {
            "type": "productunitprecisions",
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
