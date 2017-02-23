@fixture-Pricelists.yml
Feature: Price lists must be sortable in system configuration

  Scenario: Changing Price List Priorities
    Given I login as administrator
    And I go to System/Configuration
    And I click "Commerce"
    And I click "Catalog"
    And I click "Pricing"
    Then I should not see "Priority" in price lists table
    And I should see Drag-n-Drop icon present on price list line
    When I click "Add Price List"
    And I choose a price list "first price list" in "2" row
    And I drag "2" row on top in price lists table
    And I click "Save settings"
    Then I should see "Configuration saved" flash message
    And I should see that "first price list" price list is in "1" row
