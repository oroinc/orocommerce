# Oro\Bundle\PricingBundle\Entity\PriceListSchedule

## ACTIONS

### get

Get one PriceListSchedule entity

### get_list

Get a collection of PriceListSchedule entities

### create

Create a new PriceListSchedule entity. The `activeAt`, `deactivateAt` fields should not intersect with
values from other schedules for one price list.
Example:
```
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

### update

Update one PriceListSchedule entity. The `activeAt`, `deactivateAt` fields should not intersect with
values from other schedules for one price list. The `priceList` field is not allowed to be updated,
you should delete wrong schedule and create a new one.
Example:
 
```
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

### delete

Delete one PriceListSchedule entity

### delete_list

Delete a collection of PriceListSchedule entities

## FIELDS

### priceList

#### create

**The required field**

## SUBRESOURCES

### priceList

#### get_subresource

Get full information about the schedule's price list
