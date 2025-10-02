@feature-BB-26056
@fixture-OroProductBundle:product_with_price.yml

Feature: Validation On Quick Order Form
  Scenario: Add new simple product
    Given I login as AmandaRCole@example.org buyer
    When I click "Quick Order"
    And I fill "Quick Order Form" with:
      | SKU1 | PSKU1 |
    And I wait for products to load
    Then "Quick Order Form" must contains values:
      | SKU1      | PSKU1 - Product1 |
      | QTY1      | 1                |
      | UNIT1     | item             |
      | SUBTOTAL1 | $10.00           |

  Scenario: Change quantity to very large number
    When I type "1000000" in "Quick Order Form > QTY1"
    And I click on empty space
    And I wait for "PSKU1" price recalculation
    Then I should see that "Quick Add Form Validation Row1 Warning" contains "You cannot order more than 100000 units"

  Scenario: Attempt to create order - should fail with item error and flash message
    When I click "Create Order"
    Then I should see "Some selected items need a quick review. Update or remove them to proceed to checkout." flash message

  Scenario: Attempt to add item with invalid quantity
    When I fill "Quick Order Form" with:
      | SKU2 | PSKU1 |
    And I wait for products to load
    And I type "0" in "Quick Order Form > QTY2"
    And I click on empty space
    Then I should see that "Quick Add Form Validation Row2 Error" contains "Quantity should be greater than 0."

  Scenario: Attempt to get Quote
    When I click "Get Quote"
    Then I should see that "Quick Add Form Validation Row2 Error" contains "Quantity should be greater than 0."
    And I should not see "Quick Add Form Validation Row1 Error"

  Scenario: Perform successful Get Quote action
    When I click on "Quick Order Form > DeleteRow2"
    And I click "Get Quote"
    Then Page title equals to "Request A Quote - Requests For Quote - My Account"

