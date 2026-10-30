@fixture-OroProductBundle:quick_order_form_with_product_kits__product.yml
Feature: Quick order form validation messages
  In order to fix the rows I entered in the quick order form
  As a buyer
  I need to see the values I entered in validation messages as plain text

  Scenario: Show an unknown unit from the pasted order as plain text
    Given I login as AmandaRCole@example.org buyer
    When I click "Quick Order"
    And I fill "Quick Add Copy Paste Form" with:
      | Paste your order | simple-product-01 2 <b>pcs</b> |
    And I click "Verify Order"
    Then I should see "Quick Order Form" validation errors:
      | UNIT1 | Unit '<b>pcs</b>' doesn't exist for product simple-product-01. |
