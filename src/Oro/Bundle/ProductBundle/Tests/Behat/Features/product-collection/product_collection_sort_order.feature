@fixture-OroProductBundle:product_collection_sort_order.yml
@elasticsearch
Feature: Product collection sort order
  In order to sort & prioritize products in a collection
  As an Administrator
  I want to have the ability of editing Product Collection sort order

  Scenario: Logged in as buyer and manager on different window sessions
    Given sessions active:
      | Admin  | first_session  |
      | Buyer  | second_session |

  Scenario: Product Collection sort order can be added
    Given I proceed as the Admin
    Given I login as administrator
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
      | PSKU2 | 2 |
      | PSKU4 | 0.4 |

  Scenario: Product Collection sort order has been saved
    When I save form
    Then I should see "Content Node has been saved" flash message
    When I reload the page
    And I click on "First Content Variant Expand Button"
    Then I should see 1 element "Product Collection Variant Label"
    And I should see following grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
      | PSKU2 | Product 2 |
      | PSKU1 | Product 1 |
      | PSKU3 | Product 3 |
      | PSKU5 | Product 5 |

  Scenario: Using select row grid action has not affect on Product Collection's elements visibility
    When I check PSKU4 record in "Active Grid" grid
    Then I should see an "Product Collection Grid PSKU4 input" element
    When I check PSKU4 record in "Active Grid" grid
    Then I should see an "Product Collection Grid PSKU4 input" element

  Scenario: Product Collection correctly sorted in frontend
    Given I operate as the Buyer
    When I am on homepage
    Then PSKU4 must be first record in "Product Frontend Grid"
    And PSKU2 must be second record in "Product Frontend Grid"
    And PSKU1 must be 3 record in "Product Frontend Grid"
    And PSKU3 must be 4 record in "Product Frontend Grid"
    And PSKU5 must be 5 record in "Product Frontend Grid"

  Scenario: Product Collection sort order can be edited
    Given I proceed as the Admin
    And I fill "Product Collection Grid Form" with:
      | PSKU2 |  |
      | PSKU3 | 0 |
      | PSKU1 | 0.1 |

  Scenario: Product Collection sort order has been saved again
    When I save form
    Then I should see "Content Node has been saved" flash message
    When I reload the page
    And I click on "First Content Variant Expand Button"
    Then I should see 1 element "Product Collection Variant Label"
    And I should see following grid:
      | SKU   | NAME      |
      | PSKU3 | Product 3 |
      | PSKU1 | Product 1 |
      | PSKU4 | Product 4 |
      | PSKU2 | Product 2 |
      | PSKU5 | Product 5 |

  Scenario: Product Collection sort order updated in frontend
    Given I operate as the Buyer
    When I am on homepage
    Then PSKU3 must be first record in "Product Frontend Grid"
    And PSKU1 must be second record in "Product Frontend Grid"
    And PSKU4 must be 3 record in "Product Frontend Grid"
    And PSKU2 must be 4 record in "Product Frontend Grid"
    And PSKU5 must be 5 record in "Product Frontend Grid"
