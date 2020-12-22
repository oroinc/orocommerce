@ticket-BB-19757
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:promotions.yml
@fixture-OroPromotionBundle:shopping_list.yml

Feature: Promotions in Shopping List when owner is changed
  In order to check discount on frontstore shopping list for specific owner
  As a customer user
  I want to check applied discounts at shopping list for different owners

  Scenario: Create different window session
  Given sessions active:
    | Admin | first_session  |
    | Buyer | second_session |

  Scenario: Create a promotion with rule expression
    Given I login as administrator
    And I go to Marketing / Promotions / Promotions
    And I click edit order Discount Promotion in grid
    And I click "Show"
    And I fill "Promotion Form" with:
      | Type           | amount              |
      | Discount Value | 10                  |
      | Currency       | USD                 |
      | Expression     | customerUser.id = 1 |
    When I save form
    Then I should see "Promotion has been saved" flash message

  Scenario: Assign List 2 to customer user Amanda Cole with buyer role
    Given I proceed as the Buyer
    And I login as NancyJSallee@example.org buyer
    And I open page with shopping list List 2
    And I see next subtotals for "Shopping List":
      | Subtotal | Amount  |
      | Discount | -$5.00 |
    When I fill "ShoppingListOwnerForm" with:
      | Customer | Amanda Cole |
    And I should see 'Shopping list "List 2" was updated successfully' flash message
    Then I see next subtotals for "Shopping List":
      | Subtotal | Amount  |
      | Discount | -$15.00 |
