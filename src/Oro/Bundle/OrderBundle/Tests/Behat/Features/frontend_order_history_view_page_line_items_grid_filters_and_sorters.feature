@regression
@fixture-OroOrderBundle:order_product_with_different_localization.yml

Feature: Frontend Order History view page line items grid filters and sorters

  Scenario: Check Grid
    When I signed in as AmandaRCole@example.org on the store frontend
    And I open Order History page on the store frontend
    And I click "View" on row "ORD#1" in grid "PastOrdersGrid"
    Then I should see "2 products"
    And I should see following "OrderLineItemsGrid" grid:
      | Product           | Quantity | Price  | Ship By  |
      | Product1 SKU: AA1 | 10 items | $5.00  | 1/1/2010 |
      | Product2 SKU: AA2 | 15 sets  | $25.00 | 1/1/2024 |
    And I shouldn't see "Actions" column in grid

  Scenario: Sorting grid by Product
    When sort grid by Product
    Then I should see that Product in 1 row is equal to "Product1 SKU: AA1"
    But when I sort grid by Product again
    Then I should see that Product in 1 row is equal to "Product2 SKU: AA2"

  Scenario: Sorting grid by Quantity
    When sort grid by Quantity
    Then I should see that Quantity in 1 row is equal to "10 items"
    But when I sort grid by Quantity again
    Then I should see that Quantity in 1 row is equal to "15 sets"

  Scenario: Sorting grid by Price
    When sort grid by Price
    Then I should see that Price in 1 row is equal to "$5.00"
    But when I sort grid by Price again
    Then I should see that Price in 1 row is equal to "$25.00"

  Scenario: Sorting grid by Ship By
    When sort grid by Ship By
    Then I should see that Ship By in 1 row is equal to "1/1/2010"
    But when I sort grid by Ship By again
    Then I should see that Ship By in 1 row is equal to "1/1/2024"

  Scenario: Check SKU filter
    When I set filter SKU as is equal to "AA1"
    Then I should see following "OrderLineItemsGrid" grid:
      | Product           | Quantity | Price | Ship By  |
      | Product1 SKU: AA1 | 10 items | $5.00 | 1/1/2010 |
    And I reset "SKU" filter in "Frontend Order Grid Filters" sidebar

  Scenario: Check Product Name filter
    When I set filter Product Name as contains "Product2"
    Then I should see following "OrderLineItemsGrid" grid:
      | Product           | Quantity | Price  | Ship By  |
      | Product2 SKU: AA2 | 15 sets  | $25.00 | 1/1/2024 |
    And I reset "Product Name" filter in "Frontend Order Grid Filters" sidebar

  Scenario: Check Quantity filter
    When I filter Quantity as is equal to "15"
    Then I should see following "OrderLineItemsGrid" grid:
      | Product           | Quantity | Price  | Ship By  |
      | Product2 SKU: AA2 | 15 sets  | $25.00 | 1/1/2024 |
    And I reset "Quantity" filter in "Frontend Order Grid Filters" sidebar

  Scenario: Check Price filter
    When I filter Price as is equal to "5"
    Then I should see following "OrderLineItemsGrid" grid:
      | Product           | Quantity | Price  | Ship By  |
      | Product1 SKU: AA1 | 10 items | $5.00  | 1/1/2010 |
    And I reset "Price" filter in "Frontend Order Grid Filters" sidebar

  Scenario: Check Product Unit filter
    When  I check "item" in Product Unit filter
    Then I should see following "OrderLineItemsGrid" grid:
      | Product           | Quantity | Price | Ship By  |
      | Product1 SKU: AA1 | 10 items | $5.00 | 1/1/2010 |
    And I click "Clear All Filters"

  Scenario: Check Ship By filter
    When I filter "Ship By" as between "2/2/2010" and "today-1"
    Then I should see following "OrderLineItemsGrid" grid:
      | Product           | Quantity | Price  | Ship By  |
      | Product2 SKU: AA2 | 15 sets  | $25.00 | 1/1/2024 |
