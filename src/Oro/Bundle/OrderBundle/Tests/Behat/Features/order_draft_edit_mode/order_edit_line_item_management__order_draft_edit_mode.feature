@feature-BB-26023-enabled
@regression
@fixture-OroOrderBundle:OrderWithPromotion.yml

Feature: Order edit line item management - Order Draft Edit Mode

  Scenario: Enable Order Draft Edit Mode
    Given I login as administrator
    When go to System / Configuration
    And I follow "Commerce/Orders/Order Draft Edit Mode" on configuration sidebar
    And I fill "Configuration Order Draft Edit Mode Form" with:
      | Enable Order Draft Edit Mode Use Default | false |
      | Enable Order Draft Edit Mode             | true  |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check sorting in grid
    When I go to Sales/ Orders
    And I click edit SimpleOrder in grid
    Then number of records should be 5

    When sort grid by SKU
    Then SKU1 must be first record
    But when I sort grid by SKU again
    Then SKU5 must be first record

    When sort grid by Product
    Then HDMI Cable must be first record
    But when I sort grid by Product again
    Then Wireless Mouse must be first record

    When sort grid by Quantity
    Then 1 item must be first record
    But when I sort grid by Quantity again
    Then 10 items must be first record

    When sort grid by Price
    Then 15.99 must be first record
    But when I sort grid by Price again
    Then 1,299.99 must be first record

    When sort grid by Ship By
    Then Jan 1, 2010 must be first record
    But when I sort grid by Ship By again
    Then May 5, 2010 must be first record
    And sort grid by SKU

  Scenario: Check filters in grid
    Given I should see following grid:
      | SKU  | Product         | Quantity | Price     | Ship By     |
      | SKU1 | Laptop Computer | 2 items  | $1,299.99 | Jan 1, 2010 |
      | SKU2 | Wireless Mouse  | 5 sets   | $29.99    | Feb 2, 2010 |
      | SKU3 | USB Keyboard    | 3 items  | $59.99    | Mar 3, 2010 |
      | SKU4 | Monitor 24 inch | 1 item   | $349.99   | Apr 4, 2010 |
      | SKU5 | HDMI Cable      | 10 items | $15.99    | May 5, 2010 |

    When I filter SKU as contains "SKU1"
    Then I should see following grid:
      | SKU  | Product         | Quantity | Price     | Ship By     |
      | SKU1 | Laptop Computer | 2 items  | $1,299.99 | Jan 1, 2010 |
    And I reset SKU filter

    When I filter Product as contains "HDMI Cable"
    Then I should see following grid:
      | SKU  | Product    | Quantity | Price  | Ship By     |
      | SKU5 | HDMI Cable | 10 items | $15.99 | May 5, 2010 |
    And I reset Product filter

    When I filter Quantity as equals "2"
    Then I should see following grid:
      | SKU  | Product         | Quantity | Price     | Ship By     |
      | SKU1 | Laptop Computer | 2 items  | $1,299.99 | Jan 1, 2010 |
    And I reset Quantity filter

    When I filter Price as equals "$15.99"
    Then I should see following grid:
      | SKU  | Product    | Quantity | Price  | Ship By     |
      | SKU5 | HDMI Cable | 10 items | $15.99 | May 5, 2010 |
    And I reset Price filter

    When I filter Ship By as equals "2010-01-01" as single value
    Then I should see following grid:
      | SKU  | Product         | Quantity | Price     | Ship By     |
      | SKU1 | Laptop Computer | 2 items  | $1,299.99 | Jan 1, 2010 |
    And I reset Ship By filter

  Scenario: Add order line item with free-form product
    When click on "Order Line Item Draft Create Free-Form Switch"
    And I fill "Order Line Item Draft Create Form" with:
      | FreeProductSku | PRODUCT_SKU |
      | FreeProduct    | PRODUCT_0   |
      | Quantity       | 5           |
      | Price          | 100         |
    And click "Add Product"
    Then number of records should be 6
    And I should see following grid:
      | SKU         | Product         | Quantity | Price     | Ship By     |
      | PRODUCT_SKU | PRODUCT_0       | 5 each   | $100.00   |             |
      | SKU1        | Laptop Computer | 2 items  | $1,299.99 | Jan 1, 2010 |
      | SKU2        | Wireless Mouse  | 5 sets   | $29.99    | Feb 2, 2010 |
      | SKU3        | USB Keyboard    | 3 items  | $59.99    | Mar 3, 2010 |
      | SKU4        | Monitor 24 inch | 1 item   | $349.99   | Apr 4, 2010 |
      | SKU5        | HDMI Cable      | 10 items | $15.99    | May 5, 2010 |

  Scenario: Check order line items edit datagrid view mode
    When I should see "Product PRODUCT_SKU has been added to the list. Show only this product"
    And I click "Show only this product"
    Then I should see following grid:
      | SKU         | Product   | Quantity | Price   |
      | PRODUCT_SKU | PRODUCT_0 | 5 each   | $100.00 |
    And I should see "Showing only product PRODUCT_SKU. Restore previous view"

    When I click "Restore previous view"
    Then I should see following grid:
      | SKU         | Product         | Quantity | Price     | Ship By     |
      | PRODUCT_SKU | PRODUCT_0       | 5 each   | $100.00   |             |
      | SKU1        | Laptop Computer | 2 items  | $1,299.99 | Jan 1, 2010 |
      | SKU2        | Wireless Mouse  | 5 sets   | $29.99    | Feb 2, 2010 |
      | SKU3        | USB Keyboard    | 3 items  | $59.99    | Mar 3, 2010 |
      | SKU4        | Monitor 24 inch | 1 item   | $349.99   | Apr 4, 2010 |
      | SKU5        | HDMI Cable      | 10 items | $15.99    | May 5, 2010 |

  Scenario: Check the confirmation dialog when leaving or reloading the page/grid with a line item in edit mode
    When I click edit SKU1 in "Order Line Item Draft Grid"
    And I fill "Order Line Item Draft Edit Form" with:
      | Quantity | 10 |
    And I save form
    And I click "Save" in modal window
    Then should see "You have unsaved changes, are you sure you want to reload this page?" in confirmation dialogue
    And click "Cancel" in confirmation dialogue

    When I refresh "Order Line Item Draft Grid" grid
    Then should see "You have unsaved changes, are you sure you want to reload this page?" in confirmation dialogue
    And click "Ok, got it" in confirmation dialogue

    When I click edit SKU1 in "Order Line Item Draft Grid"
    And I fill "Order Line Item Draft Edit Form" with:
      | Quantity | 10 |
    And I go to Sales/Orders
    Then I should see alert with message "You have unsaved changes, are you sure you want to leave this page?"
    And I accept alert
