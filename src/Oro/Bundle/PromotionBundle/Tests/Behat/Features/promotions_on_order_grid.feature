@ticket-BB-19653
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:promotions.yml
@fixture-OroPromotionBundle:shopping_list.yml
Feature: Promotions on Order grid
  In order to find out applied discounts in order
  As administrator
  I need to have ability to see applied discounts on order grid

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Disable inventory management
    Given I proceed as the Admin
    And I login as administrator
    And I disable inventory management

  Scenario: Create Order
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I do the order through completion, and should be on order view page

  Scenario: Check that applied discounts are shown on order grid
    Given I proceed as the Admin
    When I go to Sales / Orders
    And I show column Discount in grid
    Then I should see following grid:
      | Order Number | Discount |
      | 1            | $13.50   |
