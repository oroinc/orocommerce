# Oro\Bundle\PricingBundle\Entity\PriceListSchedule

## ACTIONS

### get

Get a price list schedule details by the schedule ID.

{@inheritdoc}

### get_list

Get a collection of price list schedules. A collection may contain all price list schedules or may be filtered using the standard <a href="https://www.oroinc.com/doc/orocommerce/current/dev-guide/integration#filters">filters</a>.

{@inheritdoc}

### create

Create a new price list schedule. The period defined by the `activeAt` and `deactivateAt` values should not overlap with
periods defined by other schedules for the same price list.

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

Update a price list schedule identified by ID.
**Notes:**
* The period defined by the `activeAt` and `deactivateAt` values should not overlap with periods defined by other schedules for the same price list. 
* The `priceList` value is not allowed to be updated. To modify the relationship with the price list, delete the incorrect price list schedule and create a new one including the correct price list.

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

Delete a price list schedule identified by ID.

{@inheritdoc}

### delete_list

Delete a collection of price list schedules. A collection may contain all price list schedules or may be filtered using the standard <a href="https://www.oroinc.com/doc/orocommerce/current/dev-guide/integration#filters">filters</a>.

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

The price list this schedule is created for.

#### create

{@inheritdoc}

**The required field**

## SUBRESOURCES

### priceList

#### get_subresource

Get complete information about the price list this schedule was created for.

#### get_relationship

Get an ID of the price list this schedule was created for.
