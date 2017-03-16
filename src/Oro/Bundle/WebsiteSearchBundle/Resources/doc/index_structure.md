Search index structure
======================


### Indexed data

Indexed data is stored by entities, i.e. each indexed entity has its own set of related field data in a "key > value"
format. There are no field restrictions, entities of the same type might contain various fields.

The following data types are supported in search: `text`, `integer`, `decimal` and `datetime`. One of these types is assigned to every mapped field. This speeds up the search based on the type of the field.

For a global search by the entity, special fields of `all_text_*` type store concatenated text value (e.g. name, description and SEO data). More than one special field may exist for the entity in the index, depending on the placeholders used. Typically, `all_text_*` fields contain localized values; there is a field for every enabled localization and the global field `all_text` that contains data for all localizations.


### Plain data structure

Entity data is stored as plain data in "key > value" format. This format is used as the most simple to store and
support so that any engine could use the data. Custom search engines and implementations might use more
advanced structure, but they are not compatible with other engines.

However, almost any complex structure may be converted to the plain data with minor effort. For example, let's convert product prices data to plain data.

Let's assume that price numeric value is defined for a combination of the price list, currency, and unit. We're going to use the following data:

```json
{
    1: {
        USD: { item: 12.00, box: 100.00 },
        EUR: { item: 10.00, box: 80.00 }
    },
    2: {
        USD: { box: 100.00, container: 1500.00 },
        EUR: { box: 80.00, container: 1200.00 }
    },
    3: {
        USD: { container: 1400.00 },
        EUR: { container: 1100.00 }
    }
}
```

Where the root nodes define the price lists with IDs `1`, `2` and `3`, the currencies price list supports are nested inside the price list node (`USD` and `EUR`), and, finally, the prices are provided for supported product units ( `item`, `box` and `container`). Basically, denormalized, the data might look like:

```json
{
    price_1_USD_item: 12.00,
    price_1_USD_box: 100.00,
    price_1_EUR_item: 10.00,
    price_1_EUR_box: 80.00,
    price_2_USD_box: 100.00,
    price_2_USD_container: 1500.00,
    price_2_EUR_box: 80.00,
    price_2_EUR_container: 1200.00,
    price_3_USD_container: 1400.00,
    price_3_EUR_container: 1100.00
}
```

Assuming that the price list and currency is fixed for the current session, this structure helps filter by the price defined for a specific unit. To simplify and speed up sorting, we add new *maximum* and *minimum value* fields for each combination of price list and currency. The minimum value is used for sorting in ascending order, and the maximum value is used for descending sorting. Note: You may choose to use minimum value only for both ascending and descending sorting, if you are going for consistent and predictable sorting behavior. 

So, after the new fields are added, the sample data becomes the following:

```json
{
    price_1_USD_item: 12.00,
    price_1_USD_box: 100.00,
    price_1_EUR_item: 10.00,
    price_1_EUR_box: 80.00,
    price_2_USD_box: 100.00,
    price_2_USD_container: 1500.00,
    price_2_EUR_box: 80.00,
    price_2_EUR_container: 1200.00,
    price_3_USD_container: 1400.00,
    price_3_EUR_container: 1100.00,
    min_price_1_USD: 12.00,
    max_price_1_USD: 100.00,
    min_price_1_EUR: 10.00,
    max_price_1_EUR: 80.00,
    min_price_2_USD: 100.00,
    max_price_2_USD: 1500.00,
    min_price_2_EUR: 80.00,
    max_price_2_EUR: 1200.00,
    min_price_3_USD: 1400.00,
    max_price_3_USD: 1400.00,
    min_price_3_EUR: 1100.00,
    max_price_3_EUR: 1100.00
}
```

Now, you can sort the data using the `ORDER BY min_price_PRICE_LIST_ID_CURRENCY ASC` and
`ORDER BY max_price_PRICE_LIST_ID_CURRENCY DESC` in the query to get the products sorted by the minimum price in the provided currency (e.g. EUR) listed in the specified price list (e.g. 1). Note: PRICE_LIST_ID and CURRENCY are placeholders and should remain so. Website search substitutes them with actual values from the scope.

Furthermore, to ensure that there is at least one product price in the specific currency (in any price list), you can add a special field (flag) to indicate that the price in this currency exists:

```json
{
    price_1_USD_item: 12.00,
    price_1_USD_box: 100.00,
    price_1_EUR_item: 10.00,
    price_1_EUR_box: 80.00,
    price_2_USD_box: 100.00,
    price_2_USD_container: 1500.00,
    price_2_EUR_box: 80.00,
    price_2_EUR_container: 1200.00,
    price_3_USD_container: 1400.00,
    price_3_EUR_container: 1100.00,
    price_currency_USD: 1,
    price_currency_EUR: 1
}
```
If you add the flag parameter to indicate that the product price in the CURRENCY is available, you can use `WHERE price_currency_CURRENCY EXISTS` in your query. Note: CURRENCY is a placeholder and should remain so. Website search substitutes it with an actual value from the scope.               

Alternatively, if your implementation adds the boolean flag as a required parameter indicating whether the price is present (1) or absent (0), use the following bit in your query: 

`WHERE price_currency_CURRENCY = 1`


