@ticket-BB-15244
Feature: Landing page open on frontend
  In order to see landing page info
  As buyer
  I need to be able to open landing page on frontend

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Open Landing Page with empty content
    Given I proceed as the Admin
    And I login as administrator
    When I go to Marketing/Landing Pages
    And click "Create Landing Page"
    And I fill in Landing Page Titles field with "Other page"
    Then I should see URL Slug field filled with "other-page"
    When I save and close form
    Then I should see "Page has been saved" flash message
    When I go to System/Frontend Menus
    And click view commerce_main_menu in grid
    And click "Create Menu Item"
    And I fill "Commerce Menu Form" with:
      | Title | Other page |
      | URI   | other-page |
    And I save form
    Then I should see "Menu item saved successfully" flash message
    When I proceed as the Buyer
    And I am on the homepage
    Then I should see "Other page"
    When I click "Other page"
    Then Page title equals to "Other page"
