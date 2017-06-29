# Oro\Bundle\PricingBundle\Entity\PriceList

## ACTIONS

### get

Get one PriceList entity

{@inheritdoc}

### get_list

Get a collection of PriceList entities

{@inheritdoc}

### create

Create a new PriceList entity. The Fields `createdAt`, `updatedAt`, `containSchedule` are not configurable
in API and are set automatically. Use `priceListCurrencies` field to set price list currencies as 
an array of strings - currency codes.

{@inheritdoc}

{@request:json_api}
Example:

`</api/pricelists>`

```JSON
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
{@/request}

### update

Update one PriceList entity. The fields `createdAt`, `updatedAt`, `containSchedule` are not configurable
in API and are set automatically. The fields `priceRules`, `schedules` are not allowed to be updated,
to modify them you should delete wrong price list and create a new one.

{@inheritdoc}

{@request:json_api}
Example:

`</api/pricelists/1>`
 
```JSON
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
{@/request}

### delete

Delete one PriceList entity

{@inheritdoc}

### delete_list

Delete a collection of PriceList entities

{@inheritdoc}

## FIELDS

### id

#### update

{@inheritdoc}

**The required field**

### name

The name of PriceList entity

#### create

{@inheritdoc}

**The required field**

### priceListCurrencies

Array of currency codes

#### create

{@inheritdoc}

**The required field**


## SUBRESOURCES

### priceRules

#### get_subresource

Get full information about the price list rules

#### get_relationship

Retrieve the IDs of the price Rules configured for a specific price list

#### delete_relationship

Remove the price rules for a specific price list

### schedules

#### get_subresource

Get full information about the price list schedules

#### get_relationship

Retrieve the IDs of the schedules configured for a specific price list

#### delete_relationship

Remove the schedules for a specific price list
