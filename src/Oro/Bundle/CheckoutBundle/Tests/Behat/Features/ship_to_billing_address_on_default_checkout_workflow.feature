@fix-BB-16312
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroWebCatalogBundle:customer.yml
@fixture-OroCheckoutBundle:CheckoutProductFixture.yml
@fixture-OroCheckoutBundle:CheckoutShoppingListFixture.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml

Feature: Ship to Billing address on "Default" Checkout workflow
  In order to create order on front store
  As a buyer
  I want to start "Default" checkout and use "Ship to this address"

  Scenario: Customer User does not has addresses in the address book
    Given There is EUR currency in the system configuration
    And I signed in as AmandaRCole@example.org on the store frontend
    And I follow "Account"
    When I click "Address Book"
    Then number of records in "Customers Address Book Grid" grid should be 0
    And number of records in "Customer Users Address Book Grid" grid should be 0

  Scenario: "Use billing address" checkbox element should be present if Billing Address marked as "Ship to this address"
    Given I open page with shopping list List 1
    And I click "Create Order"
    And fill form with:
      | Label           | Home Address   |
      | First name      | NewFname       |
      | Last name       | NewLname       |
      | Organization    | NewOrg         |
      | Street          | Clayton St, 10 |
      | City            | San Francisco  |
      | Country         | United States  |
      | State           | California     |
      | Zip/Postal Code | 90001          |
    And I check "Ship to this address" on the "Billing Information" checkout step and press Continue
    When on the "Shipping Method" checkout step I go back to "Edit Shipping Information"
    Then I should see "Use billing address"
    And the "Use billing address" checkbox should be checked
    And I should not see "Checkout Address Fields" element inside "Shipping Information Form" element
    When I uncheck "Use billing address" on the checkout page
    Then I should see "Checkout Address Fields" element inside "Shipping Information Form" element
