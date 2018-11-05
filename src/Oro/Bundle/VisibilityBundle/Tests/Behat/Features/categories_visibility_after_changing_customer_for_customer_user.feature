@ticket-BB-15302
@fixture-OroVisibilityBundle:categories-for-visibility.yml
@fixture-OroVisibilityBundle:customers.yml
Feature: Categories visibility after changing Customer for Customer User
  In order to have ability open categories on frontend
  As buyer
  I need to see only available categories

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Change "Medical Apparel" category visibility
    Given I proceed as the Admin
    And I login as administrator
    When I go to Products/Master Catalog
    And I click "Medical Apparel"
    And I click "Visibility" in scrollspy
    And I click "Visibility to Customers" tab
    And I fill "Category Form" with:
      | Visibility To Customers First  | Visible |
      | Visibility To Customers Second | Hidden  |
    And I save form
    Then I should see "Category has been saved" flash message
    When I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    Then I should see "Medical Apparel"
    And I should see "Lighting Products"
    When I proceed as the Admin
    And I go to Customers/Customer Users
    And click edit Amanda in grid
    And I fill "Customer User Form" with:
      | Customer | second |
    And I save form
    Then I should see "Customer user has been saved" flash message
    When I proceed as the Buyer
    And I go to homepage
    Then I should not see "Medical Apparel"
    And I should see "Lighting Products"
