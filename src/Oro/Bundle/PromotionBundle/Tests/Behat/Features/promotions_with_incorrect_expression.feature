@regression
@ticket-BB-17631
@ticket-BB-17557
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:promotions.yml
@fixture-OroPromotionBundle:shopping_list.yml

Feature: Promotions with incorrect expression
  In order to make sure that incorrect promotion rule expression doesn't leads to storefront errors
  As an administrator
  I need to have ability to create promotion with rule and be sure that storefront works without errors

  Scenario: Create a promotion with incorrect rule expression
    Given I login as administrator
    And I go to Marketing / Promotions / Promotions
    And I click edit line Item Discount Promotion in grid
    And I click "Show"
    And I fill "Promotion Form" with:
      | Expression | lineItem[1].quantity > 2 |
    When I save form
    Then I should see "Promotion has been saved" flash message

  Scenario: Check there is no errors on storefront
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    Then I should not see "Undefined index"
