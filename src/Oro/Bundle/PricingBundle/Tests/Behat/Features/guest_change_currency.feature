@ticket-BB-14857
Feature: Guest Change Currency
  In order to operate main site functionality
  As a guest buyer
  I want to have ability to change the currency

  Scenario: Feature Background
    Given I disable configuration options:
      | oro_frontend.guest_access_enabled |

  Scenario: Enable required currencies
    Given I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    When fill "Pricing Form" with:
      | Enabled Currencies System | false                     |
      | Enabled Currencies        | [US Dollar ($), Euro (€)] |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Trying to change currency to EUR
    Given I am on homepage
    Then I should see that "Currency Switcher Button" contains "$"
    When I change currency in currency switcher to "Euro"
    Then I should see that "Currency Switcher Button" contains "€"
