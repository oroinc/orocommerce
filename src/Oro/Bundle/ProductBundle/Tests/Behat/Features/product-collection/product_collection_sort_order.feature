@fixture-OroProductBundle:product_collection_sort_order.yml
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
      | PSKU5 | Product 5 |
      | PSKU4 | Product 4 |
      | PSKU3 | Product 3 |
      | PSKU2 | Product 2 |
      | PSKU1 | Product 1 |
    And I type "Some Custom Segment Name" in "Segment Name"
    And I fill "Product Collection Grid Form" with:
      | PSKU2 | 2 |

  Scenario: Product Collection sort order has been saved
    When I save form
    Then I should see "Content Node has been saved" flash message
    When I reload the page
    And I click on "First Content Variant Expand Button"
    Then I should see 1 element "Product Collection Variant Label"
    And I should see following grid:
      | SKU   | NAME      |
      | PSKU2 | Product 2 |
      | PSKU5 | Product 5 |
      | PSKU4 | Product 4 |
      | PSKU3 | Product 3 |
      | PSKU1 | Product 1 |

  Scenario: Product Collection correctly sorted in frontend
    Given I operate as the Buyer
    When I am on homepage
    Then I should see "PSKU2"
    And I should see "PSKU1"
    And I should see "PSKU3"
    And I should see "PSKU4"
    And I should see "PSKU5"

  Scenario: Product Collection sort order can be edited
    Given I proceed as the Admin
    And I fill "Product Collection Grid Form" with:
      | PSKU2 |  |
      | PSKU3 | 0 |
      | PSKU4 | 0.1 |

  Scenario: Product Collection sort order has been saved
    When I save form
    Then I should see "Content Node has been saved" flash message
    When I reload the page
    And I click on "First Content Variant Expand Button"
    Then I should see 1 element "Product Collection Variant Label"
    And I should see following grid:
      | SKU   | NAME      |
      | PSKU3 | Product 3 |
      | PSKU4 | Product 4 |
      | PSKU5 | Product 5 |
      | PSKU2 | Product 2 |
      | PSKU1 | Product 1 |

  Scenario: Product Collection sort order updated in frontend
    Given I operate as the Buyer
    When I am on homepage
    Then I should see "PSKU3"
    And I should see "PSKU4"
    And I should see "PSKU5"
    And I should see "PSKU2"
    And I should see "PSKU1"
