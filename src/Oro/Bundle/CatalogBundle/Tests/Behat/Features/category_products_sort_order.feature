@fixture-OroCatalogBundle:category_products_sort_order.yml
@elasticsearch
Feature: Category products sort order
  In order to sort & prioritize products in a category
  As an Administrator
  I want to have the ability of editing Category sort order

  Scenario: Logged in as buyer and manager on different window sessions
    Given sessions active:
      | Admin  | first_session  |
      | Buyer  | second_session |

  Scenario: Category sort order can be added
    Given I proceed as the Admin
    Given I login as administrator
    And I go to Products/ Master Catalog
    And I click "Create Category"
    Then I should see following grid:
      | SKU   | NAME      |
      | PSKU1 | Product 1 |
      | PSKU2 | Product 2 |
      | PSKU3 | Product 3 |
      | PSKU4 | Product 4 |
      | PSKU5 | Product 5 |
    And I click on PSKU1 in grid
    And I click on PSKU2 in grid
    And I click on PSKU4 in grid
    And I click on PSKU5 in grid
    And I fill "Category Form" with:
      | Title | Test Category |
      | PSKU2 | 2             |
      | PSKU4 | 0.4           |
    And click "Save"
    Then I should see "Category has been saved" flash message
    And I should see following grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
      | PSKU2 | Product 2 |
      | PSKU1 | Product 1 |
      | PSKU5 | Product 5 |
      | PSKU3 | Product 3 |

  Scenario: Category correctly sorted in frontend
    Given I operate as the Buyer
    When I am on homepage
    Then I should see "4 items" for "Test Category" category
    When I click "Test Category"
    Then PSKU4 must be first record in "Product Frontend Grid"
    And PSKU2 must be second record in "Product Frontend Grid"
    And PSKU1 must be 3 record in "Product Frontend Grid"
    And PSKU5 must be 4 record in "Product Frontend Grid"
    And I should not see "PSKU3"

  Scenario: Category sort order can be edited
    Given I proceed as the Admin
    And I click on PSKU1 in grid
    And I click on PSKU3 in grid
    And I fill "Category Form" with:
      | Title | Test Category |
      | PSKU2 |               |
      | PSKU3 | 0.3           |
      | PSKU5 | 0             |
    And click "Save"

  Scenario: Category sort order has been saved again
    When I save form
    Then I should see "Category has been saved" flash message
    Then I should see following grid:
      | SKU   | NAME      |
      | PSKU5 | Product 5 |
      | PSKU3 | Product 3 |
      | PSKU4 | Product 4 |
      | PSKU2 | Product 2 |
      | PSKU1 | Product 1 |

  Scenario: Category sort order updated in frontend
    Given I operate as the Buyer
    When I am on homepage
    Then I should see "4 items" for "Test Category" category
    When I click "Test Category"
    Then PSKU5 must be first record in "Product Frontend Grid"
    And PSKU3 must be second record in "Product Frontend Grid"
    And PSKU4 must be 3 record in "Product Frontend Grid"
    And PSKU2 must be 4 record in "Product Frontend Grid"
    And I should not see "PSKU1"
