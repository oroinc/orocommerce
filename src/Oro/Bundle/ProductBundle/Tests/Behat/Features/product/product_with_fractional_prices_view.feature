@ticket-BB-14800
@fixture-OroLocaleBundle:GermanLocalization.yml
@fixture-OroShoppingListBundle:ShoppingListWithFractionalPriceFixture.yml

Feature: Product with fractional prices view
  In order to use correct decimal separator for fractional prices in different locales
  As an Buyer
    I should see fractional prices formatted according locale settings on Product view page

  Scenario: Feature Background
    Given There is USD currency in the system configuration
    And I enable the existing localizations
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on the homepage
    And I click "Localization Switcher"
    And I select "German Localization" localization

  Scenario: Search product by SKU
    When type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "Product 1"
    And I should see "12,99 $" in the "Product Price Main" element
    And I should see "12,99 $" in the "Product Price Listed" element

  Scenario: Filter product by fractional price
    When I filter Price as equals or more than "10,55"
    Then should see filter hints in frontend grid:
      | Price: equals or more than 10,55 / ea |
    And I should see "Product 1"
    And I should see "12,99 $" in the "Product Price Main" element
    And I should see "12,99 $" in the "Product Price Listed" element

  Scenario: Prices on product view page formatted according locale settings
    When I click "Product 1"
    Then I should see "Product 1"
    And I should see an "Default Page Prices" element
    And I should see "1 12,99 $" in the "Default Page Prices" element
    And I should see "100 10,99 $" in the "Default Page Prices" element
