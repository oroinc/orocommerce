@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroCheckoutBundle:CheckoutCustomerFixture.yml
@fixture-OroCheckoutBundle:CheckoutLocalizedProductFixture.yml
@fixture-OroCheckoutBundle:CheckoutShoppingListFixture.yml
@fixture-OroCheckoutBundle:CheckoutQuoteFixture.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml

Feature: Single page checkout order email template verification
  In order to check that the html comments are correct worked in the template and are edited through the WYSIWYG editor
  As a Administrator
  I am changing the email template with WYSIWYG editor

  Scenario: Feature Background
    Given I login as administrator
    And go to System/Workflows
    And I click "Activate" on row "Single Page Checkout" in grid
    And I click "Activate"

  Scenario: Changing the email template with WYSIWYG editor
    Given I go to System/ Emails/ Templates
    And I filter Template Name as Contains "order_confirmation_email"
    And I click edit "order_confirmation_email" in grid
    And I save form

  # After saving the template with the WYSIWYG editor, we must make sure that the TWIG tags are left
  Scenario: Verified email template source
    Given AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I scroll to top
    And I wait line items are initialized
    And I click "Create Order"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And Email should contains the following "PG-PA103" text
