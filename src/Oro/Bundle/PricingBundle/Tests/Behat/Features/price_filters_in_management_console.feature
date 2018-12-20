@fixture-OroPricingBundle:PriceListFilter.yml
@fixture-OroSalesBundle:OpportunityWithBudgetFixture.yml
@fixture-OroOrderBundle:OrderWithSubtotalAndTotal.yml

Feature: Price filters in management console
  In order to use correct values for price filter in management console
  As an Administrator
  I want to have ability to use price filter in management console with correct decimal separator and formatting

  Scenario: Feature Background
    Given I login as administrator
    When I go to System/Configuration
    And follow "System Configuration/General Setup/Localization" on configuration sidebar
    And fill "Configuration Localization Form" with:
      | Locale Use Default | false            |
      | Locale             | German (Germany) |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Product Price filters use formatting according locale setting
    Given I go to Products/ Products
    When I filter "Price (USD)" as equals "6" use "item" unit
    Then should see filter hints in grid:
      | Price (USD): equals 6,00 / item |
    And I should see "6,00 $" in grid

    When I filter "Price (USD)" as more than "5,77" use "item" unit
    Then should see filter hints in grid:
      | Price (USD): more than 5,77 / item |
    And I should see "6,00 $" in grid

    When I filter "Price (USD)" as more than "6,11" use "item" unit
    Then should see filter hints in grid:
      | Price (USD): more than 6,11 / item |
    And I should not see "6,00 $"

  Scenario: Opportunity Price filters use formatting according locale setting
    Given I go to Sales/ Opportunities
    When I filter "Budget amount ($)" as equals "50"
    Then should see filter hints in grid:
      | Budget amount ($): equals 50,00 |
    And I should see "50,00 $" in grid

    When I filter "Budget amount ($)" as more than "49,77"
    Then should see filter hints in grid:
      | Budget amount ($): more than 49,77 |
    And I should see "50,00 $" in grid

    When I filter "Budget amount ($)" as more than "50,11"
    Then should see filter hints in grid:
      | Budget amount ($): more than 50,11 |
    And I should not see "50,00 $"

  Scenario: Order Price filters use formatting according locale setting
    Given I go to Sales/ Orders
    And I go to Sales/ Orders
    When I filter "Total ($)" as equals "50"
    Then should see filter hints in grid:
      | Total ($): equals 50,00 |
    And number of records should be 1

    When I filter "Total ($)" as more than "49,77"
    Then should see filter hints in grid:
      | Total ($): more than 49,77 |
    And number of records should be 1

    When I filter "Total ($)" as more than "50,11"
    Then should see filter hints in grid:
      | Total ($): more than 50,11 |
    And there is no records in grid
