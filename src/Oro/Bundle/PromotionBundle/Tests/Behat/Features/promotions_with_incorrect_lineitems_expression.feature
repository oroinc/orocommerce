@regression
@ticket-BB-21416
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:shopping_list_for_promotion_with_bad_expression.yml

Feature: Promotions with incorrect lineItems expression
  In order to make sure that incorrect promotion rule expression doesn't leads to storefront errors by incorrect array access in promotion expression
  As an administrator
  I need to have ability to create promotion with incorrect rule expression and be sure that storefront works without errors

  Scenario: Check there are no errors on the shopping list page on storefront
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    Then I should see "Product 1"
    And I should see "Product 2"

  Scenario: Check if checkout can be started and finished
    When I click on "Create Order"
    And I should see "Checkout"
    Then I click "Ship to this address"
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    And I should see "Product 1"
    And I should see "Product 2"
    When I scroll to bottom
    And I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"
