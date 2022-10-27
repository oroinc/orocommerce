@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:promotions.yml
@fixture-OroPromotionBundle:shopping_list.yml
Feature: Changes of Promotion not affect Order
  In order to be able to find out applied discounts for past order
  As an site user
  I need to have ability to see applied discounts for order, it shouldn't be affected by changes in related promotions

  Scenario: Check that order view has all needed information about promotion
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I login as administrator and use in "second_session" as "Admin"
    And I disable inventory management
    And I proceed as the Buyer
    And I do the order through completion, and should be on order view page
    When I click on "FrontendGridColumnManagerButton"
    And I click "Select All"
    And I click on empty space
    Then I should see following "Order Line Items Grid" grid:
      | Product                | RTDA  | RTADIT | RTADET |
      | Product 1 Item #: SKU1 | $0.00 | $10.00 | $10.00 |
      | Product 2 Item #: SKU2 | $5.00 | $5.00  | $5.00  |

  Scenario: Check that promotion change not affect past orders
    Given I operate as the Admin
    And I go to Marketing / Promotions / Promotions
    And I click Edit line Item Discount Promotion in grid
    And I fill "Promotion Form" with:
      | Discount Value (%) | 0 |
    And I save form

    # at back-office
    When I go to Sales / Orders
    And I click "edit" on first row in grid
    Then I see next line item discounts for backoffice order:
      | SKU  | Row Total Incl Tax | Row Total Excl Tax | Discount |
      | SKU1 | $10.00             | $10.00             | $0.00    |
      | SKU2 | $5.00              | $5.00              | $5.00    |

    # at front-office
    When I proceed as the Buyer
    And I open Order History page on the store frontend
    And I click "view" on first row in "Past Orders Grid"
    And I show column "Row Total (Discount Amount)" in "Order Line Items Grid" frontend grid
    Then I should see following "Order Line Items Grid" grid:
      | Product                | RTDA  |
      | Product 1 Item #: SKU1 | $0.00 |
      | Product 2 Item #: SKU2 | $5.00 |
