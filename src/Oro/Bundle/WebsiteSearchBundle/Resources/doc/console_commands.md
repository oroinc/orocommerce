Console commands
================

OroWebsiteSearchBundle provides commands to interact with search index.

oro:website-search:reindex
--------------------------

This command performs reindexation of entities to be included in the search index. It has optional parameters that allows reindexing specific type of entities and/or website.

Reindexation might take long time for a big volume of data, so it would be good idea to run it scheduled (e.g. once a day).

To reindex all entities, use the following command:
```
> php bin/console oro:website-search:reindex
Starting reindex task for all mapped entities
Total indexed items: 733
```

To reindex only a certain website and specific entity, use the --website-id and --class parameters:
```
> bin/console oro:website-search:reindex --website-id 1 --class OroUserBundle:User

```

Normally, reindexation is performed immediately after the reindex command
is issued. However, it can also be scheduled to be 
performed in the background by the Message Queue consumers.

Advantages of this mode:
* asynchronous
* can be multithreaded
* scalable

You will need a configured Message Queue and at least one running consumer worker to use this mode.

Please use the following parameter to run a scheduled, background indexation :
```
> bin/console oro:website-search:reindex --scheduled

```

This command won't directly run indexation - it will immediately quit, putting a reindex request to the Queue. The process itself will be performed in the background by the consumers.

In order to smoothly scale indexation of big volumes, we supplied another parameter - **product-id**, that controls the granulation of reindexation. 

You can specify a range of IDs of products to be reindexed, for example:

 ```
> bin/console oro:website-search:reindex --scheduled --product-id=1-5000 
```

The parameter also supports ID range splitting.

Let's assume we have a very large database of 5M products and want to distribute load nicely among a set of 32 message consumers. In order to do this, we could tell the reindexer to split the products between workers in 1000-product sets:
 
 ```
> bin/console oro:website-search:reindex --scheduled --product-id=1-5000000/1000 
```

This command will generate reindex requests with 1000 products per each, thus allowing to split the 5M product pool into 5000 * 1k chunks. This strategy might drastically improve reindexation performance, depending on the amount of available consumers.

If you don't know the exact amount of products in the database, you can use the **asterisk** instead:

 ```
> bin/console oro:website-search:reindex --scheduled --product-id=*/1000 
```
