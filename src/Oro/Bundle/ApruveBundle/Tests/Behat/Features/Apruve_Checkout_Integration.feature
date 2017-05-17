@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-Checkout.yml
@fixture-OroWarehouseBundle:Checkout.yml
Feature: Apruve Checkout Integration

  Scenario: Create two session
    Given sessions active:
      | Admin  | first_session  |
      | Apruve | second_session |

  Scenario: Create Apruve integration
    Given I proceed as the Admin
    And login as administrator
    And go to System/ Integrations/ Manage Integrations
    And click "Create Integration"
    When fill "Apruve Integration Form" with:
      | Type          | Apruve                           |
      | Name          | Apruve                           |
      | Label         | Apruve                           |
      | Short Label   | Apruve Short Label               |
      | Test Mode     | True                             |
      | API Key       | d0cbaf64fccdf9de4209895b0f8404ab |
      | Merchant ID   | 507c64f0cbcf190ce548d19e93d5c909 |
      | Status        | Active                           |
      | Default owner | John Doe                         |
    When click "Check Apruve connection"
    Then I should see "Apruve Connection is valid" flash message
    And save form
    Then should see "Integration saved" flash message
    And I should see "/admin/apruve/webhook/notify/" in Webhook Url
    And I go to System/ Payment Rules
    And click "Create Payment Rule"
    And fill "Payment Rule Form" with:
      | Enable     | true     |
      | Name       | Apruve   |
      | Sort Order | 1        |
      | Currency   | $        |
      | Method     | [Apruve] |
    When save and close form
    Then should see "Payment rule has been saved" flash message
    And click logout in user menu

  Scenario: Check out with Apruve integration
    Given I proceed as the Admin
    And Currency is set to USD
    And I enable the existing warehouses
    And AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I press "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And on the "Shipping" checkout step I press Continue
    And on the "Payment" checkout step I press Continue
    And click "Submit Order"
    When I fill "Aprove Login Form" with:
      | Email    | apruve-qa+buyer@orocommerce.com |
      | Password | wyVjpjA2                        |
    And I press "Sign In" in "Aprove Login Form"
    And I press "Confirm" in "Aprove Login Form"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And click "Sign Out"

  Scenario: Check order status in admin panel after order creation
    Given I proceed as the Admin
    And login as administrator
    And go to Sales/ Orders
    When click view "Amanda Cole" in grid
    Then I should see order with:
      | Payment Method | Apruve             |
      | Payment Status | Payment authorized |
    And click "Send Invoice" on row "Authorize" in grid "Order Payment Transaction Grid"
    And click "Yes, Charge"
    Then should see "Invoice has been sent successfully" flash message
    And I should see order with:
      | Payment Status | Invoiced |
    And I should see following "Order Payment Transaction Grid" grid:
      | Payment Method | Type      | Successful |
      | Apruve         | Shipment  | Yes        |
      | Apruve         | Invoice   | Yes        |
      | Apruve         | Authorize | Yes        |
    And click logout in user menu

#  This part should implemented only when travis will allow accept web hooks
#  Scenario: Cofirm order from Apruve
#    Given I switch to the "Apruve" session
#    And I go to "https://test.apruve.com/"
#    And fill in "user_email" with "apruve-qa+buyer@orocommerce.com"
#    And fill in "user_password" with "wyVjpjA2"
#    And click "Log in"
#    And switch to the "Invoices" tab
#    And select open invoice
#    And click "Pay"
#    And select "Paper Check" option
#    And click "Pay"
#    And should see "Thank you!"
#
#  Scenario: Check order status in admin panel after confirm from Apruve]
#    Given I proceed as the Admin
#    And login as administrator
#    And go to Sales/ Orders
#    When click view "Amanda Cole" in grid
#    Then I should see order with:
#      | Payment Method | Apruve       |
#      | Payment Status | Paid in full |
