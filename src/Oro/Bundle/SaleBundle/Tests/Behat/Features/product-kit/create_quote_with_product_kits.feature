@feature-BB-22730
@fixture-OroSaleBundle:product-kit/create_quote_with_product_kits.yml

Feature: Create Quote with Product Kits

  Scenario: Add a product kit line item
    Given I login as administrator
    And go to Sales / Quotes
    And click "Create Quote"
    And I fill "Quote Form" with:
      | Customer         | Customer1                                                   |
      | Customer User    | Amanda Cole                                                 |
      | Shipping Address | Test Customer, ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
      | LineItemProduct  | product-kit-01                                              |
    Then I should see "Optional Item" in the "Quote Form Line Item 1 Kit Item 1 Label" element
    And I should see "Mandatory Item *" in the "Quote Form Line Item 1 Kit Item 2 Label" element
    And the "Line Item 1 Item 1 Quantity" field should be disabled in form "Quote Form"
    And I should see the following options for "Line Item 1 Item 1 Product" select in form "Quote Form":
      | simple-product-03 - Simple Product 03 |
    And I should see the following options for "Line Item 1 Item 2 Product" select in form "Quote Form":
      | simple-product-01 - Simple Product 01 |
      | simple-product-02 - Simple Product 02 |
    And "Quote Form" must contains values:
      | LineItemQuantity            | 1                                     |
      | LineItemPrice               | 124.69                                |
      | Line Item 1 Item 1 Product  | None                                  |
      | Line Item 1 Item 1 Quantity |                                       |
      | Line Item 1 Item 2 Product  | simple-product-01 - Simple Product 01 |
      | Line Item 1 Item 2 Quantity | 1                                     |
    And the "LineItemPrice" field should be readonly in form "Quote Form"
    When I click on "Quote Form Line Item 1 Kit Item 1 Quantity Label Tooltip"
    Then I should see "The quantity of product kit item units to be purchased: piece (whole numbers)" in the "Tooltip Popover Content" element

  Scenario: Add one more product kit line item via the entity select popup
    When I click "Backend Quote Add Product Button"
    And I open select entity popup for field "Line Item 2 Product Dropdown" in form "Quote Form"
    And I sort grid by "SKU"
    Then I should see following grid:
      | SKU               | Name              |
      | product-kit-01    | Product Kit 01    |
      | simple-product-01 | Simple Product 01 |
      | simple-product-02 | Simple Product 02 |
      | simple-product-03 | Simple Product 03 |
    When I click on product-kit-01 in grid
    And fill "Quote Form" with:
      | LineItemQuantity2           | 2                                     |
      | Line Item 2 Item 1 Product  | simple-product-03 - Simple Product 03 |
      | Line Item 2 Item 1 Quantity | 3                                     |
    Then "Quote Form" must contains values:
      | Line Item 2 Product         | product-kit-01 - Product Kit 01       |
      | Line Item 2 Quantity        | 2                                     |
      | Line Item 2 Price           | 128.39                                |
      | Line Item 2 Item 1 Product  | simple-product-03 - Simple Product 03 |
      | Line Item 2 Item 1 Quantity | 3                                     |
      | Line Item 2 Item 2 Product  | simple-product-01 - Simple Product 01 |
      | Line Item 2 Item 2 Quantity | 1                                     |
    And the "LineItemPrice" field should be readonly in form "Quote Form"
    And the "LineItemPrice2" field should be readonly in form "Quote Form"

  Scenario: Save Quote and check the view page
    When I save and close form
    And agree that shipping cost may have changed
    Then I should see "Quote has been saved" flash message
    And I should see Quote with:
      | Customer         | Customer1   |
      | Customer User    | Amanda Cole |
    And I should see next rows in "Quote Line Items Table" table
      | SKU            | Product                                                                                                 | Quantity      | Price   |
      | product-kit-01 | Product Kit 01 Mandatory Item [piece x 1] Simple Product 01                                             | 1 pc or more  | $124.69 |
      | product-kit-01 | Product Kit 01 Optional Item [piece x 3] Simple Product 03 Mandatory Item [piece x 1] Simple Product 01 | 2 pcs or more | $128.39 |
