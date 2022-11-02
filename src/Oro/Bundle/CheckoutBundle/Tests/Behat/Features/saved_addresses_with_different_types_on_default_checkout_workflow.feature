@fix-BB-12896
@fix-BB-16312
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml

Feature: Saved addresses with different types on "Default" Checkout workflow
  In order to create order on front store
  As a buyer
  I want to start "Default" checkout and use saved address with different types

  Scenario: Create order and select billing address type
    Given There is EUR currency in the system configuration
    And AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And I fill "Billing Information Form" with:
      | Billing Address | ORO, Fourth avenue, 10111 Berlin, Germany |
    Then I should not see "Ship to this address" element inside "Billing Information Form" element

  Scenario: Select billing and shipping address type
    Given I fill "Billing Information Form" with:
      | Billing Address | ORO, Fifth avenue, 10115 Berlin, Germany |
    Then I should see "Ship to this address" element inside "Billing Information Form" element

  Scenario: Check that "Ship to this address" checkbox is not visible when page is loaded with Billing address type
    Given I select "Fourth avenue, 10111 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    When on the "Shipping Information" checkout step I go back to "Edit Billing Information"
    Then I should see that option "ORO, Fourth avenue, 10111 Berlin, Germany" is selected in "Billing Address" select
    And I should not see "Ship to this address" element inside "Billing Information Form" element

  Scenario: Check that "Ship to this address" checkbox is visible when address changed to New Address
    Given I select "New address" from "Select Billing Address"
    Then I should see "Ship to this address" element inside "Billing Information Form" element

  Scenario: I check that "Ship to this address" checkbox is visible when address changed to Shipping address type
    Given I fill "Billing Information Form" with:
      | Billing Address | ORO, Fifth avenue, 10115 Berlin, Germany |
    Then I should see "Ship to this address" element inside "Billing Information Form" element

  Scenario: I can proceed with "Ship to this address" when address changed to New Address
    Given I select "Fourth avenue, 10111 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And on the "Shipping Information" checkout step I go back to "Edit Billing Information"
    And I select "New address" from "Select Billing Address"
    When I press "Continue"
    Then I should see "First Name and Last Name or Organization should not be blank"

  Scenario: Enter Shipping Address form should not be open and "Use billing address" should be checked
    Given I select "Fourth avenue, 10111 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And on the "Shipping Information" checkout step I go back to "Edit Billing Information"
    And I select "New address" from "Select Billing Address"
    And I fill "Billing Information" with:
      | Label       | Billing address 2 |
      | First Name  | Amanda            |
      | Last Name   | Cole              |
      | Street      | Billing street  2 |
      | City        | Berlin            |
      | Country     | Germany           |
      | State       | Berlin            |
      | Postal Code | 10115             |
    And I check "Ship to this address" on the "Billing Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And on the "Payment" checkout step I press Continue
    When on the "Order Review" checkout step I go back to "Edit Shipping Information"
    Then the "Use billing address" checkbox should be checked
    And I should not see "Checkout Address Fields" element inside "Shipping Information Form" element
    And I click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And on the "Payment" checkout step I press Continue
    When I uncheck "Delete this shopping list after submitting order" on the "Order Review" checkout step and press Submit Order
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: I can change Billing Address from "Customer Address" to "New Address" when Billing/Shipping addresses are already filled
    Given I open page with shopping list List 1
    And I click "Create Order"
    And I fill "Billing Information Form" with:
      | Billing Address | ORO, Fifth avenue, 10115 Berlin, Germany |
    And I click "Continue"
    And I select "New address" from "Select Shipping Address"
    And I fill "Shipping Information" with:
      | Label        | Shipping address 2 |
      | First Name   | Amanda             |
      | Last Name    | Cole               |
      | Street       | Shipping street  2 |
      | City         | Berlin             |
      | Country      | Germany            |
      | State        | Berlin             |
      | Postal Code  | 10115              |
    And I click "Continue"
    When on the "Shipping Method" checkout step I go back to "Edit Billing Information"
    And I select "New address" from "Select Billing Address"
    And I fill "Billing Information" with:
      | Label       | Billing address 3 |
      | First Name  | Amanda            |
      | Last Name   | Cole              |
      | Street      | Billing street  3 |
      | City        | Berlin            |
      | Country     | Germany           |
      | State       | Berlin            |
      | Postal Code | 10115             |
    And I check "Ship to this address" on the "Billing Information" checkout step and press Continue
    And on the "Shipping Method" checkout step I go back to "Edit Shipping Information"
    And the "Use billing address" checkbox should be checked
    And I should not see "Checkout Address Fields" element inside "Shipping Information Form" element
    And I uncheck "Use billing address" on the checkout page
    And I select "New address" from "Select Shipping Address"
    And I should see "Checkout Address Fields" element inside "Shipping Information Form" element
    And I check "Use billing address" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And on the "Payment" checkout step I press Continue
    And I uncheck "Delete this shopping list after submitting order" on the "Order Review" checkout step and press Submit Order
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
