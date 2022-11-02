@regression
@fixture-OroPaymentTermBundle:PaymentTermActivity.yml
# Unskip when CRM-9209 will be fixed
@skip
Feature:
  To make sure that the activity can be added and removed correctly
  As an administrator
  I add an activity to entity and remove it from the activity list

  Scenario: Add note activity to payment term
    Given I login as administrator
    And go to Sales/ Payment terms
    And I click view "Payment Term" in grid
    When I click "Add note"
    And I fill form with:
      | Message | Activity message                                       |
      | Context | [Payment term (Payment Term), All Products (Category)] |
    And I click "Add Note Button"
    Then I should see "Note saved" flash message
    And I should see "Activity message" note in activity list

  Scenario: Remove context from activity list
    Given I click on "First Activity Item"
    When I press "Delete Payment Term Activity"
    Then I should see "The context has been removed" flash message
