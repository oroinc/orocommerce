# Oro\Bundle\PricingBundle\Entity\PriceList

## ACTIONS

### get

Retrieve a price list record.

{@inheritdoc}

### get_list

Retrieve a collection of price list records.

{@inheritdoc}

### create

Create a new price list. Use the **priceListCurrencies** field to set price list currencies as an array of strings.
Every string should match the existing currency code.

The created record is returned in the response.

{@inheritdoc}

**Note:**
The fields **createdAt**, **updatedAt**, **containSchedule** cannot be set via the API as their values are generated automatically.

{@request:json_api}
Example:

```JSON
{
    "data": {
        "id": "price_list",
        "type": "pricelists",
        "attributes": {
            "name": "some name",
            "priceListCurrencies": ["EUR", "USD"],
            "active": true,
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
            },
            "organization": {
                "data": {
                  "type": "organizations",
                  "id": "1"
                }
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

Edit a specific price list record.

The updated record is returned in the response.

{@inheritdoc}

**Notes:**

* The values for the fields **updatedAt**, **containSchedule** cannot are set via the API as their values are generated automatically.
* The fields **createdAt**, **priceRules**, **schedules** are not allowed to be updated.
To modify the relationship with price rules and/or price list schedules, delete the incorrect price list and create a new one including the correct price rules and price schedule.

{@request:json_api}
Example:

```JSON
{
    "data": {
        "id": "1",
        "type": "pricelists",
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

Delete a specific price list record.

{@inheritdoc}

### delete_list

Delete a collection of price list records.

{@inheritdoc}

## FIELDS

### name

The name of the price list.

#### create

{@inheritdoc}

**The required field.**

### priceListCurrencies

Array of currency codes.

#### create

{@inheritdoc}

**The required field.**

### containSchedule

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

## SUBRESOURCES

### priceRules

#### get_subresource

Get full information about the price list rules.

#### get_relationship

Retrieve the IDs of the price Rules configured for a specific price list.

#### delete_relationship

Remove the price rules for a specific price list.

### schedules

#### get_subresource

Get full information about the price list schedules.

#### get_relationship

Retrieve the IDs of the schedules configured for a specific price list.

#### delete_relationship

Remove the schedules for a specific price list.

### organization

#### get_subresource

Retrieve the record of the organization a specific price list belongs to.

#### get_relationship

Retrieve the ID of the organization that a specific price list belongs to.

#### update_relationship

Replace the organization that a specific price list belongs to.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "organizations",
    "id": "1"
  }
}
```
{@/request}
