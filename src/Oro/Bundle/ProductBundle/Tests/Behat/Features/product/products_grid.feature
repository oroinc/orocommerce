@regression
@fixture-OroProductBundle:products_grid.yml
@ticket-BAP-17648
@skip
#Issue should be resolved in BB-21070

Feature: Products Grid

  In order to ensure backoffice products grid works correctly
  As an administrator
  I check filters are working, sorting is working and columns config is working as designed.

  Scenario: Check SKU filter
    Given I login as administrator
    And I go to Products / Products
    When I filter SKU as Contains "PSKU2"
    Then I should see following grid:
      | SKU    |
      | PSKU20 |
      | PSKU2  |
    And records in grid should be 2
    And I reset SKU filter

  Scenario: Check Name filter
    Given records in grid should be 20
    When I filter Name as Contains "Product 2"
    Then I should see following grid:
      | Name       |
      | Product 20 |
      | Product 2  |
    And records in grid should be 2
    And I reset Name filter

  Scenario: Check Product Family filter
    Given records in grid should be 20
    When I choose "Product Attribute Family Custom" in the Product Family filter
    Then I should see following grid:
      | Name       |
      | Product 19 |
    And records in grid should be 1
    And I reset Product Family filter

  Scenario: Check Inventory Status filter
    Given records in grid should be 20
    When I choose filter for Inventory Status as Is Any Of "In Stock"
    Then I should not see "PSKU7"
    And I reset Inventory Status filter

  Scenario: Check Status filter
    Given records in grid should be 20
    When I check "Enabled" in "Status: All" filter strictly
    Then I should not see "PSKU6"
    And I reset "Status: Enabled" filter

  Scenario: Check Type filter
    Given records in grid should be 20
    When I check "Configurable" in "Type: All" filter strictly
    Then I should see following grid:
      | Name      |
      | Product 9 |
    And records in grid should be 1
    And I reset "Type: Configurable" filter

  Scenario: Enable & Check Created At filter
    Given records in grid should be 20
    And I show filter "Created At" in "Products Grid" grid
    When I filter Created At as between "now + 1" and "now + 2"
    Then there are no records in grid
    When I filter Created At as between "now - 1" and "now + 1"
    Then records in grid should be 20
    And I reset "Created At" filter

  Scenario: Check Updated At filter
    Given records in grid should be 20
    When I filter Updated At as between "now + 1" and "now + 2"
    Then there are no records in grid
    When I filter Updated At as between "now - 1" and "now + 1"
    Then records in grid should be 20
    And I reset "Updated At" filter

  Scenario: Enable & Check Tax Code filter
    Given records in grid should be 20
    And I show filter "Tax Code" in "Products Grid" grid
    When I check "ProductTaxCode2" in "Tax Code: All" filter strictly
    Then I should see following grid:
      | Name      |
      | Product 8 |
    And records in grid should be 1
    And I reset "Tax Code: productTaxCode2" filter

  Scenario: Enable & Check New Arrival filter
    Given records in grid should be 20
    And I show filter "New Arrival" in "Products Grid" grid
    When I check "Yes" in "New Arrival: All" filter strictly
    Then I should see following grid:
      | Name       |
      | Product 10 |
    And records in grid should be 1
    And I reset "New Arrival: Yes" filter

  Scenario: Check Price filter
    Given records in grid should be 20
    And I check "USD"
    When I filter "Price (USD)" as Equals "15"
    Then I should see following grid:
      | Name       |
      | Product 15 |
    And records in grid should be 1
    And I reset "Price (USD)" filter
    And I show filter "Price (USD/each)" in "Products Grid" grid
    When I filter "Price (USD/each)" as Equals "12"
    Then I should see following grid:
      | Name       |
      | Product 12 |
    And records in grid should be 1
    And I reset "Price (USD/each)" filter

  Scenario: Check Price Attribute filter
    Given records in grid should be 20
    When I filter "Price Attribute (USD)" as Equals "10"
    Then I should see following grid:
      | Name       |
      | Product 10 |
    And records in grid should be 1

  Scenario: Check Filter Applies After Different Actions
    Given I hide column Price Attribute (USD) in grid
    Then I should see following grid:
      | Name       |
      | Product 10 |
    And records in grid should be 1
    When I filter "Price Attribute (USD)" as Less Than "16"
    Then I should not see "Product 16"
    And records in grid should be 15
    When I select 10 from per page list dropdown
    Then records in grid should be 10
    And I should not see "Product 16"
    When I press next page button
    Then records in grid should be 5
    When I refresh "Products Grid" grid
    Then records in grid should be 5
    When I reload the page
    Then records in grid should be 5
    When I hide filter "Price Attribute (USD)" in "Products Grid" grid
    Then there is 20 records in grid
    When I reset "Products Grid" grid
    Then there is 20 records in grid
    And records in grid should be 20

  Scenario: Sort by SKU
    Given I should see following grid:
      | Name       |
      | Product 20 |
      | Product 19 |
    When I sort grid by "SKU"
    Then I should see following grid:
      | Name       |
      | Product 1  |
      | Product 10 |
    When I sort grid by "SKU" again
    Then I should see following grid:
      | Name      |
      | Product 9 |
      | Product 8 |
    And I reset "Products Grid" grid

  Scenario: Sort by Name
    Given I should see following grid:
      | Name       |
      | Product 20 |
      | Product 19 |
    When I sort grid by "Name"
    Then I should see following grid:
      | Name       |
      | Product 1  |
      | Product 10 |
    When I sort grid by "Name" again
    Then I should see following grid:
      | Name      |
      | Product 9 |
      | Product 8 |
    And I reset "Products Grid" grid

  Scenario: Sort by Product Family
    Given I should see following grid:
      | Name       |
      | Product 20 |
    When I sort grid by "Product Family"
    Then I should see following grid:
      | Name       |
      | Product 1  |
    When I sort grid by "Product Family" again
    Then I should see following grid:
      | Name      |
      | Product 19 |
    And I reset "Products Grid" grid

  Scenario: Sort by Status
    Given I should see following grid:
      | Name       |
      | Product 20 |
      | Product 19 |
    When I sort grid by "Status"
    Then I should see following grid:
      | Name      |
      | Product 6 |
      | Product 1 |
    When I sort grid by "Status" again
    Then I should see following grid:
      | Name       |
      | Product 20 |
      | Product 19 |
    And I reset "Products Grid" grid

  Scenario: Enable column "Created At" and Sort by it
    Given I should see following grid:
      | Name       |
      | Product 20 |
      | Product 19 |
    When I show column Created At in grid
    And I sort grid by "Created At"
    Then I should see following grid:
      | Name      |
      | Product 1 |
      | Product 2 |
    When I sort grid by "Created At" again
    Then I should see following grid:
      | Name       |
      | Product 20 |
      | Product 19 |
    And I reset "Products Grid" grid

  Scenario: Sort by Updated At
    Given I should see following grid:
      | Name       |
      | Product 20 |
      | Product 19 |
    When I sort grid by "Updated At"
    Then I should see following grid:
      | Name      |
      | Product 1 |
      | Product 2 |
    When I sort grid by "Updated At" again
    Then I should see following grid:
      | Name       |
      | Product 20 |
      | Product 19 |
    And I reset "Products Grid" grid

  Scenario: Sort by Price
    Given I should see following grid:
      | Name       |
      | Product 20 |
      | Product 19 |
    When I sort grid by "Price (USD)"
    Then I should see following grid:
      | Name       |
      | Product 1  |
      | Product 10 |
    When I sort grid by "Price (USD)" again
    Then I should see following grid:
      | Name      |
      | Product 9 |
      | Product 8 |
    And I show column Price (USD/each) in grid
    When I sort grid by "Price (USD/each)"
    Then I should see following grid:
      | Name       |
      | Product 1  |
      | Product 10 |
    When I sort grid by "Price (USD/each)" again
    Then I should see following grid:
      | Name      |
      | Product 9 |
      | Product 8 |
    And I reset "Products Grid" grid

  Scenario: Enable column "Tax Code" and Sort by it
    Given I should see following grid:
      | Name       |
      | Product 20 |
      | Product 19 |
    When I show column Tax Code in grid
    And I sort grid by "Tax Code"
    Then I should see following grid:
      | Name      |
      | Product 1 |
      | Product 2 |
    When I sort grid by "Tax Code" again
    Then I should see following grid:
      | Name       |
      | Product 8  |
      | Product 20 |
    And I reset "Products Grid" grid

  Scenario: Enable column "Type" and Sort by it
    Given I should see following grid:
      | Name       |
      | Product 20 |
      | Product 19 |
    And I show column Type in grid
    When I sort grid by "Type"
    Then I should see following grid:
      | Name      |
      | Product 9 |
      | Product 1 |
    When I sort grid by "Type" again
    Then I should see following grid:
      | Name       |
      | Product 20  |
      | Product 19 |
    And I reset "Products Grid" grid

  Scenario: Enable column "New Arrival" and Sort by it
    Given I should see following grid:
      | Name       |
      | Product 20 |
      | Product 19 |
    And I show column New Arrival in grid
    When I sort grid by "New Arrival"
    Then I should see following grid:
      | Name      |
      | Product 1 |
      | Product 2 |
    When I sort grid by "New Arrival" again
    Then I should see following grid:
      | Name       |
      | Product 10  |
      | Product 20 |
    And I reset "Products Grid" grid

  Scenario: Sort by Price Attribute
    Given I should see following grid:
      | Name       |
      | Product 20 |
      | Product 19 |
    When I sort grid by "Price Attribute (USD)"
    Then I should see following grid:
      | Name      |
      | Product 1 |
      | Product 10 |
    When I sort grid by "Price Attribute (USD)" again
    Then I should see following grid:
      | Name      |
      | Product 9 |
      | Product 8 |

  Scenario: Check Sorter Applies After Different Actions
    Given I hide column Price Attribute (USD) in grid
    Then I should see following grid:
      | Name      |
      | Product 9 |
      | Product 8 |
    When I select 10 from per page list dropdown
    Then records in grid should be 10
    And I should see following grid:
      | Name      |
      | Product 9 |
      | Product 8 |
    When I press next page button
    Then I should see following grid:
      | Name       |
      | Product 18 |
      | Product 17 |
    When I reload the page
    Then I should see following grid:
      | Name       |
      | Product 18 |
      | Product 17 |
    When I reset "Products Grid" grid
    Then there is 20 records in grid
    And records in grid should be 20

  Scenario: Check columns are loaded correctly
    Given I hide all columns in grid except SKU
    When I show column Name in grid
    Then I should see "Name" column in grid
    And I should see following grid with exact columns order:
      | SKU    | Name       |
      | PSKU20 | Product 20 |
    When I show column Price Attribute (USD) in grid
    Then I should see "Price Attribute (USD)" column in grid
    And I should see following grid with exact columns order:
      | SKU    | Name       | Price Attribute (USD) |
      | PSKU20 | Product 20 | Each $20.00           |
    When I show column Price (USD/each) in grid
    Then I should see "Price (USD/each)" column in grid
    And I should see following grid with exact columns order:
      | SKU    | Name       | Price Attribute (USD) | Price (USD/Each) |
      | PSKU20 | Product 20 | Each $20.00           | $20.00           |

  Scenario: Check Columns Config Applies After Different Actions
    When I select 10 from per page list dropdown
    Then records in grid should be 10
    And I should see following grid with exact columns order:
      | SKU    | Name       | Price Attribute (USD) | Price (USD/Each) |
      | PSKU20 | Product 20 | Each $20.00           | $20.00           |
    When I press next page button
    And I should see following grid with exact columns order:
      | SKU    | Name       | Price Attribute (USD) | Price (USD/Each) |
      | PSKU10 | Product 10 | Each $10.00           | $10.00           |
    When I reload the page
    And I should see following grid with exact columns order:
      | SKU    | Name       | Price Attribute (USD) | Price (USD/Each) |
      | PSKU10 | Product 10 | Each $10.00           | $10.00           |
    When I reset "Products Grid" grid
    Then I should see following grid with exact columns order:
      | SKU    | Image | Name       | Product Family           | Status  | Inventory Status |
      | PSKU20 |       | Product 20 | Product Attribute Family | Enabled | In Stock         |
