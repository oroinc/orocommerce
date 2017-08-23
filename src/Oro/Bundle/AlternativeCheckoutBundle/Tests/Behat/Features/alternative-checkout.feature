@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroAlternativeCheckoutBundle:AlternativeCheckout.yml
@fixture-OroWarehouseBundle:Checkout.yml
Feature: Alternative Checkout workflow threshold
  In order to create order on front store
  As a buyer
  I want to start and request approval of alternative checkout

  Scenario: Create different window session
      Given sessions active:
        | User  |first_session |
        | Admin |second_session|

  Scenario: Activate Alternative Checkout workflow
    Given I proceed as the Admin
    And I login as administrator
    When I go to System/ Workflows
    And I click Activate Alternative Checkout in grid
    And I press "Activate"
    Then I should see "Workflow activated" flash message
    And click Logout in user menu

  Scenario: Create order with Alternative Checkout with threshold
    Given I proceed as the User
    And There is EUR currency in the system configuration
    And I enable the existing warehouses
    And MarleneSBradley@example.org customer user has Buyer role
    And I signed in as MarleneSBradley@example.org on the store frontend
    When I open page with shopping list List Threshold
    And I press "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I check "Delete this shopping list after submitting order" on the "Order Review" checkout step and press Request Approval
    Then I should see "You exceeded the allowable amount of $5000."
    When I press "Request Approval"
    Then I should see "Pending approval"
    And I proceed as the Admin
    When I signed in as NancyJSallee@example.org on the store frontend
    And click "Account"
    And click "Order History"
    And click "Check Out" on row "List Threshold" in grid
    And click "Approve Order"
    And I proceed as the User
    And reload the page
    Then I should see "Approved at"
    And click "Submit Order"
    And I see the "Thank You" page with "Thank You For Your Purchase!" title

