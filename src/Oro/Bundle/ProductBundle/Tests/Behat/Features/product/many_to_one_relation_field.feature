@regression
@ticket-BB-10400
Feature: Many to One relation field

  Scenario: Edit Many to One relation field to not Extended entity
    Given I login as administrator
    And I go to System/ Entities/ Entity Management
    And filter Name as is equal to "Product"
    And I click view Product in grid
    When I click Edit "taxCode" in grid
    And I save and close form
    Then I should see "Field saved" flash message
