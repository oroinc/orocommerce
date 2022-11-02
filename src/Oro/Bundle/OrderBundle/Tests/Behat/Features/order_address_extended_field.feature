@regression
@ticket-BB-15909
@fixture-OroProductBundle:products.yml

Feature: Order Address extended field
  In order to manage addresses
  As an Administrator
  I want to have an ability to add fields to Order Address

  Scenario: Add string_field to Order Address
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And I filter Name as is equal to "OrderAddress"
    And I click View OroOrderBundle in grid
    And I click "Create field"
    And I fill form with:
      | Field Name   | test_field   |
      | Storage Type | Table column |
      | Type         | String       |
    And I click "Continue"
    And I save and close form
    Then I should see "Field saved" flash message
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Check Order create
    When I go to Sales/Orders
    And click "Create Order"
    Then I should see "test_field"
    And click "Add Product"
    And fill "Order Form" with:
      | Customer | first customer |
      | Product  | PSKU1          |
      | Price    | 50             |
    When I click "Save and Close"
    And agree that shipping cost may have changed
    Then I should see "Order has been saved" flash message
