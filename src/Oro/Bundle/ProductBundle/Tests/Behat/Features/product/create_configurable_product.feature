Feature: Create configurable product
  In order to manage products
  As administrator
  I need to be able to create configurable product

  Scenario: First step validation
    Given I login as administrator
    And go to Products/ Products
    And click "Create Product"
    And fill "ProductForm Step One" with:
      | Type           | Configurable |
      | Product Family | Default      |
    When I focus on "Type" field and press Enter key
    Then I should see "Configurable product requires at least one filterable attribute of the Select or Boolean type to enable product variants. The provided product family does not fit for configurable products" error message
