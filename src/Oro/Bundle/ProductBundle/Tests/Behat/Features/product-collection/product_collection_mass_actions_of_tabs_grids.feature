@fixture-OroProductBundle:product_collections_individual_products.yml
@regression
Feature: Product collection mass actions of tabs grids
  In order to be able to process several product at once, at Product Collection's All Added, Excluded and Manually Added tabs
  As and Administrator
  I want to have ability to use mass actions here

  Scenario Outline: Process a few product from different pages, at once, with help of mass action
    Given I login as administrator
    And I am on Content Node page and added Product Collection variant
    And I have all products available in <tab> tab, and focused on it
    And I check PSKU1 record in "Active Grid" grid
    And I check PSKU2 record in "Active Grid" grid
    And I press next page button in grid "Active Grid"
    And I check PSKU11 record in "Active Grid" grid
    And I click <mass action> mass action in "Active Grid" grid
    Then I should see following "Active Grid" grid:
      | SKU    | NAME       |
      | PSKU3  | Product 3  |
      | PSKU4  | Product 4  |
      | PSKU5  | Product 5  |
      | PSKU6  | Product 6  |
      | PSKU7  | Product 7  |
      | PSKU8  | Product 8  |
      | PSKU9  | Product 9  |
      | PSKU10 | Product 10 |
      | PSKU12 | Product 12 |
    And I click "Cancel"
    Examples:
      | tab            | mass action      |
      | All Added      | Exclude          |
      | Excluded       | Remove           |
      | Manually Added | Reset to Default |

  Scenario Outline: Process all products at once, with help of mass action
    Given I am on Content Node page and added Product Collection variant
    And I have all products available in <tab> tab, and focused on it
    And I check all records in "Active Grid" grid
    And I click <mass action> mass action in "Active Grid" grid
    And I should see "There are no products"
    And I click "Cancel"
    Examples:
      | tab            | mass action      |
      | All Added      | Exclude          |
      | Excluded       | Remove           |
      | Manually Added | Reset to Default |

  Scenario Outline: Process all products except a few
    Given I am on Content Node page and added Product Collection variant
    And I have all products available in <tab> tab, and focused on it
    And I check all records in "Active Grid" grid
    And I uncheck PSKU1 record in "Active Grid" grid
    And I uncheck PSKU2 record in "Active Grid" grid
    And I press next page button in grid "Active Grid"
    And I uncheck PSKU11 record in "Active Grid" grid
    And I click <mass action> mass action in "Active Grid" grid
    Then I should see following "Active Grid" grid:
      | SKU    | NAME       |
      | PSKU1  | Product 1  |
      | PSKU2  | Product 2  |
      | PSKU11 | Product 11 |
    And I click "Cancel"
    Examples:
    | tab            | mass action      |
    | All Added      | Exclude          |
    | Excluded       | Remove           |
    | Manually Added | Reset to Default |

  Scenario Outline: Process all visible products
    Given I am on Content Node page and added Product Collection variant
    And I have all products available in <tab> tab, and focused on it
    And I press next page button in grid "Active Grid"
    And I check All Visible records in "Active Grid" grid
    And I click <mass action> mass action in "Active Grid" grid
    Then I should see following "Active Grid" grid:
      | SKU    | NAME       |
      | PSKU1  | Product 1  |
      | PSKU2  | Product 2  |
      | PSKU3  | Product 3  |
      | PSKU4  | Product 4  |
      | PSKU5  | Product 5  |
      | PSKU6  | Product 6  |
      | PSKU7  | Product 7  |
      | PSKU8  | Product 8  |
      | PSKU9  | Product 9  |
      | PSKU10 | Product 10 |
    And I should see "Total Of 10 Records"
    And I click "Cancel"
    Examples:
      | tab            | mass action      |
      | All Added      | Exclude          |
      | Excluded       | Remove           |
      | Manually Added | Reset to Default |
