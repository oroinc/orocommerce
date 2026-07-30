@regression
@pricing-storage-combined
@ticket-BB-26190
@fixture-OroPricingBundle:PriceCalculationDetailsSingleCurrency.yml

Feature: Price Calculation Details Single Currency

  Scenario: Prepare instance. Leave only one currency
    Given I login as administrator

    When I go to System / User Management / Organizations
    And click Configuration "Oro" in grid
    And I follow "System Configuration/General Setup/Currency" on configuration sidebar
    And uncheck "Use System" for "Allowed Currencies" field
    And I click "Delete currency EUR"
    And I confirm deletion
    And click "Save settings"
    Then should see "Configuration saved" flash message

    When I go to System / Configuration
    When I follow "System Configuration/General Setup/Currency" on configuration sidebar
    And I click "Delete currency EUR"
    And I confirm deletion
    And click "Save settings"
    Then should see "Configuration saved" flash message

    When I follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "Pricing Form" with:
      | Enabled Currencies | [US Dollar ($)] |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Price column is shown after changing sidebar filters with a single currency
    When I go to Sales / Price Calculation Details
    And I filter SKU as Contains "PSKU1"
    Then I should see following grid:
      | SKU   | Price (USD)     |
      | PSKU1 | Each 1:  $10.00 |

    When fill "Price Calculation Details Grid Sidebar" with:
      | Website  | Default   |
      | Customer | Company A |
    Then I should see following grid:
      | SKU   | Price (USD)                           |
      | PSKU1 | Each 1:  $8.00 10:  $9.00 100:  $8.00 |

  Scenario: Price column is shown after page refresh
    When I reload the page
    Then I should see following grid:
      | SKU   | Price (USD)                           |
      | PSKU1 | Each 1:  $8.00 10:  $9.00 100:  $8.00 |
