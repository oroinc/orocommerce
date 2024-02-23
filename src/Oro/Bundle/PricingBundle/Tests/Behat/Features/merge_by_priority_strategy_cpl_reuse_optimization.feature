@regression
@pricing-storage-combined
@ticket-BB-21683
@fixture-OroPricingBundle:MergeStrategyProductPrices.yml

Feature: Merge by Priority Strategy CPL Reuse Optimization

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Switch Pricing Strategy to Merge by priority
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    When uncheck "Use default" for "Pricing Strategy" field
    And I fill form with:
      | Pricing Strategy | Merge by priority |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Configure customer price lists chain PL1, PL2
    Given I proceed as the Admin
    When I go to Customers/Customers
    And click edit "Company A" in grid
    And I fill form with:
      | Fallback    | Current customer only |
    When I click "Add Price List"
    And I choose Price List "PL1" in 1 row
    And I check "Merge Allowed In Row 1" element
    And I choose Price List "PL2" in 2 row
    And I check "Merge Allowed In Row 2" element
    And I submit form
    Then I should see "Customer has been saved" flash message

  Scenario: Check that product have expected for Customer and PL1, PL2
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I type "PSKU1" in "search"
    And I click "Product 1"
    Then should see "1+ $10.00"
    And should see "10+ $9.00"

  Scenario: Configure customer price lists chain PL1, PL2, PL3
    Given I proceed as the Admin
    When I click "Add Price List"
    And I choose Price List "PL3" in 3 row
    And I check "Merge Allowed In Row 3" element
    And I submit form
    Then I should see "Customer has been saved" flash message

  Scenario: Check that product have expected for Customer and PL1, PL2, PL3
    Given I proceed as the Buyer
    When I reload the page
    Then should see "1+ $10.00"
    And should see "10+ $9.00"
    And should see "100+ $8.00"

  Scenario: Configure customer price lists chain PL3, PL1, PL2
    Given I proceed as the Admin
    When I drag 3 row to the top in "Price List" table
    And I submit form
    Then I should see "Customer has been saved" flash message

  Scenario: Check that product have expected for Customer and PL3, PL1, PL2
    Given I proceed as the Buyer
    When I reload the page
    Then should see "1+ $8.00"
    And should see "10+ $9.00"
    And should see "100+ $8.00"

  Scenario: Configure customer price lists chain PL3 (merge disallowed), PL1, PL2
    Given I proceed as the Admin
    When I uncheck "Merge Allowed In Row 1" element
    And I submit form
    Then I should see "Customer has been saved" flash message

  Scenario: Check that product have expected for Customer and PL3 (merge disallowed), PL1, PL2
    Given I proceed as the Buyer
    When I reload the page
    Then should see "1+ $8.00"
    And should see "100+ $8.00"
    And should not see "10+ $9.00"

  Scenario: Configure customer price lists chain PL1, PL2, PL3 (merge disallowed)
    Given I proceed as the Admin
    When I drag 3 row to the top in "Price List" table
    When I drag 3 row to the top in "Price List" table
    And I submit form
    Then I should see "Customer has been saved" flash message

  Scenario: Check that product have expected for Customer and PL1, PL2, PL3 (merge disallowed)
    Given I proceed as the Buyer
    When I reload the page
    Then should see "1+ $10.00"
    And should see "10+ $9.00"
    And should not see "100+ $8.00"
