Merge By Priority Strategy
==========================

Merge By Priority Strategy picks the first price from the given sequence of the Price Lists for an empty slot.

The `Merge Allowed` flag shows whether a particular Price List is allowed to be merged with the other prices. When Merge Allowed is disabled for a price list, its price will be exclusively used for the product that has no price provided in other price lists with higher priority.
Example1:

| Product | Price List         | Price       | Merge Allowed|
|---------|--------------------|-------------|--------------|
| SKU1    | Default PriceList  | 9$ / 1 item | true         |
| SKU1    | Default PriceList  | 8$ / 2 item | true         |
| SKU1    | Default PriceList  | 6$ / 5 item | true         |
|--|--|--|--
| SKU1    | Custom PriceList   | 8$ / 1 item | true         |
| SKU1    | Custom PriceList   | 7$ / 2 item | true         |
| SKU1    | Custom PriceList   | 7$ / 4 item | true         |

Result:

| Product |  Price      | 
|---------|-------------|
| SKU1    | 9$ / 1 item |
| SKU1    | 8$ / 2 item |
| SKU1    | 7$ / 4 item |
| SKU1    | 6$ / 5 item |

Example2:

| Product | Price List         | Price       | Merge Allowed|
|---------|--------------------|-------------|--------------|
| SKU1    | Default PriceList  | 9$ / 1 item | false        |
| SKU1    | Default PriceList  | 8$ / 2 item | false        |
| SKU1    | Default PriceList  | 6$ / 5 item | false        |
|--|--|--|--
| SKU1    | Custom PriceList   | 8$ / 1 item | true         |
| SKU1    | Custom PriceList   | 7$ / 2 item | true         |
| SKU1    | Custom PriceList   | 7$ / 4 item | true         |

Result:

| Product |  Price      | 
|---------|-------------|
| SKU1    | 9$ / 1 item |
| SKU1    | 8$ / 2 item |
| SKU1    | 6$ / 5 item |

Example3:

| Product | Price List         | Price         | Merge Allowed|
|---------|--------------------|---------------|--------------|
| SKU1    | Default PriceList  | 9$ / 1 item   | true         |
| SKU1    | Default PriceList  | 8$ / 2 item   | true         |
| SKU1    | Default PriceList  | 6$ / 5 item   | true         |
|--|--|--|--
| SKU1    | Custom PriceList   | 8$ / 1 item   | false        |
| SKU1    | Custom PriceList   | 7$ / 2 item   | false        |
| SKU1    | Custom PriceList   | 7$ / 4 item   | false        |
|--|--|--|--
| SKU1    | Custom2 PriceList  | 5$ / 10 item  | true         |
| SKU1    | Custom2 PriceList  | 4$ / 100 item | true         |

Result:

| Product |  Price        | 
|---------|---------------|
| SKU1    | 9$ / 1 item   |
| SKU1    | 8$ / 2 item   |
| SKU1    | 6$ / 5 item   |
| SKU1    | 5$ / 10 item  |
| SKU1    | 4$ / 100 item |
