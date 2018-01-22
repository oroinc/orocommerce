Price Sharding
==============

In order to speed up queries, a price table can be split (sharded) into several smaller tables. 

Price sharding is disabled by default.To enable it, set the following parameter `enable_price_sharding: true` in the ... file.

To reorgonize storage, run the `oro:price-lists:pl-storage-reorganize` command and follow the instructions displayed once the command is run.

Queries
-------

In Oro applicatoins (e.g. in PriceManager), sharding is handled via ShardManager, since Doctrine cannot work with dynamic tables for one entity. All operations with the price data, such as persist and remove, should be done via PriceManager which uses ShardManager.

To make you query `sharding-aware` (and use a proper table), add the following hints:

* `$query->setHint('priceList', $priceList->getId());`
   This hint is necessary to use correct query cache. If this hint is absent, the query cache should be disabled.
* `$query->setHint(PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $shardManager);`
   This hint is used by PriceShardWalker to define the current table.
* `$query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);`
   This hint is used to update the final SQL.

**Notice** 
* Tables are divided by Price Lists, so one table contains prices only from one Price List. When prices from a different Price List are needed, we should use JOIN for each Price List.
* `HINT_CUSTOM_OUTPUT_WALKER` does not apply to delete queries. Delete queries must be executed via SQL, not DQL. You should manually manually replace the table names before running this query.

Insert Into Select Query
------------------------

To create a correct insert-into-select query, please use `InsertFromSelectShardQueryExecutor` that defines the table and executes insert into it.

PriceShardWalker
----------------

PriceShardWalker analyzes a query and tries to detect a proper table to use based on the query parameters.

Grids
-----

To apply PriceShardWalker to grids, use the `HINT_PRICE_SHARD` hint. Oro `QueryHintResolver` applies the required hints automatically.

    source:
        hints:
            - HINT_PRICE_SHARD
        count_hints:
            - HINT_PRICE_SHARD
