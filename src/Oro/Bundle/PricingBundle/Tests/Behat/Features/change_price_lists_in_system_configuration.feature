@regression
@pricing-storage-combined
@ticket-BB-7811
@ticket-BB-8098
@fixture-OroPricingBundle:Pricelists.yml

Feature: Change price lists in system configuration
  In order to manage price lists
  As an Administrator
  I want to be able to change priorities and delete price lists

  Scenario: Changing Price List Priorities
    Given I login as administrator
    And I go to System/Configuration
    When I follow "Commerce/Catalog/Pricing" on configuration sidebar
    Then I should not see "Priority" in "Price List" table
    And I should see drag-n-drop icon present in "Price List" table
    When I click "Add Price List"
    And I choose Price List "first price list" in 2 row
    And I drag 2 row to the top in "Price List" table
    And I click "Save settings"
    Then I should see "Configuration saved" flash message
    And I should see that "first price list" is in 1 row

  Scenario: Delete price that not marked as default
    Given I go to Sales/ Price Lists
    When I click Delete second in grid
    Then I should see "Are you sure you would like to delete the \"second price list\" price list?"
    And I click "Cancel"

  Scenario: Delete price that marked as default
    Given I click Delete first in grid
    Then I should see "Are you sure you would like to delete the \"first price list\" price list that is used in system configuration as default?"

    When I click "Yes"
    Then I should see "Price List deleted" flash message

  Scenario: Check system configuration
    Given I go to System/Configuration
    When I follow "Commerce/Catalog/Pricing" on configuration sidebar
    Then I should see "Default Price List"
    And I should not see "first price list"
