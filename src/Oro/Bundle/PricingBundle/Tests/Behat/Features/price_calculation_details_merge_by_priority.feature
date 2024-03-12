@regression
@pricing-storage-combined
@ticket-BB-22993
@fixture-OroPricingBundle:PriceCalculationDetails.yml

Feature: Price Calculation Details Merge by Priority

  Scenario: Switch pricing strategy
    Given I login as administrator
    When I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    And I fill "PriceSelectionStrategyForm" with:
      | Use Default          | false             |
      | Pricing Strategy     | Merge by priority |
    And I submit form
    Then I should see "Configuration saved" flash message
    And I recalculate combined prices

  Scenario: Check Price Calculation Details Grid
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
      | PSKU1 | Each 1:  $9.00 10:  $9.00 100:  $8.00 |

  Scenario: Check Price Calculation Details View page General Section
    When click on PSKU1 in grid
    Then I should see "Website Default"
    And I should see "Customer Company A"
    And I should see "Product Product 1"
    And I should see "Pricing Strategy Merge by priority"
    And I should see next prices for "Customer Prices":
      | Each (USD) |
      | 1 $9.00    |
      | 10 $9.00   |
      | 100 $8.00  |

  Scenario: Check Price Calculation Details View page Price Merge Details Section
    And I should see next prices for "PL2":
      | Each (USD) |
      | 1 $9.00    |
      | 10 $9.00   |
    And should see next prices selected for "PL2":
      | Each (USD) |
      | 1 $9.00    |
      | 10 $9.00   |

    And I should see next prices for "PL3":
      | Each (USD) |
      | 1 $8.00    |
      | 100 $8.00  |
    And should see next prices selected for "PL3":
      | Each (USD) |
      | 100 $8.00  |

    And I should see next prices for "PL1":
      | Each (USD) |
      | 1 $10.00   |
