@fixture-OroProductBundle:product_collection_sort_order.yml
@elasticsearch
Feature: Product collection sort order with drag n' drop
  In order to sort & prioritize products
  As an Administrator
  I want to have the ability drag n' drop products in Product Collection grid to change sort order

  Scenario: Logged in as buyer and manager on different window sessions
    Given sessions active:
      | Admin | first_session |

  Scenario: Product Collection sort order can be added
    Given I proceed as the Admin
    And I login as administrator
    And I set "Default Web Catalog" as default web catalog
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Remove Variant Button"
    And I click "Content Variants"
    And I click on "Show Variants Dropdown"
    And I click "Add Product Collection"
    And I click on "Advanced Filter"
    And I should see "Drag And Drop From The Left To Start Working"
    And I drag and drop "Field Condition" on "Drop condition here"
    And I click "Choose a field.."
    And I click on "SKU"
    And I type "PSKU" in "value"
    And I click on "Preview Results"
    Then I should see following grid:
      | SKU   | NAME      |
      | PSKU1 | Product 1 |
      | PSKU2 | Product 2 |
      | PSKU3 | Product 3 |
      | PSKU4 | Product 4 |
      | PSKU5 | Product 5 |
    And I type "Some Custom Segment Name" in "Segment Name"
    And I fill "Product Collection Grid Form" with:
      | PSKU2 | 2   |
      | PSKU4 | 0.4 |

  Scenario: Sort order product with drag n' drop
    When I click "Manage sort order"
    Then should see "Save all changes?" in confirmation dialogue
    When I click "Submit and continue" in confirmation dialogue
    Then I should see "Content Node has been saved" flash message
    And I should see "UiDialog" with elements:
      | Title | Sort products in Some Custom Segment Name product collection |
    When I drag and drop "Draggable Separator Row" before "Draggable Product5 Row"
    And I drag and drop "Draggable Product5 Row" before "Draggable Product2 Row"
    And I click "Close" in "UiDialog ActionPanel" element
    Then I should see following grid:
      | SORT ORDER | SKU   | NAME      |
      | 0.4        | PSKU4 | Product 4 |
      | 2          | PSKU5 | Product 5 |
      | 12         | PSKU2 | Product 2 |
      | 22         | PSKU1 | Product 1 |
      | 32         | PSKU3 | Product 3 |

  Scenario: Sort order product with drag n' drop and Drop Zone Move
    When I click "Manage sort order"
    And I drag and drop "Draggable Product1 Row" on "Drop Zone Move to Top"
    And I drag and drop "Draggable Product4 Row" on "Drop Zone Move to Bottom"
    And I drag and drop "Draggable Product5 Row" on "Drop Zone Remove from Sorted"
    # following step is skipped due to issue with waitForAjax AfterStep
    # And I drag and drop "Draggable Product2 Row" on "Drop Zone Exclude from Collection"
    And I click "Close" in "UiDialog ActionPanel" element
    # click on scrollspy section bellow to scroll page and make products grid visible
    And I click "Activity" in scrollspy
    Then I should see following grid:
      | SORT ORDER | SKU   | NAME      |
      | 0.4        | PSKU1 | Product 1 |
      | 12         | PSKU2 | Product 2 |
      | 22         | PSKU3 | Product 3 |
      | 32         | PSKU4 | Product 4 |
      |            | PSKU5 | Product 5 |
#    And I should see 1 for "Excluded" counter
