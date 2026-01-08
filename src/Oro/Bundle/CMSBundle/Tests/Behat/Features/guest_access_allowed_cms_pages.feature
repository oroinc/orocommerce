@regression
@ticket-BB-26729
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroCMSBundle:guest_access_cms_pages.yml
Feature: Guest Access Allowed CMS Pages
  In order to allow guests to access specific CMS pages when guest access is disabled
  As an Administrator
  I want to be able to configure which CMS pages are accessible to guests

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Guest | second_session |

  Scenario: Disable guest access
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Guests/Website Access" on configuration sidebar
    And uncheck "Use default" for "Enable Guest Access" field
    And I uncheck "Enable Guest Access"
    When I save form
    Then I should see "Configuration Saved" flash message

  Scenario: Verify CMS pages are not accessible when guest access is disabled
    Given I proceed as the Guest
    When I go to "/test-about-us"
    Then I should be on Customer User Login page
    When I go to "/test-privacy-policy"
    Then I should be on Customer User Login page

  Scenario: Configure allowed CMS pages
    Given I proceed as the Admin
    And I go to System/Configuration
    And I follow "Commerce/Guests/Website Access" on configuration sidebar
    And uncheck "Use default" for "Allow Guest Access to Landing Pages" field
    And I fill "System Config Form" with:
      | Allow Guest Access to Landing Pages | Test About Us Page |
    When I save form
    Then I should see "Configuration Saved" flash message

  Scenario: Verify configured CMS page is accessible
    Given I proceed as the Guest
    When I go to "/test-about-us"
    Then I should see "Test About Us Page"
    And I should see "This is the about us page content."
    And Page title equals to "Test About Us Page"

  Scenario: Verify non-configured CMS pages are still blocked
    Given I proceed as the Guest
    When I go to "/test-privacy-policy"
    Then I should be on Customer User Login page

  Scenario: Configure multiple allowed CMS pages
    Given I proceed as the Admin
    And I go to System/Configuration
    And I follow "Commerce/Guests/Website Access" on configuration sidebar
    And I fill "System Config Form" with:
      | Allow Guest Access to Landing Pages | [Test About Us Page, Test Privacy Policy Page] |
    When I save form
    Then I should see "Configuration Saved" flash message

  Scenario: Verify multiple configured CMS pages are accessible
    Given I proceed as the Guest
    When I go to "/test-about-us"
    Then I should see "Test About Us Page"
    And I should see "This is the about us page content."
    And Page title equals to "Test About Us Page"
    When I go to "/test-privacy-policy"
    Then I should see "Test Privacy Policy Page"
    And I should see "This is the privacy policy page content."
    And Page title equals to "Test Privacy Policy Page"

  Scenario: Remove CMS page from allowed list
    Given I proceed as the Admin
    And I go to System/Configuration
    And I follow "Commerce/Guests/Website Access" on configuration sidebar
    And I fill "System Config Form" with:
      | Allow Guest Access to Landing Pages | Test Privacy Policy Page |
    When I save form
    Then I should see "Configuration Saved" flash message

  Scenario: Verify removed CMS page is no longer accessible
    Given I proceed as the Guest
    When I go to "/test-about-us"
    Then I should be on Customer User Login page
    When I go to "/test-privacy-policy"
    Then I should see "Test Privacy Policy Page"
    And I should see "This is the privacy policy page content."
    And Page title equals to "Test Privacy Policy Page"
