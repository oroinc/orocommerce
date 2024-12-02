@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:DefaultCheckoutFromShoppingList.yml

Feature: Default Checkout With Custom Addresses

  Scenario: Feature Background
    Given There is USD currency in the system configuration
    And AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend

  Scenario: Create order from Shopping List 1
    Given I open page with shopping list List 1
    When I click "Create Order"
    Then the "Ship to this address" checkbox should not be checked

  Scenario: Check that "Ship to this address" checkbox should not be checked after closing ui dialog
    Given I click "Add Address"
    Then I should see an "Ship to This Address Modal Checkbox" element
    And The "Ship to This Address Modal Checkbox" checkbox should be checked
    When I close ui dialog
    Then the "Ship to this address" checkbox should not be checked

  Scenario: Add a billing custom address
    Given I click "Add Address"
    When I click "Continue" in modal window
    Then I should see "First Name and Last Name or Organization should not be blank"
    And I fill "New Address Popup Form" with:
      | Label       | Billing address |
      | First Name  | Amanda          |
      | Last Name   | Cole            |
      | Street      | Billing street  |
      | City        | Berlin          |
      | Country     | Germany         |
      | State       | Berlin          |
      | Postal Code | 10115           |
    And I uncheck "Ship to This Address Modal Checkbox" element
    When I click "Continue" in modal window
    Then the "Ship to this address" checkbox should not be checked
    And I click "Add Address"
    When I check "Ship to This Address Modal Checkbox" element
    And I click "Continue" in modal window
    Then the "Ship to this address" checkbox should be checked
    And I press "Continue"

  Scenario: Add a shipping custom address and finish checkout
    Given on the "Shipping Method" checkout step I go back to "Edit Shipping Information"
    And the "Use billing address" checkbox should be checked
    And I click "Add Address"
    When I fill "New Address Popup Form" with:
      | Label        | Shipping address |
      | First Name   | Amanda           |
      | Last Name    | Cole             |
      | Street       | Shipping street  |
      | City         | Berlin           |
      | Country      | Germany          |
      | State        | Berlin           |
      | Postal Code  | 10115            |
    And I click "Continue" in modal window
    Then the "Use billing address" checkbox should not be checked
    And I should see "New address (Amanda Cole, Shipping street, 10115 Berlin, Germany)"
    And I press "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And on the "Payment" checkout step I press Continue
    And I press "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
