# Oro\Bundle\ProductBundle\Api\Model\ProductSearch

## ACTIONS

### get_list

High-performance search queries based on indexed products information.

#### **searchQuery** filter

This filter is used to specify the search query.

The simple query consists of a field, followed by an operator, followed by one or more values surrounded by parentheses.
For example:

```
sku = "SKU1"
```

This query will find a product with SKU equals `SKU1`. It uses the **sku** field, the `=` (EQUALS) operator,
and the `SKU1` value.

```
sku in ("SKU1", "SKU2")
```

This query will find products with SKU equals `SKU1` or `SKU2`. It uses the **sku** field, the `in` operator,
and the `SKU1` and `SKU2` values.

A more complex query might look like this:

```
name ~ "headlamp" and minimalPrice < 10
```

This query will find all products that contain the `headlamp` word in name, and which minimal price is less than 10.
It uses two simple expressions, `name ~ "headlamp"` and `minimalPrice < 10`, that are joined by the logical `and` operator.

The parentheses in complex queries can be used to enforce the precedence of operators. For example:

```
(newArrival = true or minimalPrice < 10) and allText ~ "headlamp"
```

**Notes:**

* the space symbol must delimit an operator from a field and a value.
* a string value that contains a space symbol must be enclosed by double quotes (`"`).
* for boolean values use `0` for `false` and `1` for `true`.
* a datetime value must be enclosed by double quotes (`"`) and formatted as `YYYY-MM-DD hh:mm:ss`, where:

    - `YYYY` - four-digit year
    - `MM` - two-digit month (01=January, etc.)
    - `DD` - two-digit day of month (01 through 31)
    - `hh` - two digits of hour (00 through 23)
    - `mm` - two digits of minute (00 through 59)
    - `ss` - two digits of second (00 through 59)

<br />
Keywords:

| Keyword | Description |
|---------|-------------|
| `and` | Logical AND. Used to combine multiple clauses allowing you to refine your search. |
| `or` | Logical OR. Used to combine multiple clauses allowing you to expand your search. Also see `in` operator which can be a more convenient way to search for multiple values of a field. |

<br />
Common operators:

| Operator | Description |
|----------|-------------|
| `=` (EQUALS) | The value of the specified field exactly matches the specified value. |
| `!=` (NOT EQUALS) | The value of the specified field does not match the specified value. |
| `in` (IN) | The value of the specified field is one of multiple specified values. The values are specified as a comma-delimited list surrounded by parentheses. The expression `field in (1, 2)` is equal to `field = 1 or field = 2`. |
| `!in` (NOT IN) | The value of the specified field is not one of multiple specified values. The values are specified as a comma-delimited list surrounded by parentheses. The expression `field !in (1, 2)` is equal to `field != 1 and field != 2`. |
| `exists` (EXISTS) | The specified field exists for a product. An example: `field exists`. |
| `notexists` (NOT EXISTS) | The specified field does not exist for a product. An example: `field notexists`. |

<br />
Operators for string values:

| Operator | Description |
|----------|-------------|
| `~` (CONTAINS) | The value of the specified field does "fuzzy" match the specified value. |
| `!~` (NOT CONTAINS) | The value of the specified field does not "fuzzy" match for the specified value. |
| `like` (LIKE) | The value of the specified field contains the specified substring in any position. |
| `notlike` (NOT LIKE) | The value of the specified field does not contain the specified substring in any position. |
| `starts_with` (STARTS WITH) | The value of the specified field starts with the specified substring. |

<br />
Operators for numeric and date values:

| Operator | Description |
|----------|-------------|
| `>` (GREATER THAN) | The value of the specified field is greater than the specified value. |
| `>=` (GREATER THAN OR EQUALS) | The value of the specified field is greater than or equal to the specified value. |
| `<` (LESS THAN) | The value of the specified field is less than the specified value. |
| `<=` (LESS THAN OR EQUALS) | The value of the specified field is less than or equal to the specified value. |

<br />
The list of fields that can be used in the search query:

**allText**, **id**, **sku**, **skuUppercase**, **name**, **shortDescription**, **productType**, **isVariant**, **newArrival**,
**inventoryStatus**, **minimalPrice**, **minimalPrice_{unit}**, **orderedAt**, **product**, **productFamily**, **category**.

