@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml
@fixture-OroCheckoutBundle:CheckoutWorkflow.yml

Feature: Javascript validation on "Default" Checkout workflow
  In order to create order on front store
  As a buyer
  I want to start "Default" checkout and see validation errors

  Scenario: Check validation error
    Given There is EUR currency in the system configuration
    And AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    When Buyer is on "List 1" shopping list
    And I click "Create Order (Custom)"
    And I fill "Checkout Order Review Form" with:
      | PO Number | Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer finibus viverra ante, sit amet fringilla ipsum fringilla eu. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec vitae felis ac neque posuere egestas. |
    And I click "Continue"
    And I should see "Checkout Order Review Form" validation errors:
      | PO Number | This value is too long. It should have 255 characters or less.  |
      | Notes     | This value should not be blank.                                 |

  Scenario: Check validation without error
    Given Buyer is on "List 1" shopping list
    And I click "Create Order (Custom)"
    And I fill "Checkout Order Review Form" with:
      | Notes | Customer test note |
    And I click "Continue"
    Then I should not see "This value should not be blank."
    And I should see "Thank You For Your Purchase!"
