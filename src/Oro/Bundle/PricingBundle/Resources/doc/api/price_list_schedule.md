# Oro\Bundle\PricingBundle\Entity\PriceListSchedule

## ACTIONS

### get

Get one PriceListSchedule entity

{@inheritdoc}

### get_list

Get a collection of PriceListSchedule entities

{@inheritdoc}

### create

Create a new PriceListSchedule entity. The `activeAt`, `deactivateAt` fields should not intersect with
values from other schedules for one price list.

{@inheritdoc}

{@request:json_api}
Example:

`</api/pricelistschedules>`

```JSON
{
    "data": {
        "type": "pricelistschedules",
        "attributes": {
            "activeAt": "2017-06-15T17:20+01:00",
            "deactivateAt": "2017-06-15T19:20+01:00"
        },
        "relationships": {
            "priceList": {
                "data": {
                    "type": "pricelists",
                    "id": "1"
                }
            }
        }
    }
}
```
{@/request}

### update

Update one PriceListSchedule entity. The `activeAt`, `deactivateAt` fields should not intersect with
values from other schedules for one price list. The `priceList` field is not allowed to be updated,
you should delete wrong schedule and create a new one.

{@inheritdoc}

{@request:json_api}
Example:

`</api/pricelistschedules/1>`
 
```JSON
{
    "data": {
        "id": "1",
        "type": "pricelistschedules",
        "attributes": {
            "deactivateAt": "2017-07-15T19:20+01:00"
        }
    }
}
```
{@/request}

### delete

Delete one PriceListSchedule entity

{@inheritdoc}

### delete_list

Delete a collection of PriceListSchedule entities

{@inheritdoc}

## FIELDS

### id

#### update

{@inheritdoc}

**The required field**

### activeAt

Defines time when price list should be activated

### deactivateAt

Defines time when price list should be deactivated

### priceList

Schedule's price list

#### create

{@inheritdoc}

**The required field**

## SUBRESOURCES

### priceList

#### get_subresource

Get full information about the schedule's price list

#### get_relationship

Get price list ID for a specific schedule
