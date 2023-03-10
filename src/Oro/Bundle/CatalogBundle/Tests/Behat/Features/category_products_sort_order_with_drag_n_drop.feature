@fixture-OroCatalogBundle:category_products_sort_order_drag_n_drop.yml
@elasticsearch
Feature: Category products sort order with drag n' drop
  In order to sort & prioritize products in a category
  As an Administrator
  I want to have the ability drag n' drop products in Category grid to change sort order

  Scenario: Logged in as buyer and manager on different window sessions
    Given sessions active:
      | Admin  | first_session  |

  Scenario: Sort order product with drag n' drop
    Given I proceed as the Admin
    Given I login as administrator
    And I go to Products/ Master Catalog
    And I click "NewCategory"
    And I fill "Category Form" with:
      | PSKU2 | 2             |
      | PSKU4 | 0.4           |
    When I click "Manage sort order"
    Then should see "Save all changes?" in confirmation dialogue
    When I click "Submit and continue" in confirmation dialogue
    Then I should see "Category has been saved" flash message
    And I should see "UiDialog" with elements:
      | Title | Sort products in NewCategory category |
    When I drag and drop "Draggable Separator Row" before "Draggable Product5 Row"
    And I drag and drop "Draggable Product5 Row" before "Draggable Product2 Row"
    And I click "Close" in "UiDialog ActionPanel" element
    Then I should see following grid:
      | IN CATEGORY | SORT ORDER | SKU   | NAME      |
      | 1           | 0.4        | PSKU4 | Product 4 |
      | 1           | 2          | PSKU5 | Product 5 |
      | 1           | 12         | PSKU2 | Product 2 |
      | 1           | 22         | PSKU1 | Product 1 |
      | 0           |            | PSKU3 | Product 3 |

  Scenario: Sort order product with drag n' drop and Drop Zone Move
    When I click "Manage sort order"
    And I drag and drop "Draggable Product1 Row" on "Drop Zone Move to Top"
    And I drag and drop "Draggable Product4 Row" on "Drop Zone Move to Bottom"
    And I drag and drop "Draggable Product5 Row" on "Drop Zone Remove from Sorted"
    And I drag and drop "Draggable Product2 Row" on "Drop Zone Exclude from Category"
    And I click "Close" in "UiDialog ActionPanel" element
    Then I should see following grid:
      | IN CATEGORY | SORT ORDER | SKU   | NAME      |
      | 1           | 0.4        | PSKU1 | Product 1 |
      | 1           | 22         | PSKU4 | Product 4 |
      | 1           |            | PSKU5 | Product 5 |
      | 0           |            | PSKU2 | Product 2 |
      | 0           |            | PSKU3 | Product 3 |
