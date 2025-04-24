@feature-BB-24920
@regression
@fixture-OroCommerceBundle:CustomerUserFixture.yml
@fixture-OroCommerceBundle:ProductFixture.yml
@fixture-OroCommerceBundle:RfqFixture.yml
@fixture-OroCommerceBundle:QuoteFixture.yml
@fixture-OroCommerceBundle:ShoppingListFixture.yml
@fixture-OroCommerceBundle:CheckoutFixture.yml
@fixture-OroCommerceBundle:OrderFixture.yml

Feature: Customer Dashboard Theme Configuration Options
  In order to ensure the correct functionality of the theme configuration settings
  As an administrator
  I should be able to verify that new theme configuration options are applied correctly
  and reflect the expected behavior in the system

  Scenario: Initialize User Sessions
    Given sessions active:
      | Admin | system_session |
      | Buyer | first_session  |

  Scenario: Validate Default Theme Configuration Settings
    Given I proceed as the Admin
    And I login as administrator
    When I go to System / Theme Configurations
    And I click "Edit" on row "Refreshing Teal" in grid
    Then "Theme Configuration Form" must contain values:
      | Show Quick Access Menu                | true                  |
      | Show Purchase Volume Chart            | true                  |
      | Customer Dashboard Content Widget     | my-shopping-lists     |
      | Customer Dashboard Content Widget (2) | my-checkouts          |
      | Customer Dashboard Content Widget (3) | my-latest-orders      |
      | Customer Dashboard Content Widget (4) | open-quotes           |
      | Customer Dashboard Content Widget (5) | latest-rfq            |
      | Customer Dashboard Content Widget (6) | Choose Content Widget |
      | Recommended Products                  | featured-products     |
      | Recommended Products (2)              | new-arrivals          |
      | Promotional Content Block             | Choose Content Block  |
      | Promotional Content Block (2)         | Choose Content Block  |

  Scenario: Verify Quick Access Menu and Widgets Display on Buyer Dashboard
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    When I click "Dashboard"
    Then I should see an "Quick Access Dashboard Menu" element
    And I should see that "Purchase Volume Widget" contains "Purchase Volume"
    And I should see that "My Shopping Lists Widget" contains "My Shopping List"
    And I should see that "My Checkouts Widget" contains "My Checkouts"
    And I should see that "My Latest Orders Widget" contains "My Latest Orders"
    And I should see that "Open Quotes Widget" contains "Open Quotes"
    And I should see that "Requests For Quote Widget" contains "Requests For Quote"

  Scenario: Disable Quick Access Menu and Widgets via Theme Configuration
    Given I proceed as the Admin
    When I fill "Theme Configuration Form" with:
      | Show Quick Access Menu                | false |
      | Show Purchase Volume Chart            | false |
      | Customer Dashboard Content Widget     |       |
      | Customer Dashboard Content Widget (2) |       |
      | Customer Dashboard Content Widget (3) |       |
      | Customer Dashboard Content Widget (4) |       |
      | Customer Dashboard Content Widget (5) |       |
      | Customer Dashboard Content Widget (6) |       |
      | Recommended Products                  |       |
      | Recommended Products (2)              |       |
      | Promotional Content Block             |       |
      | Promotional Content Block (2)         |       |
    And I save and close form
    Then I should see "Theme Configuration" flash message

  Scenario: Verify Theme Configuration Reflects Disabled Widgets
    Given I click "Edit" on row "Refreshing Teal" in grid
    Then "Theme Configuration Form" must contain values:
      | Show Quick Access Menu                | false                 |
      | Show Purchase Volume Chart            | false                 |
      | Customer Dashboard Content Widget     | Choose Content Widget |
      | Customer Dashboard Content Widget (2) | Choose Content Widget |
      | Customer Dashboard Content Widget (3) | Choose Content Widget |
      | Customer Dashboard Content Widget (4) | Choose Content Widget |
      | Customer Dashboard Content Widget (5) | Choose Content Widget |
      | Customer Dashboard Content Widget (6) | Choose Content Widget |
      | Recommended Products                  | Choose Content Widget |
      | Recommended Products (2)              | Choose Content Widget |
      | Promotional Content Block             | Choose Content Block  |
      | Promotional Content Block (2)         | Choose Content Block  |

  Scenario: Validate Hidden Quick Access Menu and Widgets on Buyer Dashboard
    Given I proceed as the Buyer
    When I reload the page
    Then I should not see an "Quick Access Dashboard Menu" element
    And I should not see an "Purchase Volume Widget" element
    And I should not see an "My Shopping Lists Widget" element
    And I should not see an "My Checkouts Widget" element
    And I should not see an "My Latest Orders Widget" element
    And I should not see an "Open Quotes Widget" element
    And I should not see an "Requests For Quote Widget" element
