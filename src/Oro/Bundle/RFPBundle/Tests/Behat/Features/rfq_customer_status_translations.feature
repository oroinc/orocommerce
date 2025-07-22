@regression
@fixture-OroShoppingListBundle:ShoppingListWithProductPriceFixture.yml
@fixture-OroLocaleBundle:LocalizationFixture.yml
@ticket-BB-15482
Feature: RFQ customer status translations
  In order to use rfq grids in different locales
  As a user
  I want to see customer status fields with appropriate translations

  Scenario: Create different window session
    Given sessions active:
      | User  | first_session  |
      | Admin | second_session |

  Scenario: Configure localizations and prepare translations for customer status field
    Given I proceed as the Admin
    And I login as administrator
    When I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English (United States), German Localization] |
      | Default Localization  | German Localization                            |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I go to System / Entities / Entity Management
    And filter Name as is equal to "Request"
    And I click view Request For Quote in grid
    And I click edit customer_status in grid
    And I fill "Entity Config Form" with:
      | Option First | Submitted_de |
    And I save form
    Then I should see "Field saved" flash message

  Scenario: Create RFQ and check status field translations
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list "Shopping List 1"
    And I click "Request Quote"
    And I fill form with:
      | First Name    | Amanda                  |
      | Last Name     | Cole                    |
      | Email Address | AmandaRCole@example.org |
      | Company       | Red Fox Tavern          |
      | PO Number     | Test RFQ                |
    And I click "Submit Request"
    And click on "Flash Message Close Button"
    And I click "Account Dropdown"
    And I click "Requests For Quote"
    Then I should see following grid:
      | PO Number | Status       |
      | Test RFQ  | Submitted_de |
    And I select "English (United States)" localization
    Then I should see following grid:
      | PO Number | Status    |
      | Test RFQ  | Submitted |

  Scenario: Check RFQ customer status field on backend
    Given I proceed as the Admin
    When I go to Sales / Requests For Quote
    And I show column Customer Status in grid
    Then I should see following grid:
      | PO Number | Customer Status |
      | Test RFQ  | Submitted_de    |
    When I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Default Localization | English (United States) |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I go to Sales / Requests For Quote
    And I show column Customer Status in grid
    Then I should see following grid:
      | PO Number | Customer Status |
      | Test RFQ  | Submitted       |

    # Check grid translation again to make sure that the cache works correctly for different languages
    When I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Default Localization | German Localization |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I go to Sales / Requests For Quote
    And I show column Customer Status in grid
    Then I should see following grid:
      | PO Number | Customer Status |
      | Test RFQ  | Submitted_de    |
