@ticket-BB-10960
@automatically-ticket-tagged
@fixture-OroCatalogBundle:categories-for-visibility.yml
Feature: Change categories visibility for store front
  In order to make categories invisible at store front
  As site administrator
  I need to be able to change category visibility

  Scenario: Logged in as buyer and manager on different window sessions
    Given sessions active:
      | Admin  | first_session  |
      | Buyer  | second_session |

  Scenario: Change "Medical Apparel" category visibility
    Given I proceed as the Admin
    And I login as administrator
    When I go to Products/Master Catalog
    And I click "Medical Apparel"
    And I click "Visibility" in scrollspy
    And I fill form with:
      | Visibility to All | Hidden |
    And I save form
    Then I should see "Category has been saved" flash message

  Scenario: Change "Retail Supplies > Fax" category visibility
    Given I should see "Products/ Master Catalog" in breadcrumbs
    And I expand "Retail Supplies" in tree
    And I click "Fax"
    And I click "Visibility" in scrollspy
    And I fill form with:
      | Visibility to All | Hidden |
    And I save form
    Then I should see "Category has been saved" flash message

  Scenario: Hidden categories are not available in menu on homepage
    Given I proceed as the Buyer
    When I am on the homepage
    Then I should not see "Medical Apparel" in main menu
    And I should see "Retail Supplies" in main menu
    And I should see "Retail Supplies/ Printers" in main menu
    And I should not see "Retail Supplies/ Fax" in main menu
