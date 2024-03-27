@feature-BB-22738
@fixture-OroProductBundle:quick_order_form_with_product_kits__product.yml

Feature: Quick order form with Product Kits

  Scenario: Product Kit can be selected in Quick Order Form
    Given I login as AmandaRCole@example.org buyer
    When I click "Quick Order"
    And I fill "Quick Order Form" with:
      | SKU1 | product-kit-01 |
    And I wait for products to load
    And I click on empty space
    Then "Quick Order Form" must contains values:
      | SKU1      | product-kit-01 - Product Kit 01 |
      | QTY1      | 1                               |
      | UNIT1     | piece                           |
      | SUBTOTAL1 | N/A                             |
    When I click on "Quick Order Form Line Item 1 View All Prices"
    Then I should see "The price cannot be displayed for a product kit that has not been configured"
    And I click on empty space

  Scenario: Product Kit can be added to Quick Order Form (import)
    When I click "What File Structure Is Accepted"
    Then I should see that "UiDialog Title" contains "Import Excel .CSV File"
    When I download "the CSV template"
    And I close ui dialog
    And I fill quick order template with data:
      | Item Number       | Quantity | Unit  |
      | product-kit-01    | 2        | piece |
      | simple-product-01 | 1        | piece |
    And I import file for quick order
    Then "Quick Order Form" must contains values:
      | SKU1      | product-kit-01 - Product Kit 01       |
      | QTY1      | 3                                     |
      | UNIT1     | piece                                 |
      | SUBTOTAL1 | N/A                                   |
      | SKU2      | SIMPLE-PRODUCT-01 - Simple Product 01 |
      | QTY2      | 1                                     |
      | UNIT2     | piece                                 |
      | SUBTOTAL2 | $1.2345                               |
    When I click on "Quick Order Form Line Item 1 View All Prices"
    Then I should see "The price cannot be displayed for a product kit that has not been configured"
    And I click on empty space

  Scenario:  Product Kit can be added to Quick Order Form (copy paste)
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | product-kit-01 3\nsimple-product-01 2 |
    And I click "Verify Order"
    Then "Quick Order Form" must contains values:
      | SKU1      | product-kit-01 - Product Kit 01       |
      | QTY1      | 6                                     |
      | UNIT1     | piece                                 |
      | SUBTOTAL1 | N/A                                   |
      | SKU2      | SIMPLE-PRODUCT-01 - Simple Product 01 |
      | QTY2      | 3                                     |
      | UNIT2     | piece                                 |
      | SUBTOTAL2 | $3.7035                               |
    When I click on "Quick Order Form Line Item 1 View All Prices"
    Then I should see "The price cannot be displayed for a product kit that has not been configured"
    And I click on empty space
