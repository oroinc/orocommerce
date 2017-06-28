# Oro\Bundle\PricingBundle\Entity\PriceList

## ACTIONS

### get

Get one PriceList entity

### get_list

Get a collection of PriceList entities

### create

Create a new PriceList entity. The Fields `createdAt`, `updatedAt`, `containSchedule` are not configurable
in API and are set automatically. Use `priceListCurrencies` field to set price list currencies as 
an array of strings - currency codes.
Example:

```
{
    "data": {
        "id": "price_list",
        "type":"pricelists",
        "attributes": {
            "name": "some name",
            "priceListCurrencies": ["EUR", "USD"],
            "active": true,
            "default": false,
            "productAssignmentRule": "product.category.id > 0"
        },
        "relationships": {  
            "priceRules": {  
                "data": [  
                    {  
                        "type": "pricerules",
                        "id": "price_rule"
                    }
                ]
            },
            "schedules": {
                "data": [
                    {"type": "pricelistschedules", "id": "schedule_1"},
                    {"type": "pricelistschedules", "id": "schedule_2"}
                ]
            }
        }
    },
    "included": [
        { 
            "type": "pricerules",
            "id": "price_rule",
            "attributes": { 
                "currency": "USD",
                "currencyExpression": "",
                "quantity": "2",
                "quantityExpression": "",
                "productUnitExpression": "product.msrp.unit",
                "ruleCondition": "product.msrp.quantity == 1",
                "rule": "product.msrp.value + 10",
                "priority": "1"
            },
            "relationships": {  
                "priceList": {  
                   "data": {  
                      "type": "pricelists",
                      "id": "price_list"
                   }
                }
            }
        },
        {
            "type": "pricelistschedules",
            "id": "schedule_1",
            "attributes": {
                "activeAt": "2017-06-15T17:20+01:00",
                "deactivateAt": "2017-06-15T19:20+01:00"
            },
            "relationships": {
                "priceList": {
                    "data": {
                        "type": "pricelists",
                        "id": "price_list"
                    }
                }
            }
        },
        {
            "type": "pricelistschedules",
            "id": "schedule_2",
            "attributes": {
                "activeAt": "2017-06-16T17:20+01:00",
                "deactivateAt": "2017-06-16T19:20+01:00"
            },
            "relationships": {
                "priceList": {
                    "data": {
                        "type": "pricelists",
                        "id": "price_list"
                    }
                }
            }
        }
    ]
}
```

### update

Update one PriceList entity. The Fields `createdAt`, `updatedAt`, `containSchedule` are not configurable
in API and are set automatically.
Example:
 
```
{
    "data": {
        "id": "1",
        "type":"pricelists",
        "attributes": {
            "name": "another name",
            "priceListCurrencies": ["EUR", "USD", "RUB"],
            "active": false
        }
    }
}
```

### delete

Delete one PriceList entity

### delete_list

Delete a collection of PriceList entities

## FIELDS

### name

The name of PriceList entity

#### create

**The required field**

### priceListCurrencies

Array of currency codes

#### create

**The required field**


## SUBRESOURCES

### priceRules

#### get_subresource

Get full information about the price list rules

### schedules

#### get_subresource

Get full information about the price list schedules
