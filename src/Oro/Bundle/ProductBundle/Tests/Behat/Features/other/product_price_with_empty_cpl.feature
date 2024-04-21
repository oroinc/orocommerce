@ticket-BB-21251
@fixture-OroProductBundle:product_with_price.yml
@pricing-storage-combined
Feature: Product price with empty cpl

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Add price list restriction to guest customer group
    Given I proceed as the Admin
    And login as administrator
    And go to Customers/ Customer Groups
    And I click Edit Non-Authenticated Visitors in grid
    And fill form with:
      | Fallback | Current customer group only |
    When I save and close form
    Then I should see "Customer group has been saved" flash message

  Scenario: Check prices is not available for guest with restricted price list
    Given I proceed as the Buyer
    And I am on the homepage
    When I type "PSKU1" in "search"
    Then I should see an "Search Autocomplete" element
    And should see "PSKU1" in the "Search Autocomplete Product" element
    And should not see "$10.00" in the "Search Autocomplete Product" element

  Scenario: Edit default website fallback to current website
    Given I proceed as the Admin
    And I go to System/Websites
    And click Edit Default in grid
    And fill form with:
      | Fallback    | Current website only |
    And I submit form
    Then I should see "Website has been saved" flash message

  Scenario: Check prices is not available for guest with restricted price list
    Given I proceed as the Buyer
    And I am on the homepage
    When I type "PSKU1" in "search"
    Then I should see an "Search Autocomplete" element
    And should see "PSKU1" in the "Search Autocomplete Product" element
    And should not see "$10.00" in the "Search Autocomplete Product" element
