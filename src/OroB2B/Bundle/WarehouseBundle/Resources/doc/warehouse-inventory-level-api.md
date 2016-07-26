Warehouse Inventory Level API
=============================

Table of Contents
-----------------
 - [GET Warehouse Inventory Levels](#get-warehouse-inventory-levels)
 - [PATCH Warehouse Inventory Level](#patch-warehouse-inventory-level)

GET Warehouse Inventory Levels
==============================

Returns a collection of **Warehouse Inventory Levels**.

This API can be used to retrieve Warehouse Inventory Level quantities.

One or more Product SKUs can be provided in order to filter the received data.

One or more Warehouse ids can be provided in order to filter the received data.

One or more Product Unit codes can be provided in order to filter the received data.

Resource URL
------------
`{web_backend_prefix}/api/warehouseinventorylevels/`

Request Headers
---------------
``Content-Type:  application/vnd.api+json``

Do not forget to add the request headers for authentication.

Resource Information
--------------------
- Response formats - JSON (default), HTML, XML, etc
- Requires authentication? - Yes

Parameters
----------
### product.sku
Product SKU(s).

One or more Product SKUs can be provided as request query.

E.g.: `filter[product.sku]=0RT28` or `filter[product.sku]=0RT28,1AB92`

### productUnitPrecision.unit.code
ProductUnit code(s).

One or more ProductUnit codes can be provided as request query.

E.g.: `filter[productUnitPrecision.unit.code]=item` or `filter[productUnitPrecision.unit.code]=item,set`

### warehouse
Warehouse ID(s).

One or more Warehouse ids can be provided as request query.

E.g.: `filter[warehouse]=1` or `filter[warehouse]=1,2`

Authentication Requirements
---------------------------
WSSE authentication is required.

ACL permission to view Warehouse Inventory Levels is required.

Example Request
---------------
http://demo.orocommerce.com/admin/api/warehouseinventorylevels?filter[product.sku]=0RT28,1AB92&filter[productUnitPrecision.unit.code]=item&filter[warehouse]=1,2

Example Response
----------------
```json
{
  "data": [
    {
      "type": "warehouseinventorylevels",
      "id": "1",
      "attributes": {
        "quantity": "49.0000000000",
        "productSku": "0RT28",
        "unit": "item"
      },
      "relationships": {
        "warehouse": {
          "data": {
            "type": "warehouses",
            "id": "1"
          }
        },
        "product": {
          "data": {
            "type": "products",
            "id": "1"
          }
        },
        "productUnitPrecision": {
          "data": {
            "type": "productunitprecisions",
            "id": "1"
          }
        }
      }
    },
    {
      "type": "warehouseinventorylevels",
      "id": "2",
      "attributes": {
        "quantity": "79.0000000000",
        "productSku": "1AB92",
        "unit": "item"
      },
      "relationships": {
        "warehouse": {
          "data": {
            "type": "warehouses",
            "id": "1"
          }
        },
        "product": {
          "data": {
            "type": "products",
            "id": "2"
          }
        },
        "productUnitPrecision": {
          "data": {
            "type": "productunitprecisions",
            "id": "2"
          }
        }
      }
    }
  ],
  "included": [
    {
      "type": "productunitprecisions",
      "id": "1",
      "attributes": {
        "unit": {
          "defaultPrecision": 0
        }
      }
    },
    {
      "type": "productunitprecisions",
      "id": "2",
      "attributes": {
        "unit": {
          "defaultPrecision": 0
        }
      }
    }
  ]
}
```

PATCH Warehouse Inventory Level
===============================
Updates a single **Warehouse Inventory Level**.

This API can be used to update the Warehouse Inventory Level quantity.

One Product SKU must be provided in order to identify the Warehouse Inventory Level of the Product.

One Warehouse ID must be provided if there are multiple Warehouses in the system.

One Product Unit code can be provided. The default Product Unit is the one of the primary Product Unit Precision of the Product. 

Warehouse Inventory Level quantity must be provided in order to update it.

Resource URL
------------
`{web_backend_prefix}/api/warehouseinventorylevels/{sku}`

Request Headers
---------------
``Content-Type:  application/vnd.api+json``

Do not forget to add the request headers for authentication.

Resource Information
--------------------
- Response formats - JSON (default), HTML, XML, etc
- Requires authentication? - Yes

Parameters
----------
### type
Type of the resource.

Must be set to `warehouseinventorylevels`.

E.g.: `"type": "warehouseinventorylevels"`

### sku
Product SKU.

One Product SKU must be provided in order to identify the Warehouse Inventory Level of the Product.

The key for the Product SKU is `id` since this is the primary identifier of the Warehouse Inventory Level.

E.g.: `"id": "0RT28"`

### warehouse
Warehouse ID.

One Warehouse ID must be provided if there are multiple Warehouses in the system.

Warehouse ID is provided in the `attributes` section.

E.g.:
```json
"attributes": {
  "warehouse": "1"
}
```

### unit
Product Unit code.

One Product Unit code can be provided. The default Product Unit is the one of the primary Product Unit Precision of the Product.

Product Unit code is provided in the `attributes` section.

E.g.:
```json
"attributes": {
  "unit": "item"
}
```

### quantity
Warehouse Inventory Level quantity.

Warehouse Inventory Level quantity must be provided in order to update it.

Warehouse Inventory Level quantity is provided in the `attributes` section.

E.g.:
```json
"attributes": {
  "quantity": "17"
}
```

Authentication Requirements
---------------------------
WSSE authentication is required.

ACL permission to update products is required.

Example Request
---------------
http://demo.orocommerce.com/admin/api/warehouseinventorylevels/0RT28

Example Request Body
--------------------
```json
{
  "data": {
    "type": "warehouseinventorylevels",
    "id": "0RT28",
    "attributes": {
      "quantity": "17",
      "warehouse": "1",
      "unit": "item"
    }
  }
}
```

Example Response
----------------
```json
{
  "data": {
    "type": "warehouseinventorylevels",
    "id": "1",
    "attributes": {
      "quantity": 17,
      "productSku": "0RT28",
      "unit": "item"
    },
    "relationships": {
      "warehouse": {
        "data": {
          "type": "warehouses",
          "id": "1"
        }
      },
      "product": {
        "data": {
          "type": "products",
          "id": "1"
        }
      },
      "productUnitPrecision": {
        "data": {
          "type": "productunitprecisions",
          "id": "1"
        }
      }
    }
  },
  "included": [
    {
      "type": "productunitprecisions",
      "id": "1",
      "attributes": {
        "unit": {
          "defaultPrecision": 0
        }
      }
    }
  ]
}
```
