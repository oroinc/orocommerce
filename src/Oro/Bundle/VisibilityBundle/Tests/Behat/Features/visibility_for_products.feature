@regression
@ticket-BB-13765
@fixture-OroVisibilityBundle:category_tree_with_product_visibility.yml

Feature: Visibility for products
  In order to manager product visibility
  As an Administrator
  I want to be able to hide product on front store and show again after it was hidden

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Check default product visibility
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I click "Lighting Products"
    Then I should see "PSKU1"

  Scenario: Hide product for all
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products/ Products
    And I click view PSKU1 in grid
    And I follow "More actions"
    And I click "Manage Visibility"
    When I select "Hidden" from "Visibility to All"
    And I fill "Visibility Product Form" with:
      | Visibility To Customer First Group | Current Product |
      | Visibility To Customers First      | Customer Group  |
    And I submit form
    Then I should see "Product visibility has been saved" flash message

  Scenario: Check product hidden
    Given I continue as the Buyer
    And should be 3 items in "oro_product_WEBSITE_ID" website search index
    When I click "All Products"
    Then I should not see "PSKU1"

  Scenario: Show product for all
    Given I proceed as the Admin
    When I select "Category" from "Visibility to All"
    And I submit form
    Then I should see "Product visibility has been saved" flash message

  Scenario: Check product visibility
    Given I continue as the Buyer
    And should be 4 items in "oro_product_WEBSITE_ID" website search index
    When I click "Lighting Products"
    Then I should see "PSKU1"
