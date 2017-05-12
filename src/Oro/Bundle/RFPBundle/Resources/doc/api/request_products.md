# Oro\Bundle\RFPBundle\Entity\RequestProduct

## ACTIONS

### create

Add a new RequestProduct to the existing Request. If you want to create a RequestProduct together with a related resources,
you can use the included section of a JSON request body. Please take a look at the following example:

```
{
  "data": {
    "type": "requestproducts",
    "id": "8da4d8e7-6b25-4c5c-8075-b510f7bbb84f",
    "attributes": {"comment": "Comment"},
    "relationships": {
      "request": {
        "data": {"type": "requests", "id": "1"}
      },
      "product": {
        "data": {"type": "products", "id": "1"}
      },
      "requestProductItems": {
        "data": [
          {
            "type": "requestproductitems",
            "id": "707dda0d-35f5-47b9-b2ce-a3e92b9fdee7"
          }
        ]
      }
    }
  },
  "included": [
    {
      "type": "requestproductitems",
      "id": "707dda0d-35f5-47b9-b2ce-a3e92b9fdee7",
      "attributes": {
        "quantity": 35,
        "value": "35.0000",
        "currency": "USD"
      },
      "relationships": {
        "productUnit": {
          "data": {"type": "productunits", "id": "set"}
        },
        "requestProduct": {
          "data": {"type": "requestproducts", "id": "8da4d8e7-6b25-4c5c-8075-b510f7bbb84f"}
        }
      }
    }
  ]
}
```
