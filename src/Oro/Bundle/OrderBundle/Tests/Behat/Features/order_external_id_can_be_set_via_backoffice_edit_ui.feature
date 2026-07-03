@regression
@ticket-BB-27515
@fixture-OroOrderBundle:order.yml

Feature: Order External ID can be set via back-office edit UI

  Scenario: Setting a unique External ID on an order that has none saves without error
    Given I login as administrator
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
