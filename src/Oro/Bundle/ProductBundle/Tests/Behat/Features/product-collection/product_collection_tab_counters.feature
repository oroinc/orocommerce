@regression
@fixture-OroProductBundle:product_collections_individual_products.yml
Feature: Product collection tab counters
  In order to know how many products are in hidden grids
  As an Administrator
  I want to have ability to see products counters in tabs

  Scenario: All counters are equal to zero for newly added Product Collection variant
    Given I login as administrator
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Show Variants Dropdown"
    And I click "Add Product Collection"
    And I click "Content Variants"
    Then I should see 1 elements "Product Collection Variant Label"
    Then I should see an "Product Collection Preview Grid" element
    And I should see "There are no products"
    And I should see 0 for "All Added" counter
    And I should see 0 for "Excluded" counter
    And I should see 0 for "Manually Added" counter

  Scenario: All Added counter is updated when filter condition changes
    When I click "Content Variants"
    And I click on "Advanced Filter"
    And I drag and drop "Field Condition" on "Drop condition here"
    And I click "Choose a field.."
    And I click on "SKU"
    And type "PSKU1" in "value"
    And I click on "Preview Results"
    Then I should see following "Active Grid" grid:
      | SKU    | NAME       |
      | PSKU1  | Product 1  |
      | PSKU10 | Product 10 |
      | PSKU11 | Product 11 |
      | PSKU12 | Product 12 |
    And I should see 4 for "All Added" counter
    And I should see 0 for "Excluded" counter
    And I should see 0 for "Manually Added" counter

  Scenario: Counters in All Added and Manual tabs are updated after products added manually
    When I click "Manually Added"
    And I click "Add Button"
    Then I should see "Add Products"
    And I check PSKU3 record in "Add Products Popup" grid
    And I check PSKU4 record in "Add Products Popup" grid
    And I check PSKU7 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU3 | Product 3 |
      | PSKU4 | Product 4 |
      | PSKU7 | Product 7 |
    And I should see 7 for "All Added" counter
    And I should see 0 for "Excluded" counter
    And I should see 3 for "Manually Added" counter

  Scenario: Counters are updated in Exclude action, All Added and Excluded tabs when some products excluded
    When I click "Excluded"
    Then I should see following "Active Grid" grid:
      | SKU | NAME |
    When I click "Add Button"
    Then I should see "Add Products"
    And I check PSKU3 record in "Add Products Popup" grid
    And I check PSKU4 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU3 | Product 3 |
      | PSKU4 | Product 4 |
    And I should see 5 for "All Added" counter
    And I should see 2 for "Excluded" counter
    And I should see 1 for "Manually Added" counter

  Scenario: Check that tab counter's value doesn't depend on grid's counter value
    When I click "All Added"
    Then I should see following "Active Grid" grid:
      | SKU    | NAME       |
      | PSKU1  | Product 1  |
      | PSKU7  | Product 7  |
      | PSKU10 | Product 10 |
      | PSKU11 | Product 11 |
      | PSKU12 | Product 12 |
    And I scroll to "ActiveGrid"
    When I filter SKU as contains "1" in "ActiveGrid" grid
    Then I should see following "Active Grid" grid:
      | SKU    | NAME       |
      | PSKU1  | Product 1  |
      | PSKU10 | Product 10 |
      | PSKU11 | Product 11 |
      | PSKU12 | Product 12 |
    And I should see 5 for "All Added" counter
    And I should see 2 for "Excluded" counter
    And I should see 1 for "Manually Added" counter

  Scenario: Check that tab counter's value doesn't depend on grid's counter value 2, after grid change
    When I click "Excluded"
    Then I should see following "Active Grid" grid:
      | SKU   | NAME      |
      | PSKU3 | Product 3 |
      | PSKU4 | Product 4 |
    When I click "Add Button"
    Then I should see "Add Products"
    And I check PSKU11 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    Then I should see following "Active Grid" grid:
      | SKU    | NAME       |
      | PSKU3  | Product 3  |
      | PSKU4  | Product 4  |
      | PSKU11 | Product 11 |
    And I should see 4 for "All Added" counter
    And I should see 3 for "Excluded" counter
    And I should see 1 for "Manually Added" counter
