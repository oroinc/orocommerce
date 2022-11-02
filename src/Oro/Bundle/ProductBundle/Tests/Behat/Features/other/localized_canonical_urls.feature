@regression
@ticket-BB-20393
@fixture-OroProductBundle:translatable_product.yml

Feature: Localized Canonical URLs
  In order to improve SEO for multilingual website
  As an Administrator
  I need to be able to use localized URLs as canonical ones

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Configure Canonical URL to use Direct URLs
    Given I proceed as the Admin
    And I enable the existing localizations
    When I login as administrator
    And I go to System/ Configuration
    And I follow "System Configuration/Websites/Routing" on configuration sidebar
    And uncheck "Use default" for "Canonical URL Type" field
    And I fill in "Canonical URL Type" with "Direct URL"
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Check product canonical URLs are localized
    Given I proceed as the Buyer
    And I am on the homepage
    And I click "Localization Switcher"
    When I select "Zulu" localization
    And I open product with sku "SKU2" on the store frontend
    Then Page should contain Canonical URL with URI "product_zulu2"

  Scenario: Disable localized Canonical URLs
    Given I proceed as the Admin
    And uncheck "Use default" for "Use localized canonical URLs" field
    And I uncheck "Use localized canonical URLs"
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Check product canonical URLs are not localized
    Given I proceed as the Buyer
    And I reload the page
    Then Page should contain Canonical URL with URI "product2"
