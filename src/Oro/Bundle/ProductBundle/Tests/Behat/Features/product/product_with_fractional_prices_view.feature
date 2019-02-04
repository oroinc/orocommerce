@ticket-BB-14800
@fixture-OroLocaleBundle:GermanLocalization.yml
@fixture-OroShoppingListBundle:ShoppingListWithFractionalPriceFixture.yml

Feature: Product with fractional prices view
  In order to use correct decimal separator for fractional prices in different locales
  As an Buyer
    I should see fractional prices formatted according locale settings on Product view page
    I should see prices and price attributes formatted according to locale settings on Product view page in backoffice

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

  Scenario: Prices on product view page in backoffice are formatted according to locale settings
    Given I login as administrator
    And I go to Products/ Products
    And I click View "PSKU1" in grid
    And I sort "ProductPricesGrid" by "Quantity"
    Then I should see following "ProductPricesGrid" grid:
      | Price List          | Quantity | Unit | Value | Currency |
      | priceListForWebsite | 1        | each | 12.99 | USD      |
      | priceListForWebsite | 100      | each | 10.99 | USD      |

    And I go to System/Configuration
    And follow "System Configuration/General Setup/Localization" on configuration sidebar
    When fill "Configuration Localization Form" with:
      | Enabled Localizations | German_Loc |
      | Default Localization  | German_Loc |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

    And I go to Products/ Products
    When I click View "PSKU1" in grid
    And I sort "ProductPricesGrid" by "Quantity"
    Then I should see following "ProductPricesGrid" grid:
      | Price List          | Quantity | Unit | Value | Currency |
      | priceListForWebsite | 1        | each | 12,99 | USD      |
      | priceListForWebsite | 100      | each | 10,99 | USD      |

  Scenario: Table view of price attributes on product view page in backoffice is formatted according to locale settings
    Given I go to Products/ Price Attributes
    And I click "Create Price Attribute"
    And I fill form with:
      | Name       | MSRP |
      | Field Name | msrp |
      | Currencies | USD  |
    And I save and close form
    Then I should see "Price Attribute has been saved" flash message

    And I go to Products/ Products
    And click edit "PSKU1" in grid
    And I add price 10,88 to Price Attribute MSRP
    When I save and close form
    Then I should see "Each 10,88 $" in the "Product Price Attributes Table" element

  Scenario: Grid view of price attributes on product view page in backoffice is formatted according to locale settings
    And go to System/ Configuration
    And follow "System Configuration/General Setup/Currency" on configuration sidebar
    And fill "Currency Form" with:
      | Allowed Currencies | GBP |
    And click "Add"
    And type "1" in "Rate From 1"
    And click on empty space
    And type "1" in "Rate To 1"
    And click on empty space
    When click "Save settings"
    Then I should see "Configuration saved" flash message

    And I go to Products/ Price Attributes
    And I click Edit "MSRP" in grid
    And I fill form with:
      | Currencies | [USD, EUR, GBP] |
    When I save and close form
    Then I should see "Price Attribute has been saved" flash message

    And I go to Products/ Products
    When I click View "PSKU1" in grid
    Then I should see following "Product Price Attributes Grid 1" grid:
      | Unit | EUR | GBP | USD   |
      | each | N/A | N/A | 10,88 |
