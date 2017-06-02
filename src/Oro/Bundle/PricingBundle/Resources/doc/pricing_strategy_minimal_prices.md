Minimal Prices Strategy
=======================

Minimal Prices Strategy picks the minimum price from the given sequence of Price Lists.


| Product | Price List         | Price       | 
|---------|--------------------|-------------|
| SKU1    | Default PriceList  | 9$ / 1 item |
| SKU1    | Default PriceList  | 8$ / 2 item |
| SKU1    | Default PriceList  | 6$ / 4 item |
| SKU1    | Custom PriceList   | 8$ / 1 item |
| SKU1    | Custom PriceList   | 7$ / 2 item |
| SKU1    | Custom PriceList   | 7$ / 4 item |

Result:

| Product |  Price      | 
|---------|-------------|
| SKU1    | 8$ / 1 item |
| SKU1    | 7$ / 2 item |
| SKU1    | 6$ / 4 item |
