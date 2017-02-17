@fixture-Pricelists.yml
Feature: Price lists must be sortable in system configuration

  Scenario: System configuration should contain sortable "price lists" config page
    Given I login as administrator
    And I go to "/admin/config/system/commerce/pricing"
    Then I should see "Price Lists"
    And I should see "Priority"
    When I add price list "first price list" into price lists collection
    And I set priority "400" to price list "first price list"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message
