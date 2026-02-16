@fixture-OroOrderBundle:OrderWithSubtotalAndTotal.yml

Feature: Reorder
  In order to manage orders
  As an Administrator
  I should be able to re-order existing orders at the backoffice

  Scenario: Re-order existing order from the view page
    Given I login as administrator
    And I go to Sales/Orders
    And I click view SecondOrder in grid
    When I click "More actions"
    And I click "Re-order"
    Then I should see "Re-order based on Order #"
    When I save and close form
    Then I should see "Order has been saved" flash message
    And I should see "Subtotal $50.00"
    And I should see "Customer first customer"
    And I should see "Product1"

  Scenario: Re-order existing order from the grid
    Given I go to Sales/Orders
    When I click "Re-order" on row "SimpleOrder" in grid
    Then I should see "Re-order based on Order #"
    When I save and close form
    Then I should see "Order has been saved" flash message
    And I should see "Subtotal $50.00"
    And I should see "Product1"

  Scenario: Check that the system have 4 orders
    When I go to Sales/Orders
    Then number of records should be 4
