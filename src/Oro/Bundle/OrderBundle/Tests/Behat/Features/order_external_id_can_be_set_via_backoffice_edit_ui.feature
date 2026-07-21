@regression
@ticket-BB-27515
@fixture-OroOrderBundle:order.yml

Feature: Order External ID can be set via back-office edit UI

  Scenario: External ID field is hidden by default
    Given I login as administrator
    And I go to Sales/Orders
    Then I shouldn't see "External ID" column in grid
    And I should not see "External ID" filter in grid
    When I click edit SimpleOrder in grid
    Then I should not see "External ID"
    When I go to Sales/Orders
    And I click view SimpleOrder in grid
    Then I should not see "External ID"

  Scenario: Enable External ID field on Order edit for
    Given I go to System/ Entities/ Entity Management
    And I filter Name as is equal to "Order"
    And I click view Order in grid
    And I click edit external_id in grid
    And I fill "Field Config Form" with:
      | Other, Show On Form | Yes |
      | Other, Show On View | Yes |
    And I save and close form
    Then I should see "Field saved" flash message

  Scenario: Setting a unique External ID on an order that has none saves without error
    When I go to Sales/Orders
    And I click edit SimpleOrder in grid
    And I fill "Order Form" with:
      | External ID | ORD-EXT-001 |
    And I click "Save and Close"
    Then I should see "Order has been saved" flash message
    And I should not see "Value for field \"External ID\" must be unique"
    And I should see "ORD-EXT-001"

  Scenario: Setting an External ID already used by another order shows a uniqueness validation error
    When I go to Sales/Orders
    And I click edit SecondOrder in grid
    And I fill "Order Form" with:
      | External ID | ORD-EXT-001 |
    And I click "Save and Close"
    Then I should see "Value for field \"External ID\" must be unique"
