@regression
@ticket-BAP-20123
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroInventoryBundle:checkout.yml

Feature: Entity Field Fallback Values rendering
  In order to have optimal performance and get information on all available fields
  As an administrator
  I should be able to be able to display Entity Field Fallback Values like Minimum Quantity To Order, Maximum Quantity To Order on Products grid

  Scenario: Configure Entity Field Fallback Values to be visible on Products grid
    Given I login as administrator
    When I go to System/ Entities/ Entity Management
    And I filter Name as is equal to "Product"
    And I click view "Product" in grid

    And I click edit "Minimum Quantity To Order" in grid
    And I fill form with:
      | Add To Grid Settings | Yes and display |
      | Show Grid Filter     | Yes             |
    And I save and close form
    Then I should see "Field saved" flash message

    And I click edit "Low Inventory Threshold" in grid
    And I fill form with:
      | Add To Grid Settings | Yes and display |
      | Show Grid Filter     | Yes             |
    And I save and close form
    Then I should see "Field saved" flash message

    And I click edit "Upcoming" in grid
    And I fill form with:
      | Add To Grid Settings | Yes and display |
      | Show Grid Filter     | Yes             |
    And I save and close form
    Then I should see "Field saved" flash message

    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Set Entity Field Fallback Values
    When I go to Products/Products
    And click edit "SKU2" in grid
    And fill "Products Product Option Form" with:
      | Minimum Quantity To Order Use | false |
      | Minimum Quantity To Order     | 100   |
      | Is Upcoming Use               | false |
      | Is Upcoming                   | Yes   |
    And I save and close form
    Then I should see "Product has been saved" flash message

    When I go to Products/Master Catalog
    And I click "NewCategory"
    And fill "Category Product Option Form" with:
      | Low Inventory Threshold Use | false |
      | Low Inventory Threshold     | 50    |
    And I click "Save"
    Then I should see "Category has been saved" flash message

    When I click "NewCategory2"
    And fill "Category Product Option Form" with:
      | Low Inventory Threshold Use | false |
      | Low Inventory Threshold     | 30    |
    And I click "Save"
    Then I should see "Category has been saved" flash message

  Scenario: Check products grid
    When I go to Products/Products
    Then I should see "MQTO" column in grid
    And I should not see "Minimum Quantity To Order" filter in grid
    And I should see "Low Inventory Threshold" column in grid
    And I should not see "Low Inventory Threshold" filter in grid
    And I should see "Upcoming" column in grid
    And I should not see "Upcoming" filter in grid

    And I should see following grid containing rows:
      | SKU  | Upcoming | MQTO | Low Inventory Threshold |
      | SKU3 | No       |      | 0                       |
      | SKU2 | Yes      | 100  | 30                      |
      | SKU1 | No       | 0    | 50                      |
