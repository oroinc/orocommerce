@fixture-OroProductBundle:product_collection_add.yml
Feature: Product collection product change reflected
  In order to changes products included in product collection to reflected on store frontend
  As an Administrator
  I want to have ability of editing Product Collection variant and change will be reflected on store frontend

  Scenario: Logged in as buyer and manager on different window sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Product Collection can be edited
    Given I proceed as the Admin
    And I login as administrator
    And I set "Default Web Catalog" as default web catalog
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click "Root Node"
    And I save form
    And click "Create Content Node"
    And I fill "Content Node Form" with:
      | Titles           | Collection1                |
      | Url Slug         | collection1                |
    And I click on "Show Variants Dropdown"
    And I click "Add Product Collection"
    And I click "Content Variants"
    And I click on "Advanced Filter"
    And I should see "Drag And Drop From The Left To Start Working"
    And I drag and drop "Field Condition" on "Drop condition here"
    And I click "Choose a field.."
    And I click on "SKU"
    And type "PSKU" in "value"
    And I click on "Preview Results"
    And I save form
    And I should see "Content Node has been saved" flash message

  Scenario: Edited product collection is accesible at frontend
    Given I operate as the Buyer
    And I am on homepage
    And I click "Collection1"
    Then I should see "PSKU1"
    And I should see "PSKU2"

  Scenario: Change "Product 2" SKU in order to exclude it from product collection filter
    Given I proceed as the Admin
    And I login as administrator
    And go to Products/ Products
    And I click view Product 2 in grid
    And I click "Edit"
    And I fill "ProductForm" with:
      | SKU | XSKU |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: "Product 2" that already not confirm to filter, excluded from product collection grid at backend
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click "Collection1"
    And I click on "First Content Variant Expand Button"
    Then I should see following grid:
      | SKU   | NAME      |
      | PSKU1 | Product 1 |

  Scenario: "Product 2" that already not confirm to filter, excluded from product collection grid at frontend
    Given I operate as the Buyer
    And I am on homepage
    And I click "Collection1"
    Then I should see "PSKU1"
    And I should not see "PSKU2"
    And I should not see "XSKU"

  Scenario: Change "Product 2" SKU in order to include it to the product collection filter again
    Given I operate as the Admin
    And I click "Cancel"
    And go to Products/ Products
    And I click view Product 2 in grid
    And I click "Edit"
    And I fill "ProductForm" with:
      | SKU | PSKU2 |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: "Product 2" that confirm to filter again, included into product collection grid at backend
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click "Collection1"
    And I click on "First Content Variant Expand Button"
    Then I should see following grid:
      | SKU   | NAME      |
      | PSKU1 | Product 1 |
      | PSKU2 | Product 2 |

  Scenario: "Product 2" that confirm to filter again, included into product collection grid at frontend
    Given I operate as the Buyer
    And I am on homepage
    And I click "Collection1"
    Then I should see "PSKU1"
    And I should see "PSKU2"
