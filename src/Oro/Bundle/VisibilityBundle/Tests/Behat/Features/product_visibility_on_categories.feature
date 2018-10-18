@regression
@fixture-OroVisibilityBundle:category_tree_with_product_visibility.yml

Feature: Product visibility on categories
  In order to manager product visibility on categories
  As an Administrator
  I want to be able to manage product visibility on categories from master catalog

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Check default product visibility
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I click "Lighting Products"
    And I click "All Products"
    Then I should see "PSKU1"
    And I should see "PSKU2"
    And I should see "PSKU3"

  Scenario: Hide products of Printers category for customer group
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products/Master Catalog
    And I expand "Retail Supplies" in tree
    And I click "Printers"
    And I click "Visibility" in scrollspy
    And I click "Visibility to Customer Groups" tab
    When I fill "Category Form" with:
      | Visibility To Customer First Group | Parent Category |
      | Inventory Threshold                | 1000            |
      | Low Inventory Threshold            | 0               |
    And I submit form
    Then I should see "Category has been saved" flash message

  Scenario: Hide product of parent category for customer group
    Given I go to Products/Master Catalog
    And I click "Retail Supplies"
    And I click "Visibility" in scrollspy
    And I click "Visibility to Customer Groups" tab
    When I fill "Category Form" with:
      | Visibility To Customer First Group | Hidden |
      | Inventory Threshold                | 1000   |
      | Low Inventory Threshold            | 0      |
    And I submit form
    Then I should see "Category has been saved" flash message

  Scenario: Check product visibility for customer group
    Given I continue as the Buyer
    When I click "Lighting Products"
    And I click "All Products"
    Then I should see "PSKU1"
    And I should not see "PSKU2"
    And I should not see "PSKU3"

  Scenario: Show product for customer
    Given I proceed as the Admin
    And I click "Visibility" in scrollspy
    And I click "Visibility to Customers" tab
    When I fill "Category Form" with:
      | Visibility To Customers First | Visible |
    And I submit form
    Then I should see "Category has been saved" flash message

  Scenario: Check product visibility for customer
    Given I proceed as the Buyer
    When I reload the page
    Then I should see "PSKU1"
    And I should see "PSKU2"
    And I should not see "PSKU3"

  Scenario: Show product for All
    Given I proceed as the Admin
    And I go to Products/Master Catalog
    And I click "Retail Supplies"
    And I click "Visibility" in scrollspy
    And I click "Visibility to All" tab
    When I fill "Category Form" with:
      | Visibility To All | Hidden |
    And I submit form
    Then I should see "Category has been saved" flash message

  Scenario: Check product visibility for All
    Given I proceed as the Buyer
    When I click "Sign Out"
    And I click "Lighting Products"
    And I click "All Products"
    Then I should see "PSKU1"
    And I should not see "PSKU2"
    And I should not see "PSKU3"
