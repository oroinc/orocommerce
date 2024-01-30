@ticket-BB-22594
@fixture-OroProductBundle:ProductKitsExportFixture.yml

Feature: Product Kit Duplicate

  Scenario: Check duplicate operation when all products from all required kit items available
    Given I login as administrator
    And I go to Products/ Products
    When click duplicate "PSKU_KIT1" in grid
    Then I should see Product with:
      | Name             | Product Kit 1 |
      | Inventory Status | In Stock      |
      | SKU              | PSKU_KIT1-1   |
    And I should see "Disabled" in the "Entity Status" element

  Scenario: Check duplicate operation when no required kit items are available
    Given I go to Products/ Products
    When click duplicate "PSKU_KIT2" in grid
    Then I should see Product with:
      | Name             | Product Kit 2 |
      | Inventory Status | In Stock      |
      | SKU              | PSKU_KIT2-1   |
    And I should see "Disabled" in the "Entity Status" element

  Scenario: Check duplicate operation when all products from any of the required kit items unavailable
    Given I go to Products/ Products
    When click edit "PSKU4" in grid
    And I fill "ProductForm" with:
      | Status | Disabled |
    And I save and close form
    Then I should see "Product has been saved" flash message
    When I go to Products/ Products
    And click edit "PSKU_KIT2-1" in grid
    And I fill "ProductKitForm" with:
      | Kit Item 1 Optional | false |
    And I save and close form
    Then I should see "Product has been saved" flash message
    When I go to Products/ Products
    And click duplicate "PSKU_KIT2-1" in grid
    Then I should see Product with:
      | Name             | Product Kit 2 |
      | Inventory Status | In Stock      |
      | SKU              | PSKU_KIT2-2   |
    And I should see "Disabled" in the "Entity Status" element
