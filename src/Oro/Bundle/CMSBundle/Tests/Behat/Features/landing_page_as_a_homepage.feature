@regression
@feature-BB-23050

Feature: Landing Page as a Homepage

  Scenario: Feature background
    Given sessions active:
      | Guest | first_session  |
      | Admin | second_session |
    And I set "Homepage Content" before content for "Homepage" page

  Scenario: Check default Homepage setting value
    Given I proceed as the Admin
    When I login as administrator
    And I go to System / Configuration
    And I follow "System Configuration/Websites/Routing" on configuration sidebar
    Then "Routing Settings Form" must contains values:
      | Homepage | Homepage |
    And I should see the following options for "Homepage" select in form "Routing Settings Form":
      | Homepage      |
      | About         |
      | Cookie Policy |

  Scenario: Homepage system config value cannot be cleared
    When click "Reset"
    And I click "OK" in confirmation dialogue
    And save form
    Then I should see "Routing Settings Form" validation errors:
      | Homepage | This value should not be blank. |

  Scenario: Check home page content
    Given I proceed as the Guest
    When I am on homepage
    Then I should see "Homepage Content"

  Scenario: Select "Cookie Policy" landing page as a Homepage
    Given I proceed as the Admin
    When I fill "Routing Settings Form" with:
      | Homepage | Cookie Policy |
    And save form
    Then should see "Configuration saved" flash message

  Scenario: Check home page content
    Given I proceed as the Guest
    When I reload the page
    Then I should not see "Homepage Content"
    And I should see "This is the Cookie Policy for OroCommerce application."

  Scenario: Try to delete "Cookie Policy" Landing Page
    Given I proceed as the Admin
    When I go to Marketing / Landing Pages
    Then I should not see following actions for Cookie Policy in grid:
      | Delete |
