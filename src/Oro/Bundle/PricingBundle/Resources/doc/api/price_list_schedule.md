# Oro\Bundle\PricingBundle\Entity\PriceListSchedule

## ACTIONS

### get

Retrieve a specific price list schedule record.

{@inheritdoc}

### get_list

Retrieve a collection of price list schedule records.

{@inheritdoc}

### create

Create a new price list schedule. The period defined by the `activeAt` and `deactivateAt` values should not overlap with
periods defined by other schedules for the same price list.

{@inheritdoc}

{@request:json_api}
Example:

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

Update a price list schedule record.

**Notes:**
* The period defined by the `activeAt` and `deactivateAt` values should not overlap with periods defined by other schedules for the same price list. 
* The `priceList` value is not allowed to be updated. To modify the relationship with the price list, delete the incorrect price list schedule and create a new one including the correct price list.

{@inheritdoc}

{@request:json_api}
Example:

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

Delete a specific price list schedule record.

{@inheritdoc}

### delete_list

Delete a collection of price list schedule records.

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
