@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroCheckoutBundle:CheckoutCustomerWithoutAddressesFixture.yml
@fixture-OroCheckoutBundle:CheckoutProductFixture.yml
@fixture-OroCheckoutBundle:CheckoutShoppingListFixture.yml

Feature: Single Page Checkout New Address Without Existing Addresses
  In order to complete checkout as a customer without saved addresses
  As a Buyer
  I should be able to create a new address on Single Page Checkout and see it in the address section

  Scenario: Feature Background
    Given I activate "Single Page Checkout" workflow

  Scenario: Create new billing address on Single Page Checkout
    Given AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And I click on "Single Checkout Page Add New Billing Address"
    And I fill "New Address Popup Form" with:
      | First Name      | Amanda          |
      | Last Name       | Cole            |
      | Street          | 801 Scenic Hwy  |
      | City            | Haines City     |
      | Country         | United States   |
      | State           | Florida         |
      | Zip/Postal Code | 33844           |
    And I click "Add Address" in modal window
    And I wait until all blocks on one step checkout page are reloaded
    Then I should see "New address (Amanda Cole, 801 Scenic Hwy, HAINES CITY FL US 33844)" for "Select Single Page Checkout Billing Address" select

  Scenario: Create new shipping address on Single Page Checkout
    Given I click on "Single Checkout Page Add New Shipping Address"
    And I fill "New Address Popup Form" with:
      | First Name      | Amanda            |
      | Last Name       | Cole              |
      | Street          | 900 Commerce Blvd |
      | City            | Haines City       |
      | Country         | United States     |
      | State           | Florida           |
      | Zip/Postal Code | 33844             |
    And I click "Add Address" in modal window
    And I wait until all blocks on one step checkout page are reloaded
    Then I should see "New address (Amanda Cole, 900 Commerce Blvd, HAINES CITY FL US 33844)" for "Select Single Page Checkout Shipping Address" select

  Scenario: Complete checkout with new addresses
    Given I check "Flat Rate" on the checkout page
    And I check "Payment Terms" on the checkout page
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
