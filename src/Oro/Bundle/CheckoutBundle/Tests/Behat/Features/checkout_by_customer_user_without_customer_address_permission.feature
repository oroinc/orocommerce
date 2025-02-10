@regression
@ticket-BB-25043
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml

Feature: Checkout by customer user without customer address permission
  In order to create order from Shopping List on front store
  As a buyer
  I want to be able to create order and save new address without customer address permission

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Set permission to create user address
    Given I login as administrator
    And I go to Customers/Customer User Roles
    When click Edit Buyer in grid
    And select following permissions:
      | Customer Address      | View:None       | Create:None       | Edit:None      | Delete:None | Assign:None |
      | Customer User Address | View:User (Own) | Create:User (Own) | Edit:User (Own)| Delete:None | Assign:None |
    And I save and close form
    Then I should see "Customer User Role has been saved" flash message

  Scenario: Create order for customer
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on homepage
    And I open page with shopping list List 1
    And I click "Create Order"
    And I click "Add Address"
    When I fill "New Address Popup Form" with:
      | Label       | Billing address |
      | First Name  | Amanda          |
      | Last Name   | Cole            |
      | Street      | Billing street  |
      | City        | Berlin          |
      | Country     | Germany         |
      | State       | Berlin          |
      | Postal Code | 10115           |
    Then I uncheck "Ship to This Address Modal Checkbox" element
    And I should see "Save address"
    Then I check "Save address"
    When I click "Add Address" in modal window
    And I click "Continue"
    And I click "Add Address"
    And I fill "New Address Popup Form" with:
      | Label       | Shipping address |
      | First Name  | Amanda           |
      | Last Name   | Cole             |
      | Street      | Shipping street  |
      | City        | Berlin           |
      | Country     | Germany          |
      | State       | Berlin           |
      | Postal Code | 10115            |
    Then I should see "Save address"
    And I check "Save address"
    When I click "Add Address" in modal window
    And I click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I check "Delete this shopping list after ordering" on the "Order Review" checkout step and press Submit Order
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And I click "Account Dropdown"
    And I click "Address Book"
    Then I should see "Billing street"
    And I should see "Shipping street"
