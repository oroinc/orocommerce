@regression
@fixture-OroProductBundle:product-quantity-negative.yml

Feature: Сomma support for quantity on backend
  Switching locales in config should change decimal separator for quantity from dot to comma

  Scenario: Create sessions
    Given sessions active:
      | User  | first_session  |
      | Admin | second_session |

  Scenario: Check quantity decimal separator for default locale
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "NewCategory"
    And I type "3." in "ProductQuantityField"
    Then ProductQuantityField field should has 3.0000 value
    And I type "3," in "ProductQuantityField"
    Then ProductQuantityField field should has 3 value

  Scenario: Check quantity decimal separator for different locale
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use default" for "Locale" field
    And I fill in "Locale" with "Ukrainian"
    And I save form
    Then I should see "Configuration saved" flash message
    Given I proceed as the User
    And I click "NewCategory"
    And I type "3," in "ProductQuantityField"
    Then ProductQuantityField field should has 3,0000 value
    And I type "3." in "ProductQuantityField"
    Then ProductQuantityField field should has 3 value
