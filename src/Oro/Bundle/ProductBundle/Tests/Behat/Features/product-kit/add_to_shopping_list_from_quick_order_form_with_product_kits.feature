@feature-BB-22738
@fixture-OroProductBundle:quick_order_form_with_product_kits__product.yml

Feature: Add to Shopping List from Quick order form with Product Kits

  Scenario: Product Kit can be selected in Quick Order Form
    Given I login as AmandaRCole@example.org buyer
    When I click "Quick Order"
    And I fill "Quick Add Copy Paste Form" with:
      | Paste your order | product-kit-01 3\nsimple-product-01 2 |
    And I click "Verify Order"
    Then "Quick Order Form" must contains values:
      | SKU1      | PRODUCT-KIT-01 - Product Kit 01       |
      | QTY1      | 3                                     |
      | UNIT1     | piece                                 |
      | SUBTOTAL1 | N/A                                   |
      | SKU2      | SIMPLE-PRODUCT-01 - Simple Product 01 |
      | QTY2      | 2                                     |
      | UNIT2     | piece                                 |
      | SUBTOTAL2 | $2.469                                |
    And I should see a "Get Quote Button" element

  Scenario: Check Shopping List page
    When I click "Add to Shopping List"
    Then I should see "2 products were added (view shopping list)" flash message
    When I click "view shopping list"
    Then I should see following grid:
      | SKU               | Item                                                                           |          | Qty Update All | Price     | Subtotal |
      | product-kit-01    | Product Kit 01                                                                 | In Stock | 3 piece        | $123.4567 | $370.37  |
      |                   | Product kit "product-kit-01" is missing the required kit item "Mandatory Item" |          |                |           |          |

      | simple-product-01 | Simple Product 01                                                              | In Stock | 2 piece        | $1.2345   | $2.47    |
    And I should see "Summary 2 Items"
    And I should see "Subtotal $372.84"
    And I should see "Total $372.84"
    And I should not see "Create Order"

  Scenario: Configure Product Kit
    When I click "Shopping List 1 Kit Line Item Edit Button"
    Then I should see "Product Kit Dialog" with elements:
      | Title                | Product Kit 01                       |
      | Kit Item 1 Name      | Optional Item                        |
      | Kit Item 2 Name      | Mandatory Item                       |
      | Kit Item 1 Product 1 | simple-product-03 Product 03 $3.7035 |
      | Kit Item 1 Product 2 | None                                 |
      | Kit Item 2 Product 1 | simple-product-01 Product 01 $1.2345 |
      | Kit Item 2 Product 2 | simple-product-02 Product 02 $2.469  |
      | Price                | Total: $374.06         |
    And "Product Kit Line Item Form" must contain values:
      | Readonly Kit Item Line Item 1 Quantity |   |
      | Kit Item Line Item 2 Quantity          | 1 |
    When I click "Product Kit Dialog Shopping List Dropdown"
    And I click "Update Shopping List" in "Shopping List Button Group Menu" element
    Then I should see 'Product kit has been updated in "Shopping List"' flash message

  Scenario: Check Shopping List
    Given I should see following grid:
      | SKU               | Item                              |          | Qty Update All | Price     | Subtotal |
      | product-kit-01    | Product Kit 01                    | In Stock | 3 piece        | $124.6867 | $374.06  |
      | simple-product-01 | Mandatory Item: Simple Product 01 |          | 1 piece        | $1.2345   |          |

      | simple-product-01 | Simple Product 01                 | In Stock | 2 piece        | $1.2345   | $2.47    |
    And I should see "Summary 2 Items"
    And I should see "Subtotal $376.53"
    And I should see "Total $376.53"
    And I should see "Checkout"
