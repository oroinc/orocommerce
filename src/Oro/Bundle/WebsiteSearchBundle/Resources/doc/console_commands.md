Console commands
================

OroWebsiteSearchBundle provides commands to interact with search index.

oro:website-search:reindex
--------------------------

This command performs reindexation of entities. It has two optional arguments that allows to reindex only entities of specified type or website.

Reindexation might take long for big volume of data, so it would be good idea to run it on schedule (e.g. once a day).

To reindex all entities, use the following command:
```
> php app/console oro:website-search:reindex
Starting reindex task for all mapped entities
Total indexed items: 733
```

To reindex only a certain website and specific entity, use the --website-id and --class parameters:
```
> app/console oro:website-search:reindex --website-id 1 --class OroUserBundle:User

```
