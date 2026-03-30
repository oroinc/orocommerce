@feature-BB-21128
@fixture-OroOrderBundle:product-kit/update_order_with_product_kits__product.yml
@fixture-OroOrderBundle:product-kit/update_order_with_product_kits__order.yml

Feature: Update Order with Product Kits

  Scenario: Check line items in order form
    Given I login as administrator
    And go to Sales / Orders
    When click edit "order1" in grid
    Then "Order Form" must contains values:
      | Customer                 | Customer1                                                   |
      | Customer User            | Amanda Cole                                                 |
      | Billing Address          | Test Customer, ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
      | Shipping Address         | Test Customer, ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
    And I click "Line Items"
    When I click on the first "Edit Line Item Button"
    Then "Order Form" must contains values:
      | Product                  | product-kit-01 - Product Kit 01                             |
      | Quantity                 | 1                                                           |
      | Price                    | 12.34                                                       |
      | ProductKitItem1Product   | simple-product-03 - Simple Product 03                       |
      | ProductKitItem1Quantity  | 1                                                           |
      | ProductKitItem1Price     | 34.56                                                       |
      | ProductKitItem2Product   | simple-product-01 - Simple Product 01                       |
      | ProductKitItem2Quantity  | 1                                                           |
      | ProductKitItem2Price     | 12.34                                                       |
    And I should see "$3.7035" in the "Order Form Line Item 1 Kit Item 1 Matched Price" element
    And I should see "$1.2345" in the "Order Form Line Item 1 Kit Item 2 Matched Price" element
    And the "Price" field should be readonly in form "Order Form"
    When I click on the first "Edit Line Item Button"
    Then "Order Form" must contains values:
      | Product2                 | product-kit-01 - Product Kit 01                             |
      | Quantity2                | 2                                                           |
      | Price2                   | 34.56                                                       |
      | Product2KitItem1Product  | None                                                        |
      | Product2KitItem1Quantity |                                                             |
      | Product2KitItem1Price    |                                                             |
      | Product2KitItem2Product  | simple-product-01 - Simple Product 01                       |
      | Product2KitItem2Quantity | 1                                                           |
      | Product2KitItem2Price    | 56.78                                                       |
    And I should not see a "Order Form Line Item 2 Kit Item 1 Matched Price" element
    And I should see "$1.2345" in the "Order Form Line Item 2 Kit Item 2 Matched Price" element
    And the "Product2KitItem1Quantity" field should be disabled in form "Order Form"
    And the "Product2KitItem1Price" field should be disabled in form "Order Form"
    And I see next subtotals for "Backend Order":
      | Subtotal | $81.46 |
      | Total    | $81.46 |
    And the "Price2" field should be readonly in form "Order Form"

  Scenario: Check that order can be saved
    When I save form
    Then I should see "Order has been saved" flash message

  Scenario: Reset kit item line item price
    When I click "Line Items"
    And I click on the first "Edit Line Item Button"
    When I click "Order Form Line Item 1 Kit Item 1 Price Overridden"
    And I click "Reset price"
    And I click "Order Form Line Item 1 Kit Item 2 Price Overridden"
    And I click "Reset price"
    Then "Order Form" must contains values:
      | Price                | 12.34 |
      | ProductKitItem1Price | 3.70  |
      | ProductKitItem2Price | 1.23  |
    And the "Price" field should be readonly in form "Order Form"
    And I click on the first "Order Edit Save Changes"
    And I see next subtotals for "Backend Order":
      | Subtotal | $81.46 |
      | Total    | $81.46 |

  Scenario: Check that order can be saved
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Reset line item price
    When I click "Line Items"
    And I click on the first "Edit Line Item Button"
    And I click "Order Form Line Item 1 Price Overridden"
    And I click "Reset price"
    Then "Order Form" must contains values:
      | Price | 128.39 |
    And the "Price" field should be readonly in form "Order Form"
    And I click on the first "Order Edit Save Changes"
    And I see next subtotals for "Backend Order":
      | Subtotal | $197.51 |
      | Total    | $197.51 |

  Scenario: Check that order can be saved
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Remove optional kit item line item
    When I click "Line Items"
    And I click on the first "Edit Line Item Button"
    When I clear "ProductKitItem1Product" field in form "Order Form"
    Then the "ProductKitItem1Quantity" field should be disabled in form "Order Form"
    And the "ProductKitItem1Price" field should be disabled in form "Order Form"
    And "Order Form" must contains values:
      | Price | 128.39 |
    When I click "Order Form Line Item 1 Price Overridden"
    And I click "Reset price"
    Then "Order Form" must contains values:
      | Price | 124.69 |
    And the "Price" field should be readonly in form "Order Form"
    And I click on the first "Order Edit Save Changes"
    And I see next subtotals for "Backend Order":
      | Subtotal | $193.81 |
      | Total    | $193.81 |

  Scenario: Check that order can be saved
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Change product of a kit item line item
    When I click "Line Items"
    And I click on the first "Edit Line Item Button"
    When I fill "Order Form" with:
      | ProductKitItem2Product | simple-product-02 - Simple Product 02 |
    Then "Order Form" must contains values:
      | Price                   | 124.69 |
      | ProductKitItem2Quantity | 1      |
      | ProductKitItem2Price    | 2.47   |
    When I click "Order Form Line Item 1 Price Overridden"
    And I click "Reset price"
    Then "Order Form" must contains values:
      | Price | 125.93 |
    And the "Price" field should be readonly in form "Order Form"
    And I click on the first "Order Edit Save Changes"
    And I see next subtotals for "Backend Order":
      | Subtotal | $195.05 |
      | Total    | $195.05 |

  Scenario: Check that order can be saved
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Change quantity of a kit item line item
    When I click "Line Items"
    And I click on the first "Edit Line Item Button"
    And I fill "Order Form" with:
      | ProductKitItem2Quantity | 2 |
    Then "Order Form" must contains values:
      | Price                   | 125.93 |
      | ProductKitItem2Quantity | 2      |
      | ProductKitItem2Price    | 2.47   |
    When I click "Order Form Line Item 1 Price Overridden"
    And I click "Reset price"
    Then "Order Form" must contains values:
      | Price | 128.40 |
    And the "Price" field should be readonly in form "Order Form"
    And I click on the first "Order Edit Save Changes"
    And I see next subtotals for "Backend Order":
      | Subtotal | $197.52 |
      | Total    | $197.52 |

  Scenario: Check that order can be saved
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Add an optional kit item line item
    When I click "Line Items"
    And I click on the second "Edit Line Item Button"
    When I fill "Order Form" with:
      | ProductKitItem1Product | simple-product-03 - Simple Product 03 |
    Then "Order Form" must contains values:
      | Price                   | 34.56 |
      | ProductKitItem1Quantity | 1     |
      | ProductKitItem1Price    | 3.70  |
    When I click "Order Form Line Item 1 Price Overridden"
    And I click "Reset price"
    Then "Order Form" must contains values:
      | Price | 183.94 |
    And the "Price" field should be readonly in form "Order Form"
    And I click on the first "Order Edit Save Changes"
    And I see next subtotals for "Backend Order":
      | Subtotal | $496.28 |
      | Total    | $496.28 |

  Scenario: Check that order can be saved
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Change price of a kit item line item
    When I click "Line Items"
    And I click on the second "Edit Line Item Button"
    When I fill "Order Form" with:
      | ProductKitItem1Price | 2.75 |
    Then "Order Form" must contains values:
      | Price                   | 183.94 |
      | ProductKitItem1Quantity | 1      |
      | ProductKitItem1Price    | 2.75   |
    And I should see "$3.7035" in the "Order Form Line Item 1 Kit Item 1 Matched Price" element
    When I click "Order Form Line Item 1 Price Overridden"
    And I click "Reset price"
    Then "Order Form" must contains values:
      | Price | 182.99 |
    And the "Price" field should be readonly in form "Order Form"
    And I click on the first "Order Edit Save Changes"
    And I see next subtotals for "Backend Order":
      | Subtotal | $494.38 |
      | Total    | $494.38 |

  Scenario: Check that order can be saved
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Remove a line item
    Given I click "Line Items"
    And I click "Delete" on second row in grid
    And I click "Yes, Delete" in confirmation dialogue
    Then I see next subtotals for "Backend Order":
      | Subtotal | $128.40 |
      | Total    | $128.40 |

  Scenario: Check that order can be saved
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Add a product kit line item
    And fill "Order Edit Add Line Item Form" with:
      | Product  | product-kit-01 |
      | Quantity | 2              |
    And fill "Order Form" with:
      | ProductKitItem1Product | simple-product-03 - Simple Product 03 |
    And fill "Order Form" with:
      | ProductKitItem1Quantity | 3 |
    And fill "Order Form" with:
      | ProductKitItem1Price | 0.75 |
    And click "Add Product"
    When I click "Line Items"
    And I click on the second "Edit Line Item Button"
    Then "Order Form" must contains values:
      | Product                 | product-kit-01 - Product Kit 01       |
      | Quantity                | 2                                     |
      | Price                   | 126.94                                |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 3                                     |
      | ProductKitItem1Price    | 0.75                                  |
      | ProductKitItem2Product  | simple-product-01 - Simple Product 01 |
      | ProductKitItem2Quantity | 1                                     |
      | ProductKitItem2Price    | 1.23                                  |
    And I should see "$3.7035" in the "Order Form Line Item 1 Kit Item 1 Matched Price" element
    And I should not see a "Order Form Line Item 1 Kit Item 2 Matched Price" element
    And the "Price" field should be readonly in form "Order Form"
    And I click on the first "Order Edit Discard Changes"
    And I see next subtotals for "Backend Order":
      | Subtotal | $382.28 |
      | Total    | $382.28 |

  Scenario: Check that order can be saved
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
