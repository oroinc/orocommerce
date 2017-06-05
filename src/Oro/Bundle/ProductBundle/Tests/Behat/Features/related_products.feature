@fixture-products.yml
@feature-BB-8377
Feature: Editing related products
  In order to propose my customer some other products
  As admin
  I need to be able to set related products to product

  Scenario: Check if datagrid of a product doesn't contain this product
    Given I login as administrator
    When go to Products/ Products
    And I click Edit "Product 1" in grid
    And I click "Select related products"
    Then I should see following "Grid On Popup" grid:
      | SKU    | NAME      |
      | PSKU2  | Product 2 |
      | PSKU3  | Product 3 |
      | PSKU4  | Product 4 |
    And I click "Cancel"

  Scenario: Create relation
    Given go to Products/ Products
    And I click Edit "Product 1" in grid
    And I click "Select related products"
    And I should see following "Grid On Popup" grid:
      | Is Related  | SKU    | NAME      |
      | 0           | PSKU2  | Product 2 |
      | 0           | PSKU3  | Product 3 |
    When I select following records in Grid On Popup:
      | PSKU2 |
      | PSKU3 |
    And I click "Select products"
    And I should see following grid:
      | SKU    | NAME      |
      | PSKU2  | Product 2 |
      | PSKU3  | Product 3 |
    And I click "Save and Close"
    Then I should see "Product has been saved" flash message
    And I should see following grid:
      | SKU    | NAME      |
      | PSKU2  | Product 2 |
      | PSKU3  | Product 3 |

  Scenario: Grid in popup should have related products checked
    Given go to Products/ Products
    And I click Edit "Product 1" in grid
    And I should see following grid:
      | SKU    | NAME      |
      | PSKU2  | Product 2 |
      | PSKU3  | Product 3 |
    When I click "Select related products"
    Then I should see following "Grid On Popup" grid:
      | Is Related  | SKU    | NAME      |
      | 1           | PSKU2  | Product 2 |
      | 1           | PSKU3  | Product 3 |
      | 0           | PSKU4  | Product 4 |
    And I click "Cancel"

  Scenario: Change relation with quick edit
    And go to Products/ Products
    And I click View Product 1 in grid
    And I click "Quick edit"
    And I click "Select related products"
    When I select following records in Grid On Popup:
      | PSKU4 |
    And I click "Select products"
    And I select following records in grid:
      | PSKU2 |
    And I click "Delete" link from mass action dropdown
    And I click "Save and Close"
    Then I should see following grid:
      | SKU    | NAME      |
      | PSKU3  | Product 3 |
      | PSKU4  | Product 4 |

  Scenario: Canceling edit will not affect related products
    And go to Products/ Products
    And I click Edit Product 1 in grid
    And I should see following grid:
      | SKU    | NAME      |
      | PSKU3  | Product 3 |
      | PSKU4  | Product 4 |
    When I select following records in grid:
      | PSKU4 |
    And I click "Delete" link from mass action dropdown
    And I click "Cancel"
    And I click View Product 1 in grid
    Then I should see following grid:
      | SKU    | NAME      |
      | PSKU3  | Product 3 |
      | PSKU4  | Product 4 |
