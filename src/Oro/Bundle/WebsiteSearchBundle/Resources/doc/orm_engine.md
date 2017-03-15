ORM search engine
=================

ORM engine is a default search engine provided out of the box by WebsiteSearchBundle. This engine uses Doctrine ORM
entities as a storage, so all data is stored in a relational DBMS. ORM search index shares the same database
with an application. To perform fulltext search index uses DBMS fulltext search index.


### ORM data storage

ORM storage uses EAV (entity-attribute-value) model to store multiple fields of the same entity. There are five
ORM entities in the WebsiteSearchBundle to store search index information:

* `Oro\Bundle\WebsiteSearchBundle\Entity\Item` (table `oro_website_search_item`) - main entry point entity,
stores general information about an entity (name, ID, alias) and includes relations to all other search entities;
* `Oro\Bundle\WebsiteSearchBundle\Entity\IndexText` (table `oro_website_search_text`) - stores values of fields of the `text` type , contains fulltext search index;
* `Oro\Bundle\WebsiteSearchBundle\Entity\IndexInteger` (table `oro_website_search_integer`) - stores values of `integer` fields;
* `Oro\Bundle\WebsiteSearchBundle\Entity\IndexDecimal` (table `oro_website_search_decimal`) - stores values of `decimal` fields;
* `Oro\Bundle\WebsiteSearchBundle\Entity\IndexDatetime` (table `oro_website_search_datetime`) - stores values of `datatime` fields.

Each of these entities has its own table in the database. Four type-specific tables have relation to main entity
table. Entities from the WebsiteSearchBundle use separate entity manager -- `search` -- and, as a consequence, separate connection
to the database.

ORM engine supports two DBMSes - Mysql and PostgreSQL. Each of these DBMSese has it's own driver class that encapsulates
interaction and provides common interface to execute queries. Mysql driver is stored in
`Oro\Bundle\WebsiteSearchBundle\Engine\ORM\Driver\PdoMysql`, PostgreSQL driver is stored in
`Oro\Bundle\WebsiteSearchBundle\Engine\ORM\Driver\PdoPgsql` - both of these drivers extend similar drivers from
SearchBundle and use the same approach to work with search index.

The ORM engine has some advantages and disadvantages. One of the big advantages is that engine shares the same database with
an application, so it can be backed up with the main data, and there is no need to set up separate search engine like
Elasticsearch or Sphinx. With relational DBMSes, indexation happens faster.
On the other hand, the search via ORM fulltext search index is not that fast, especially if there are many enitites.
One more disadvantage is the usage of EAV. Though it is very flexible, the database query execution might be quite
heavy and memory consuming.

### ORM search

The ORM search engine is represented by the `Oro\Bundle\WebsiteSearchBundle\Engine\ORM\OrmEngine` class. This engine 
proxies a search call to the appropriate DBMS driver (Mysql ot PostgreSQL) and converts result to the required format.

Let's check an example of a simple query. Here is the text representation of a search query:

```
SELECT
    text.sku,
    text.name_LOCALIZATION_ID,
    text.short_description_LOCALIZATION_ID,
    text.type
FROM
    oro_product_WEBSITE_ID
WHERE
    text.all_text_LOCALIZATION_ID ~ "light"
LIMIT 25
```

For PostgreSQL DBMS, the SQL query looks like:

```
SELECT DISTINCT
    o0_.id AS id_0,
    o0_.entity AS entity_1,
    o0_.alias AS alias_2,
    o0_.record_id AS record_id_3,
    o0_.title AS title_4,
    o0_.changed AS changed_5,
    o0_.created_at AS created_at_6,
    o0_.updated_at AS updated_at_7,
    o1_.VALUE AS value_8,
    o2_.VALUE AS value_9,
    o3_.VALUE AS value_10,
    o4_.VALUE AS value_11,
    ts_rank(to_tsvector(o5_.VALUE) , to_tsquery('light:*')) AS sclr_12
FROM
    oro_website_search_item o0_
    LEFT JOIN
        oro_website_search_text o1_
        ON o0_.id = o1_.item_id
        AND (o1_.field = 'sku')
    LEFT JOIN
        oro_website_search_text o2_
        ON o0_.id = o2_.item_id
        AND (o2_.field = 'name_1')
    LEFT JOIN
        oro_website_search_text o3_
        ON o0_.id = o3_.item_id
        AND (o3_.field = 'short_description_1')
    LEFT JOIN
        oro_website_search_text o4_
        ON o0_.id = o4_.item_id
        AND (o4_.field = 'type')
    LEFT JOIN
        oro_website_search_text o5_
        ON o0_.id = o5_.item_id
        AND (o5_.field = 'all_text_1')
WHERE
    o0_.alias IN ('oro_product_1')
    AND
    (
        ((to_tsvector(o5_.VALUE) @@ to_tsquery ('light:*' )) = TRUE
        AND o5_.field = 'all_text_1'
        AND ts_rank(to_tsvector(o5_.VALUE) , to_tsquery('light:*')) > 1.0E-6)
    )
ORDER BY
    sclr_12 DESC LIMIT 25;
```

It's clearly visible that every new field adds new join to a query, and the actual search is performed using the
PostgreSQL fulltext search index.


### ORM indexation

An ORM indexer is represented by the `Oro\Bundle\WebsiteSearchBundle\Engine\ORM\OrmIndexer` class and
almost all method calls proxy methods from an appropriate ORM search driver.

The only interesting part in this indexer is alias renaming. When developer requests reindexation of some website
(or full reindexation), the data is not removed from ORM immediately. Instead, a new temporary alias is used to index new data.
Then, after all new data is persisted, the old data with its permanent alias is dropped and the temporary alias is renamed to the
permanent one. With this approach, search index always contains some data, and user is able to use search during the indexation.
