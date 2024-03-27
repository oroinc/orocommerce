@regression
@feature-BB-22738
@fixture-OroProductBundle:quick_order_form_with_product_kits__product.yml

Feature: Create Order from Quick order form with Product Kits

  Scenario: Product Kit can be selected in Quick Order Form
    Given I login as AmandaRCole@example.org buyer
    When I click "Quick Order"
    Then I should see an "Enabled Create Order Button" element
    And "Quick Order Form Buttons" should contains "Enabled Create Order Button Wrapper" with attributes:
      | title | ~ |
    When I fill "Quick Order Form" with:
      | SKU1 | product-kit-01 |
    And I wait for products to load
    And I click on empty space
    Then "Quick Order Form" must contains values:
      | SKU1      | product-kit-01 - Product Kit 01 |
      | QTY1      | 1                               |
      | UNIT1     | piece                           |
      | SUBTOTAL1 | N/A                             |
    And I should see a "Disabled Create Order Button" element
    And "Quick Order Form Buttons" should contains "Disabled Create Order Button Wrapper" with attributes:
      | title | Some of the products cannot be added to the order. Please add these products to the shopping list or create a quote. |
    When I click on "Disabled Create Order Button Wrapper"
    Then Page title equals to "Quick Order"

  Scenario: Add simple product
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | simple-product-01 2 |
    And I click "Verify Order"
    Then "Quick Order Form" must contains values:
      | SKU1      | product-kit-01 - Product Kit 01       |
      | QTY1      | 1                                     |
      | UNIT1     | piece                                 |
      | SUBTOTAL1 | N/A                                   |
      | SKU2      | SIMPLE-PRODUCT-01 - Simple Product 01 |
      | QTY2      | 2                                     |
      | UNIT2     | piece                                 |
      | SUBTOTAL2 | $2.469                                |
    And I should see a "Disabled Create Order Button" element
    And "Quick Order Form Buttons" should contains "Disabled Create Order Button Wrapper" with attributes:
      | title | Some of the products cannot be added to the order. Please add these products to the shopping list or create a quote. |
    When I click on "Disabled Create Order Button Wrapper"
    Then Page title equals to "Quick Order"

  Scenario: Remove Product Kit
    When I click on "Quick Order Form > DeleteRow1"
    Then "Quick Order Form" must contains values:
      | SKU2      | SIMPLE-PRODUCT-01 - Simple Product 01 |
      | QTY2      | 2                                     |
      | UNIT2     | piece                                 |
      | SUBTOTAL2 | $2.469                                |
    And I should see an "Enabled Create Order Button" element
    And "Quick Order Form Buttons" should contains "Enabled Create Order Button Wrapper" with attributes:
      | title | ~ |

  Scenario: Add Product Kit (copy paste)
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | product-kit-01 1 |
    And I click "Verify Order"
    Then "Quick Order Form" must contains values:
      | SKU2      | SIMPLE-PRODUCT-01 - Simple Product 01 |
      | QTY2      | 2                                     |
      | UNIT2     | piece                                 |
      | SUBTOTAL2 | $2.469                                |
      | SKU3      | PRODUCT-KIT-01 - Product Kit 01       |
      | QTY3      | 1                                     |
      | UNIT3     | piece                                 |
      | SUBTOTAL3 | N/A                                   |
    And I should see a "Disabled Create Order Button" element
    And "Quick Order Form Buttons" should contains "Disabled Create Order Button Wrapper" with attributes:
      | title | Some of the products cannot be added to the order. Please add these products to the shopping list or create a quote. |
    When I click on "Disabled Create Order Button Wrapper"
    Then Page title equals to "Quick Order"

  Scenario: Select Simple Product instead of Product Kit
    When I fill "Quick Order Form" with:
      | SKU3 | simple-product-02 |
    And I wait for products to load
    And I click on empty space
    Then "Quick Order Form" must contains values:
      | SKU2      | SIMPLE-PRODUCT-01 - Simple Product 01 |
      | QTY2      | 2                                     |
      | UNIT2     | piece                                 |
      | SUBTOTAL2 | $2.469                                |
      | SKU3      | simple-product-02 - Simple Product 02 |
      | QTY3      | 1                                     |
      | UNIT3     | piece                                 |
      | SUBTOTAL3 | $2.469                                |
    And I should see an "Enabled Create Order Button" element
    And "Quick Order Form Buttons" should contains "Enabled Create Order Button Wrapper" with attributes:
      | title | ~ |

  Scenario: Select Product Kit instead of Simple Product
    When I fill "Quick Order Form" with:
      | SKU2 | product-kit-01 |
    And I wait for products to load
    And I click on empty space
    Then "Quick Order Form" must contains values:
      | SKU2      | product-kit-01 - Product Kit 01       |
      | QTY2      | 2                                     |
      | UNIT2     | piece                                 |
      | SUBTOTAL2 | N/A                                   |
      | SKU3      | simple-product-02 - Simple Product 02 |
      | QTY3      | 1                                     |
      | UNIT3     | piece                                 |
      | SUBTOTAL3 | $2.469                                |
    And I should see a "Disabled Create Order Button" element
    And "Quick Order Form Buttons" should contains "Disabled Create Order Button Wrapper" with attributes:
      | title | Some of the products cannot be added to the order. Please add these products to the shopping list or create a quote. |
    When I click on "Disabled Create Order Button Wrapper"
    Then Page title equals to "Quick Order"

  Scenario: Remove Product Kit
    When I click on "Quick Order Form > DeleteRow2"
    Then "Quick Order Form" must contains values:
      | SKU3      | simple-product-02 - Simple Product 02 |
      | QTY3      | 1                                     |
      | UNIT3     | piece                                 |
      | SUBTOTAL3 | $2.469                                |
    And I should see an "Enabled Create Order Button" element
    And "Quick Order Form Buttons" should contains "Enabled Create Order Button Wrapper" with attributes:
      | title | ~ |

  Scenario: Add Product Kit (import)
    When I click "What File Structure Is Accepted"
    Then I should see that "UiDialog Title" contains "Import Excel .CSV File"
    When I download "the CSV template"
    And I close ui dialog
    And I fill quick order template with data:
      | Item Number    | Quantity | Unit  |
      | product-kit-01 | 1        | piece |
    And I import file for quick order
    Then "Quick Order Form" must contains values:
      | SKU3      | simple-product-02 - Simple Product 02 |
      | QTY3      | 1                                     |
      | UNIT3     | piece                                 |
      | SUBTOTAL3 | $2.469                                |
      | SKU4      | PRODUCT-KIT-01 - Product Kit 01       |
      | QTY4      | 1                                     |
      | UNIT4     | piece                                 |
      | SUBTOTAL4 | N/A                                   |
    And I should see a "Disabled Create Order Button" element
    And "Quick Order Form Buttons" should contains "Disabled Create Order Button Wrapper" with attributes:
      | title | Some of the products cannot be added to the order. Please add these products to the shopping list or create a quote. |
    When I click on "Disabled Create Order Button Wrapper"
    Then Page title equals to "Quick Order"

  Scenario: Remove all products
    When I click on "Quick Order Form > DeleteRow3"
    And I click on "Quick Order Form > DeleteRow4"
    Then I should see an "Enabled Create Order Button" element
    And "Quick Order Form Buttons" should contains "Enabled Create Order Button Wrapper" with attributes:
      | title | ~ |