### Website scope

Website search support indexing multiple websites, as every website is an autonomous selling tool with its own search index.

Throughout the bundle, the `WEBSITE_ID` placeholder (e.g. in the entity alias) helps build unique alias name and set unique storage scope for each website. For example, an alias for product entity may be `oro_product_WEBSITE_ID`. During the reindexation, each product is saved in several scopes (one per website), and the scopes are named  `oro_product_1`, `oro_product_2`, etc.

Using this approach, the website search engine automatically gets information about the website the search request came for and knows how to reindex product for all websites when necessary. 

The `WEBSITE_ID` placeholder is automatically substituted with a website current customer uses. During the reindexation, by default, you can specify entity-related data and it will be automatically put into all website scopes. However, you can also set specific data for every website. The method is described in the following section.


### Localized data

Website scope is quite useful when you need to isolate scopes of data for every website, but sometimes you need several variants of the same field (e.g. localized values, like product name and description when several languages are enabled for the website).

Website search bundle provides several parametrized values out-of-the-box:

* `LOCALIZATION_ID` - a special placeholder for multiple localization support in a scope of a website. Similar to the `WEBSITE_ID`, the `LOCALIZATION_ID` helps you store several values for the same entity and simplify using them.

* `all_text_LOCALIZATION_ID` - a placeholder for a concatenated information related to the entity per localization. Each entity has dedicated fields that store these data (e.g. `all_text_1`, `all_text_2`).

Every new localized field increases the size of the search index and slows down the reindexation. For large volumes of information, real-time updates might be processed with a significant delay. To optimize the process, it is recommended to review the search index data and remove the fields that are not used. Also, moving search index to a separate storage on a separate server might be a good idea.


### Examples

Following is the product entity mapping configuration for OroCommerce deployment with one website, one localization, and one currency:

```yaml
Oro\Bundle\ProductBundle\Entity\Product:
    alias: oro_product
    fields:
        -
            name: sku
            type: text
        -
            name: name
            type: text
        -
            name: price
            type: decimal
        -
            name: all_text
            type: text
```

As you can see, no placeholders are used and the search index contains the following information:

**oro_product**

```json
{
    1: {
        sku: "PR1",
        name: "First product",
        price: 12.00,
        all_text: "PR1 First product"
    },
    2: {
        sku: "PR2",
        name: "Second product",
        price: 25.00,
        all_text: "PR2 Second product"
    }
}
```

Query for this index is quite simple:

```
SELECT
    text.sku,
    text.name,
    decimal.price
FROM
    oro_product
WHERE
    text.all_text ~ product
ORDER_BY
    decimal.price ASC
```

Now let's look at the OroCommerce deployment with two websites, two localizations, and three currencies:

* Global website (`WEBSITE_ID=1`) supports two localizations (English `LOCALIZATION_ID=1` and Russian `LOCALIZATION_ID=2`) and two currencies (`EUR`
and `GBP`);
* Russian website (`WEBSITE_ID=2`) supports one localization (Russian `LOCALIZATION_ID=2`) and one currency (`RUR`).

Use placeholders `WEBSITE_ID`, `LOCALIZATION_ID` and `CURRENCY`, like in the mapping configuration of product entity below:

```yaml
Oro\Bundle\ProductBundle\Entity\Product:
    alias: oro_product_WEBSITE_ID
    fields:
        -
            name: sku
            type: text
        -
            name: name_LOCALIZATION_ID
            type: text
        -
            name: price_CURRENCY
            type: decimal
        -
            name: all_text_LOCALIZATION_ID
            type: text
        -
            name: all_text
            type: text
```

Based on this configuration, the data may the following:

**oro_product_1**

```json
{
    1: {
        sku: "PR1",
        name_1: "First product",
        name_2: "Первый продукт",
        price_EUR: 12.00,
        price_GBP: 9.00,
        all_text_1: "PR1 First product",
        all_text_2: "PR1 Первый продукт",
        all_text: "PR1 First product Первый продукт"
    },
    2: {
        sku: "PR2",
        name_1: "Second product",
        name_2: "Второй продукт",
        price_EUR: 25.00,
        price_GBP: 20.00,
        all_text_1: "PR2 Second product",
        all_text_2: "PR2 Второй продукт",
        all_text: "PR2 Second product Второй продукт"
    }
}
```

**oro_product_2**

```json
{
    1: {
        sku: "PR1",
        name_2: "Первый продукт",
        price_RUR: 100.00,
        all_text_2: "PR1 Первый продукт",
        all_text: "PR1 Первый продукт"
    },
    2: {
        sku: "PR2",
        name_2: "Второй продукт",
        price_RUR: 200.00,
        all_text_2: "PR2 Второй продукт",
        all_text: "PR2 Второй продукт"
    }
}
```

The following query is automatically modified to substitute placeholders with the appropriate parameters for the current customer based on the scope:

```
SELECT
    text.sku,
    text.name_LOCALIZATION_ID AS name,
    decimal.price_CURRENCY AS price
FROM
    oro_product_WEBSITE_ID
WHERE
    text.all_text_LOCALIZATION_ID ~ продукт
ORDER_BY
    decimal.price_CURRENCY ASC
```
