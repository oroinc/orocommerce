Product API
===========

Table of Contents
-----------------
 - [GET Products](#get-products)
 - [PATCH Products](#patch-products)

GET Products
============

Returns a collection of **Products**.
This API can be used to retrieve Inventory-Statuses.
One or more Product SKU can be provided in order to filter the received data.

Resource URL
------------
`{web_backend_prefix}/api/products/`

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
### sku
Product SKU(s).
One ore more Product SKU can be provided as request query.
E.g.: `filter[sku]=0RT28` or `filter[sku]=0RT28,1AB92`

Authentication Requirements
---------------------------
WSSE authentication is required.
ACL permission to view projects is required.

Example Request
---------------
http://demo.orocommerce.com/admin/api/products?filter[sku]=0RT28

Example Response
----------------
```json
{
  "data": [
    {
      "type": "products",
      "id": "1",
      "attributes": {
        "sku": "0RT28",
        "hasVariants": false,
        "status": "enabled",
        "variantFields": [],
        "createdAt": "2016-06-27T10:44:48Z",
        "updatedAt": "2016-06-27T10:44:51Z"
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
        "primaryUnitPrecision": {
          "data": {
            "type": "productunitprecisions",
            "id": "1"
          }
        },
        "inventory_status": {
          "data": {
            "type": "prodinventorystatuses",
            "id": "in_stock"
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
        }
      }
    }
  ]
}
```

PATCH Products
==============

Updates a single **Product**.
This API can be used to update the Inventory-Status of a Product.
One Product SKU must be provided in order to identify the Product.

Resource URL
------------
`{web_backend_prefix}/api/products/{sku}`

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
Must be set to `products`.
E.g.: `"type": "products"`

### sku
Product SKU.
One Product SKU must be provided in order to identify the Product.
The key for the Product SKU is `id` since this is the identifier of the Product.
E.g.: `"id": "0RT28"`

### inventory_status
Product Inventory Status.
One Inventory Status can be provided, in order to update the Product Inventory Status.
Since Inventory Status is a separate entity from Product, it has to be provided in the relationships section.
E.g.:
```json
"relationships": {
  "inventory_status": {
    "data": {
      "type": "prodinventorystatuses",
      "id": "out_of_stock"
    }
  }
}
```

Authentication Requirements
---------------------------
WSSE authentication is required.
ACL permission to update projects is required.

Example Request
---------------
http://demo.orocommerce.com/admin/api/products/0RT28

Example Request Body
--------------------
```json
{
  "data": {
    "type": "products",
    "id": "0RT28",
    "relationships": {
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

Example Response
----------------
```json
{
  "data": {
    "type": "products",
    "id": "1",
    "attributes": {
      "sku": "0RT28",
      "hasVariants": false,
      "status": "enabled",
      "variantFields": [],
      "createdAt": "2016-06-27T10:44:48Z",
      "updatedAt": "2016-07-20T12:18:37Z"
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
      }
    }
  }
}
```
