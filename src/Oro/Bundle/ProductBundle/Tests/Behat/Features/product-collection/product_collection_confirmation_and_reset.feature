@regression
@fixture-OroProductBundle:product_collection_edit.yml
Feature: Product collection confirmation and reset
  In order to add more than one product by some criteria into the content nodes
  As an Administrator
  I want to have confirmation popup for changes which will be applied

  Scenario: Confirmation cancel after save changed not applied filters
    Given I login as administrator
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click "Content Variants"
    And I click on "First Content Variant Expand Button"
    And type "PSKU2" in "value"
    And I save form
    Then I should see text matching "You have changes in the Filters section that have not been applied"
    And I click "Cancel" in modal window
    And I should see following grid:
      | SKU   | NAME      |
      | PSKU1 | Product 1 |

  Scenario: Confirmation accept after save changed not applied filters
    When I click "Content Variants"
    And type "PSKU2" in "value"
    And I save form
    Then I should see text matching "You have changes in the Filters section that have not been applied"
    And I click "Continue" in modal window
    And I click on "First Content Variant Expand Button"
    And I should see following grid:
      | SKU   | NAME      |
      | PSKU2 | Product 2 |

  Scenario: Confirmation cancel, after save changed not applied filters several product collections
    When I click "Content Variants"
    And type "PSKU1" in "value"
    Then I click on "Show Variants Dropdown"
    And I click "Add Product Collection"
    And I click on "Advanced Filter"
    And I drag and drop "Field Condition" on "Drop condition here"
    And I click "Choose a field.."
    And I click on "SKU"
    And type "PSKU" in "value"
    And I click "Content Variants"
    And I click on "Advanced Filter"
    Then I should see 2 elements "Product Collection Variant Label"
    And I save form
    Then I should see text matching "You have changes in the Filters section that have not been applied"
    And I click "Cancel" in modal window
    Then I should not see text matching "You have changes in the Filters section that have not been applied"
    And I click "Cancel"
    Then I should see "Web Catalogs"

  Scenario: Reset Product Collection after filters change
    When I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click "Content Variants"
    And I click on "First Content Variant Expand Button"
    And type "SKU42" in "value"
    And I click "Preview Results"
    And I click on "Reset"
    Then I should not see "SKU42"
    And I click "Preview Results"
    Then I should see "PSKU2"
    And I click "Cancel"
