@regression
@ticket-BB-16902
@fixture-OroProductBundle:products.yml
Feature: Order View Permissions
  In order to restrict order view by a customer
  As administrator
  I need to be able to set local permission for Order view

  Scenario: Create order without customer user assigned
    Given I login as administrator
    And I go to Sales/Orders
    And I click "Create Order"
    And click "Add Product"
    And fill "Order Form" with:
      | Customer | first customer |
      | Product  | PSKU1          |
      | Price    | 50             |
    When I click "Save and Close"
    And agree that shipping cost may have changed
    Then I should see "Order has been saved" flash message

  Scenario: Change Administrator role's Order view permission to Department level access
    When I go to Customers/Customer User Roles
    When I click Edit Administrator in grid
    And select following permissions:
      | Order | View:Department (Same Level) |
    And save and close form
    Then I should see "Customer User Role has been saved" flash message

  Scenario: Check that Order can be viewed by frontend user
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I click "Orders"
    And I click "view" on first row in "Past Orders Grid"
    Then I should see "Order #1"
