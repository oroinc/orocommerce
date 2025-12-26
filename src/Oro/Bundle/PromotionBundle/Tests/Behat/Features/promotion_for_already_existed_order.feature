@regression
@fixture-OroPromotionBundle:order_without_promotion.yml

Feature: Promotion for already existed order
  In order to be sure that new promotion not applied to already existed order
  As an Administrator
  I need to be sure that after order completed we don't apply new promotion

  Scenario: Create different window session
    Given sessions active:
      | Admin   |first_session |
      | Buyer   |second_session|

  Scenario: Create promotion with fixed order discount
    Given I proceed as the Admin
    And I login as administrator
    When I go to Marketing / Promotions / Promotions
    And I click "Create Promotion"
    When I fill "Promotion Form" with:
      | Name            | PR1  |
      | Enabled         | 1    |
      | Sort Order      | 1    |
      | Discount Value  | 10.0 |
      | Currency        | $    |
    And I press "Add" in "Items To Discount" section
    And I check AA1 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    And I save form
    Then I should see "Promotion has been saved" flash message

  Scenario: Check order that promotion wasn't applied on admin order view page
    When I go to Sales/Orders
    And click view SimpleOrder in grid
    When I click "Totals"
    Then I should see "Subtotal $50"
    And I should see "Total $50"
    And I should not see "Discount -$10"

  Scenario:  Check order that promotion wasn't applied on client order view page
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I follow "Account"
    And I click "Order History"
    When I click view SimpleOrder in "Past Orders Grid"
    Then I should see "Subtotal $50.00" in the "Subtotals" element
    And I should not see "Discount -$10.00" in the "Subtotals" element
    And I should see "Total $50.00" in the "Subtotals" element
