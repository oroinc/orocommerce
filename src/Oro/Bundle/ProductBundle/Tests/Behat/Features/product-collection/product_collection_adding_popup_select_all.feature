@regression
@fixture-OroProductBundle:product_collections_individual_products.yml
Feature: Product collection adding popup select all
  In order to be able to add, to the Product Collection, bunch of products that is available in adding popup grid
  As and Administrator
  I want to have ability to use "select all" and "select all visible" grid features

  Scenario: Add with help of "select all" grid feature, more products than allowed to add at once
    Given I login as administrator
    And I set "Mass action limit" in Product Collections settings to the "5"
    And I am on Content Node page and added Product Collection variant
    When I click "All Added"
    And I click "Add Button"
    And I check all records in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    And I should see "A limit of selected products (5) was exceeded. Do you want to force add your selection?"
    And I click "Yes"
    Then I should see following grid:
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
    And number of records should be 12
    Then I click "Cancel"

  Scenario: Add with help of "select all" grid feature, allowed amount of products
    Given I set "Mass action limit" in Product Collections settings to the "100"
    And I am on Content Node page and added Product Collection variant
    And I click "All Added"
    And I click "Add Button"
    And I check all records in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    Then I should see following grid:
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
    And number of records should be 12
    Then I click "Cancel"

  Scenario: Add with help of "select all" grid feature, with unchecked products, and do add from second page
    Given I am on Content Node page and added Product Collection variant
    And I click "All Added"
    And I click "Add Button"
    And I check all records in "Add Products Popup" grid
    And I uncheck PSKU11 record in grid
    And I press next page button in grid "Add Products Popup"
    And I uncheck PSKU1 record in grid
    And I uncheck PSKU2 record in grid
    And I click "Add" in "UiDialog ActionPanel" element
    Then I should see following grid:
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
    Then I click "Cancel"

  Scenario: Add with help of "select all", products from grid that is filtered with grid's controls
    Given I am on Content Node page and added Product Collection variant
    And I click "All Added"
    And I click "Add Button"
    And I filter Name as is equal to "Product 1" in "Add Products Popup" grid
    And I check all records in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    Then I should see following grid:
      | SKU    | NAME      |
      | PSKU1  | Product 1 |
    Then I click "Cancel"

  Scenario: Add with help of "select visible", products from grid with multiple pages
    Given I am on Content Node page and added Product Collection variant
    And I click "All Added"
    And I click "Add Button"
    And I press next page button in grid "Add Products Popup"
    And I check All Visible records in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    Then I should see following grid:
      | SKU   | NAME      |
      | PSKU1 | Product 1 |
      | PSKU2 | Product 2 |
    Then I click "Cancel"

  Scenario: "Mass action limit" setting should accept only positive numbers
    Given I am on Product Collections settings page
    And I fill in "Mass Action Limit" with "-5"
    And I click "Save settings"
    Then I should see "This value should be greater than 0."
    Then I fill in "Mass Action Limit" with "1.25"
    And I click "Save settings"
    Then I should see "This value should be of type integer"
    And I fill in "Mass Action Limit" with "some string"
    And I click "Save settings"
    Then I should see "This value should not be blank."
