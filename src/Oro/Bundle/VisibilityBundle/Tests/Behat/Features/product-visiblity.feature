@fixture-OroVisibilityBundle:category_tree_with_product_visibility.yml
@skip
Feature: Product Visibility

  Scenario: Create two session
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I login as administrator and use in "second_session" as "Manager"
    When I continue as the Buyer
    And I click "Lighting Products"
    And I click "Products categories"
    Then I should see "PSKU1"
    Then I should see "PSKU2"
    Then I should see "PSKU3"

  Scenario: Hide product for customer group
    Given I operate as the Manager
    When I go to Products/Master Catalog
    When I expand "Retail Supplies" in tree
    And I click "Printers"
    And click "Visibility"
    And click "Visibility to Customer Groups"
    And I fill "Category Form" with:
      | Visibility To Customer First Group | Parent Category |
      | Inventory Threshold                | 1000            |
    And I submit form
    Then I should see "Category has been saved" flash message

    When I go to Products/Master Catalog
    And click "Retail Supplies"
    And click "Visibility"
    And click "Visibility to Customer Groups"
    And I fill "Category Form" with:
      | Visibility To Customer First Group | Hidden |
      | Inventory Threshold                | 1000   |
    And I submit form
    Then I should see "Category has been saved" flash message
    When I continue as the Buyer
    And I click "Lighting Products"
    And I click "Products categories"
    Then I should see "PSKU1"
    Then I should not see "PSKU2"
    Then I should not see "PSKU3"

  Scenario: Show product for customer
    Given I operate as the Manager
    And click "Visibility"
    And click "Visibility to Customers"
    And I fill "Category Form" with:
      | Visibility To Customers First | Visible |
    And I submit form
    Then I should see "Category has been saved" flash message
    When I continue as the Buyer
    And I reload the page
    Then I should see "PSKU1"
    Then I should see "PSKU2"
    Then I should not see "PSKU3"

  Scenario: Show product for All
    Given I operate as the Manager
    When I go to Products/Master Catalog
    And click "Retail Supplies"
    And click "Visibility"
    And click "Visibility to All"
    And I fill "Category Form" with:
      | Visibility To All | Hidden |
    And I submit form
    Then I should see "Category has been saved" flash message
    When I continue as the Buyer
    And I click "Sign Out"
    And I click "Lighting Products"
    And I click "Products categories"
    Then I should see "PSKU1"
    Then I should not see "PSKU2"
    Then I should not see "PSKU3"