Also, any filterable product attribute can be used.

The **allText** is a particular field that can be used to do an overall full-text search. The value of this field usually
contains values of all text fields.

The **minimalPrice_{unit}** means that **{unit}** can be replaced with any
[product unit](https://doc.oroinc.com/user/back-office/products/products/product-units/).
E.g., to specify the minimal price for the `set` product unit, the field name will be **minimalPrice_set**.

#### **aggregations** filter

This filter is used to request aggregated data.

This filter should contain comma delimited definitions of aggregations.
The definition of each aggregation can be `fieldName aggregatingFunction`
or `fieldName aggregatingFunction resultName`. An example is 
`minimalPrice sum sumOfMinimalPrices,productType count`.

If `resultName` is not specified, it is built automatically as `fieldName` + `aggregatingFunction` with
uppercased first character, e.g., the result name for `productType count` will be `productTypeCount`.

The list of fields for which the aggregated data can be requested:

**id**, **sku**, **skuUppercase**, **name**, **shortDescription**, **productType**, **isVariant**, **newArrival**,
**inventoryStatus**, **minimalPrice**, **minimalPrice_{unit}**, **orderedAt**, **product**, **productFamily**, **category**.

Also, any filterable product attribute can be used.

Aggregating functions:

| Function | Description |
|----------|-------------|
| `count` | Counts the number of values that are extracted from the search index. |
| `sum` | Sums up numeric values that are extracted from the search index. |
| `avg` | Computes the average of numeric values that are extracted from the search index. |
| `min` | Returns the minimum value among numeric values that are extracted from the search index. |
| `max` | Returns the maximum value among the numeric values that are extracted from the search index. |

<br />
The aggregated data is returned in the **aggregatedData** field of **meta** section of the response.

The response for the `count` aggregating function is an array. Each element of this array is an object with
2 properties, **value** and **count**.
The **value** property contains a value for which the count is calculated.
The **count** property contains the number of occurrences of the value in the search result.

The response for other aggregating functions is a number.

An example:

```JSON
{
    "meta": {
        "aggregatedData": {
            "minimalPriceSum": 123.45,
            "productTypeCount": [
                { "value": "simple", "count": 10 },
                { "value": "configurable", "count": 5 }
            ]
        }
    }
}
```

#### **sort** filter

This filter is used to sort the result data.

The list of fields that can be used in the **sort** filter:

**relevance**, **id**, **sku**, **skuUppercase**, **name**, **productType**, **isVariant**, **newArrival**,
**inventoryStatus**, **minimalPrice**, **orderedAt**.

Also, any sortable product attribute can be used.

## FIELDS

### sku

The code of the product.

### name

The localized name of the product.

### shortDescription

The localized short description of the product.

### productType

Defines the type of product, "simple" / "configurable" / "kit".

### isVariant

Specifies if the product is a variation of a configurable product.

### newArrival

Specifies if the product is a new arrival.

### inventoryStatus

The status of the inventory of the product, in stock if there is a minimum quantity that can be ordered.

### unitPrecisions

An array of precisions for each product unit selected for the product.

Each element of the array is an object with the following properties:

**unit** is a string that contains the ID of the product unit.

**precision** is a number of digits after the decimal point for the number of products that a customer
can order or add to the shopping list.

**default** is a boolean that indicates whether this unit is default or not for the product.

Example of data: **\[{"unit": "item", "default": true}, {"unit": "set", "default": false}\]**

### images

An array of product images.

Each element of the array is an object with the following properties:

**url** is a string that contains URL of the image.

**type** is a string that contains the type of the image. Possible values of the image types are `medium` and `large`.

Example of data: **\[{"url": "/path/to/image.jpeg", "type": "medium"}, {"url": "/path/to/image.jpeg", "type": "large"}\]**

### product

The product related to the search record.

### productFamily

The product attribute family which defines attributes that can be used by products of a similar type.

## FILTERS

### searchQuery

The filter that is used to specify the search query.

### aggregations

The filter that is used to request aggregated data.
