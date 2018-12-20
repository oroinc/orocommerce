@regression
@fixture-OroProductBundle:product-quantity-negative.yml
@fixture-OroLocaleBundle:GermanLocalization.yml

Feature: Comma support for quantity on backend
  Switching locales in config should change decimal separator for quantity from dot to comma

  Scenario: Feature Background
    Given I enable the existing localizations

  Scenario: Check quantity decimal separator for default locale
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I click "NewCategory"
    When I type "3." in "ProductQuantityField"
    Then ProductQuantityField field should has 3.0000 value
    When I type "3," in "ProductQuantityField"
    Then ProductQuantityField field should has 3 value

  Scenario: Check quantity decimal separator for different locale
    Given I click "Localization Switcher"
    And I select "German Localization" localization
    And I click "NewCategory"
    When I type "3," in "ProductQuantityField"
    Then ProductQuantityField field should has 3,0000 value
    When I type "3." in "ProductQuantityField"
    Then ProductQuantityField field should has 3 value
