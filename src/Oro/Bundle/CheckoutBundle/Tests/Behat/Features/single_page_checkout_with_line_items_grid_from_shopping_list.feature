@ticket-BB-19696
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroCheckoutBundle:NewCheckoutLineItemsLayoutFixture.yml

Feature: Single Page Checkout With Line Items Grid From Shopping List
  In order to create order on front store
  As a Buyer
  I want to start and complete checkout from shopping list

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator
    And I go to System / Localization / Translations
    And I filter Key as equal to "oro.checkout.order_summary.unit"
    And I edit "oro.checkout.order_summary.unit" Translated Value as "Unit"
    And I click "Update Cache"
    And I should see "Translation Cache has been updated" flash message
    And I set configuration property "oro_checkout.use_new_layout_for_checkout_page" to "1"
    And I go to System/Workflows
    And I click "Activate" on row "Single Page Checkout" in grid
    And I click "Activate" in modal window
    And I should see "Workflow activated" flash message

  Scenario: Create Color product attribute
    Given I go to Products/Product Attributes
    When I click "Create Attribute"
    And fill form with:
      | Field Name | Color  |
      | Type       | Select |
    And click "Continue"
    And set Options with:
      | Label |
      | Red   |
      | Green |
      | Blue  |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Create Size product attribute
    Given I go to Products/Product Attributes
    When I click "Create Attribute"
    And fill form with:
      | Field Name | Size   |
      | Type       | Select |
    And click "Continue"
    And set Options with:
      | Label |
      | S     |
      | M     |
      | L     |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Update schema
    Given I go to Products/Product Attributes
    And I confirm schema update

  Scenario: Update product family
    Given I go to Products/Product Families
    When I click Edit Attribute Family in grid
    And set Attribute Groups with:
      | Label           | Visible | Attributes                                                                                                                                                                       |
      | Attribute group | true    | [SKU, Name, Is Featured, New Arrival, Brand, Description, Short Description, Images, Inventory Status, Meta title, Meta description, Meta keywords, Product prices, Color, Size] |
    And I save form
    Then I should see "Successfully updated" flash message

  Scenario Outline: Prepare simple products
    Given I go to Products/Products
    And I filter SKU as is equal to "<SKU>"
    And I click Edit <SKU> in grid
    When I fill in product attribute "Color" with "<Color>"
    And I fill in product attribute "Size" with "<Size>"
    And I set Images with:
      | Main | Listing | Additional |
      | 1    | 1       | 1          |
    And I click on "Digital Asset Choose"
    And I fill "Digital Asset Dialog Form" with:
      | File  | <Image>       |
      | Title | <SKU>_<Image> |
    And I click "Upload"
    And click on <SKU>_<Image> in grid
    And I save and close form
    Then I should see "Product has been saved" flash message
    Examples:
      | SKU  | Color | Size | Image    |
      | BB4  | Red   | M    | cat1.jpg |
      | BB5  | Green | L    | cat2.jpg |
      | BB6  | Blue  | S    | cat3.jpg |
      | BB7  | Red   | M    | cat1.jpg |
      | BB8  | Green | L    | cat2.jpg |
      | BB9  | Blue  | S    | cat3.jpg |
      | BB10 | Red   | M    | cat1.jpg |
      | BB11 | Green | L    | cat2.jpg |
      | BB12 | Blue  | S    | cat3.jpg |

  Scenario Outline: Prepare configurable products
    Given I go to Products/Products
    And I filter SKU as is equal to "<MainSKU>"
    And I click Edit <MainSKU> in grid
    When I fill "ProductForm" with:
      | Configurable Attributes | [Color, Size] |
    And I check records in grid:
      | <SKU1> |
      | <SKU2> |
      | <SKU3> |
    And I save and close form
    Then I should see "Product has been saved" flash message
    Examples:
      | MainSKU | SKU1 | SKU2 | SKU3 |
      | AA1     | BB4  | BB5  | BB12 |
      | AA2     | BB6  | BB7  | BB11 |
      | AA3     | BB8  | BB9  | BB10 |

  Scenario: Create order from Shopping List 3 and verify quantity
    Given I operate as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I open page with shopping list Shopping List 3
    And I click "Create Order"
    Then I should see "32 total records"
    And I should see following grid:
      | Item                                                                       | Qty | Unit   | Price  | Subtotal                 |
      | Configurable Product 1 BB4 In Stock Color: Red Size: M Note 4 text         | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | Configurable Product 1 BB5 Out of Stock Color: Green Size: L Note 5 text   | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | Configurable Product 2 BB6 In Stock Color: Blue Size: S Note 6 text        | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | Configurable Product 2 BB7 Out of Stock Color: Red Size: M Note 7 text     | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3 BB8 In Stock Color: Green Size: L Note 8 text       | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3 BB9 Out of Stock Color: Blue Size: S Note 9 text    | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3 BB10 In Stock Color: Red Size: M Note 10 text       | 7   | sets   | $19.00 | $133.00                  |
      | Configurable Product 2 BB11 Out of Stock Color: Green Size: L Note 11 text | 7   | sets   | $19.00 | $133.00                  |
      | Configurable Product 1 BB12 In Stock Color: Blue Size: S Note 12 text      | 7   | items  | $23.00 | $161.00 -$80.50 $80.50   |
      | Product 13 BB13 Out of Stock Note 13 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
    And I should see "Summary 32 Items"
    And I should see "Subtotal $8,818.00"
    And I should see "Discount -$647.50"
    And I should see "Shipping $3.00"
    And I should see "Total $8,173.50"
    And I should see "Shopping List 3 Notes"

  Scenario: Check view second page
    When I click "Next"
    Then I should see following grid:
      | Item                                      | Qty | Unit   | Price  | Subtotal                 |
      | Product 14 BB14 In Stock Note 14 text     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 15 BB15 Out of Stock Note 15 text | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 16 BB16 In Stock Note 16 text     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 17 BB17 Out of Stock Note 17 text | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 18 BB18 In Stock Note 18 text     | 11  | sets   | $29.00 | $319.00                  |
      | Product 19 BB19 Out of Stock Note 19 text | 11  | sets   | $29.00 | $319.00                  |
      | Product 20 BB20 In Stock Note 20 text     | 11  | sets   | $29.00 | $319.00                  |
      | Product 21 CC21 In Stock Note 21 text     | 13  | pieces | $31.00 | $403.00                  |
      | Product 22 CC22 In Stock Note 22 text     | 13  | pieces | $31.00 | $403.00                  |
      | Product 23 CC23 In Stock Note 23 text     | 13  | pieces | $31.00 | $403.00                  |

  Scenario: Check view third page
    When I click "Next"
    Then I should see following grid:
      | Item                                  | Qty | Unit   | Price  | Subtotal |
      | Product 24 CC24 In Stock Note 24 text | 13  | pieces | $31.00 | $403.00  |
      | Product 25 CC25 In Stock Note 25 text | 13  | pieces | $31.00 | $403.00  |
      | Product 26 CC26 In Stock Note 26 text | 13  | pieces | $31.00 | $403.00  |
      | Product 27 CC27 In Stock Note 27 text | 13  | pieces | $31.00 | $403.00  |
      | Product 28 CC28 In Stock Note 28 text | 13  | pieces | $31.00 | $403.00  |
      | Product 29 CC29 In Stock Note 29 text | 13  | pieces | $31.00 | $403.00  |
      | Product 30 CC30 In Stock Note 30 text | 13  | pieces | $31.00 | $403.00  |
      | Product 31 CC31 In Stock Note 31 text | 13  | pieces | $31.00 | $403.00  |
      | Product 32 CC32 In Stock Note 32 text | 13  | pieces | $31.00 | $403.00  |
      | Product 33 CC33 In Stock Note 33 text | 13  | pieces | $31.00 | $403.00  |

  Scenario: Check view fourth page
    When I click "Next"
    Then I should see following grid:
      | Item                                  | Qty | Unit   | Price  | Subtotal |
      | Product 34 CC34 In Stock Note 34 text | 13  | pieces | $31.00 | $403.00  |
      | Product 35 CC35 In Stock Note 35 text | 13  | pieces | $31.00 | $403.00  |

  Scenario: Check edit line item
    When I click "Edit items"
    And I click on "Shopping List Line Item 1 Quantity"
    And I type "10" in "Shopping List Line Item 1 Quantity Input"
    And I click on "Shopping List Line Item 1 Save Changes Button"
    And I click "Create Order"
    Then I should see "32 total records"
    And I should see following grid:
      | Item                                                                       | Qty | Unit   | Price  | Subtotal                 |
      | Configurable Product 1 BB4 In Stock Color: Red Size: M Note 4 text         | 10  | items  | $11.00 | $110.00 -$55.00 $55.00   |
      | Configurable Product 1 BB5 Out of Stock Color: Green Size: L Note 5 text   | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | Configurable Product 2 BB6 In Stock Color: Blue Size: S Note 6 text        | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | Configurable Product 2 BB7 Out of Stock Color: Red Size: M Note 7 text     | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3 BB8 In Stock Color: Green Size: L Note 8 text       | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3 BB9 Out of Stock Color: Blue Size: S Note 9 text    | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3 BB10 In Stock Color: Red Size: M Note 10 text       | 7   | sets   | $19.00 | $133.00                  |
      | Configurable Product 2 BB11 Out of Stock Color: Green Size: L Note 11 text | 7   | sets   | $19.00 | $133.00                  |
      | Configurable Product 1 BB12 In Stock Color: Blue Size: S Note 12 text      | 7   | items  | $23.00 | $161.00 -$80.50 $80.50   |
      | Product 13 BB13 Out of Stock Note 13 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
    And I should see "Summary 32 Items"
    And I should see "Subtotal $8,895.00"
    And I should see "Discount -$686.00"
    And I should see "Total $8,212.00"

  Scenario: Change products status and inventory status
    Given I proceed as the Admin
    And I go to Products/Products
    And I filter SKU as is equal "CC27"
    When I edit "CC27" Inventory Status as "Discontinued"
    Then I should see "Record has been successfully updated" flash message
    And I filter SKU as is equal "CC28"
    When I edit "CC28" Status as "Disabled"
    Then I should see "Record has been successfully updated" flash message

  Scenario: Create order from Shopping List 1 and verify quantity
    Given I operate as the Buyer
    When I reload the page
    Then I should see "Some products have not been added to this order. Please create an RFQ to request price." flash message and I close it
    And I should see "30 total records"
    And I should see following grid:
      | Item                                                                       | Qty | Unit   | Price  | Subtotal                 |
      | Configurable Product 1 BB4 In Stock Color: Red Size: M Note 4 text         | 10  | items  | $11.00 | $110.00 -$55.00 $55.00   |
      | Configurable Product 1 BB5 Out of Stock Color: Green Size: L Note 5 text   | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | Configurable Product 2 BB6 In Stock Color: Blue Size: S Note 6 text        | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | Configurable Product 2 BB7 Out of Stock Color: Red Size: M Note 7 text     | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3 BB8 In Stock Color: Green Size: L Note 8 text       | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3 BB9 Out of Stock Color: Blue Size: S Note 9 text    | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3 BB10 In Stock Color: Red Size: M Note 10 text       | 7   | sets   | $19.00 | $133.00                  |
      | Configurable Product 2 BB11 Out of Stock Color: Green Size: L Note 11 text | 7   | sets   | $19.00 | $133.00                  |
      | Configurable Product 1 BB12 In Stock Color: Blue Size: S Note 12 text      | 7   | items  | $23.00 | $161.00 -$80.50 $80.50   |
      | Product 13 BB13 Out of Stock Note 13 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
    And I should see "Summary 30 Items"
    And I should see "Subtotal $8,089.00"
    And I should see "Discount -$686.00"
    And I should see "Total $7,406.00"

  Scenario: Check view second page
    When I click "Next"
    Then I should see following grid:
      | Item                                      | Qty | Unit   | Price  | Subtotal                 |
      | Product 14 BB14 In Stock Note 14 text     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 15 BB15 Out of Stock Note 15 text | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 16 BB16 In Stock Note 16 text     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 17 BB17 Out of Stock Note 17 text | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 18 BB18 In Stock Note 18 text     | 11  | sets   | $29.00 | $319.00                  |
      | Product 19 BB19 Out of Stock Note 19 text | 11  | sets   | $29.00 | $319.00                  |
      | Product 20 BB20 In Stock Note 20 text     | 11  | sets   | $29.00 | $319.00                  |
      | Product 21 CC21 In Stock Note 21 text     | 13  | pieces | $31.00 | $403.00                  |
      | Product 22 CC22 In Stock Note 22 text     | 13  | pieces | $31.00 | $403.00                  |
      | Product 23 CC23 In Stock Note 23 text     | 13  | pieces | $31.00 | $403.00                  |

  Scenario: Check view third page
    When I click "Next"
    Then I should see following grid:
      | Item                                  | Qty | Unit   | Price  | Subtotal |
      | Product 24 CC24 In Stock Note 24 text | 13  | pieces | $31.00 | $403.00  |
      | Product 25 CC25 In Stock Note 25 text | 13  | pieces | $31.00 | $403.00  |
      | Product 26 CC26 In Stock Note 26 text | 13  | pieces | $31.00 | $403.00  |
      | Product 29 CC29 In Stock Note 29 text | 13  | pieces | $31.00 | $403.00  |
      | Product 30 CC30 In Stock Note 30 text | 13  | pieces | $31.00 | $403.00  |
      | Product 31 CC31 In Stock Note 31 text | 13  | pieces | $31.00 | $403.00  |
      | Product 32 CC32 In Stock Note 32 text | 13  | pieces | $31.00 | $403.00  |
      | Product 33 CC33 In Stock Note 33 text | 13  | pieces | $31.00 | $403.00  |
      | Product 34 CC34 In Stock Note 34 text | 13  | pieces | $31.00 | $403.00  |
      | Product 35 CC35 In Stock Note 35 text | 13  | pieces | $31.00 | $403.00  |

  Scenario: Check SKU filter for simple product
    Given I open Order History page on the store frontend
    And I click "Check Out" on row "Shopping List 3" in grid "OpenOrdersGrid"
    And I should see "30 total records"
    When I filter SKU as contains "CC3"
    Then I should see following grid:
      | Item                                  | Qty | Unit   | Price  | Subtotal |
      | Product 30 CC30 In Stock Note 30 text | 13  | pieces | $31.00 | $403.00  |
      | Product 31 CC31 In Stock Note 31 text | 13  | pieces | $31.00 | $403.00  |
      | Product 32 CC32 In Stock Note 32 text | 13  | pieces | $31.00 | $403.00  |
      | Product 33 CC33 In Stock Note 33 text | 13  | pieces | $31.00 | $403.00  |
      | Product 34 CC34 In Stock Note 34 text | 13  | pieces | $31.00 | $403.00  |
      | Product 35 CC35 In Stock Note 35 text | 13  | pieces | $31.00 | $403.00  |
    And I should see "6 total records"

  Scenario: Check SKU filter for configurable product
    Given I open Order History page on the store frontend
    And I click "Check Out" on row "Shopping List 3" in grid "OpenOrdersGrid"
    And I should see "30 total records"
    When I filter SKU as contains "BB4"
    Then I should see following grid:
      | Item                                                               | Qty | Unit  | Price  | Subtotal               |
      | Configurable Product 1 BB4 In Stock Color: Red Size: M Note 4 text | 10  | items | $11.00 | $110.00 -$55.00 $55.00 |
    And I should see "1 total records"

  Scenario: Check Show All and Group similar
    Given I open Order History page on the store frontend
    When I click "Check Out" on row "Shopping List 3" in grid "OpenOrdersGrid"
    And I should see "30 total records"
    And I click "Show All"
    And I click "Group Similar"
    Then I should see "27 total records"
    And I should see following grid:
      | Item                                                                       | Qty | Unit   | Price  | Subtotal                 |
      | Configurable Product 2 BB6 In Stock Color: Blue Size: S Note 6 text        | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | Configurable Product 2 BB7 Out of Stock Color: Red Size: M Note 7 text     | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3                                                     | 10  | pieces |        | $170.00                  |
      | BB8 In Stock Color: Green Size: L Note 8 text                              | 5   | pieces | $17.00 | $85.00                   |
      | BB9 Out of Stock Color: Blue Size: S Note 9 text                           | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3 BB10 In Stock Color: Red Size: M Note 10 text       | 7   | sets   | $19.00 | $133.00                  |
      | Configurable Product 2 BB11 Out of Stock Color: Green Size: L Note 11 text | 7   | sets   | $19.00 | $133.00                  |
      | Configurable Product 1                                                     | 20  | items  |        | $304.00 -$152.00 $152.00 |
      | BB4 In Stock Color: Red Size: M Note 4 text                                | 10  | items  | $11.00 | $110.00 -$55.00 $55.00   |
      | BB5 Out of Stock Color: Green Size: L Note 5 text                          | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB12 In Stock Color: Blue Size: S Note 12 text                             | 7   | items  | $23.00 | $161.00 -$80.50 $80.50   |
      | Product 13 BB13 Out of Stock Note 13 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 14 BB14 In Stock Note 14 text                                      | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 15 BB15 Out of Stock Note 15 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 16 BB16 In Stock Note 16 text                                      | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 17 BB17 Out of Stock Note 17 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 18 BB18 In Stock Note 18 text                                      | 11  | sets   | $29.00 | $319.00                  |
      | Product 19 BB19 Out of Stock Note 19 text                                  | 11  | sets   | $29.00 | $319.00                  |
      | Product 20 BB20 In Stock Note 20 text                                      | 11  | sets   | $29.00 | $319.00                  |
      | Product 21 CC21 In Stock Note 21 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 22 CC22 In Stock Note 22 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 23 CC23 In Stock Note 23 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 24 CC24 In Stock Note 24 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 25 CC25 In Stock Note 25 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 26 CC26 In Stock Note 26 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 29 CC29 In Stock Note 29 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 30 CC30 In Stock Note 30 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 31 CC31 In Stock Note 31 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 32 CC32 In Stock Note 32 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 33 CC33 In Stock Note 33 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 34 CC34 In Stock Note 34 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 35 CC35 In Stock Note 35 text                                      | 13  | pieces | $31.00 | $403.00                  |
    When I reload the page
    Then I should see "Ungroup Similar"
    And I should see "27 total records"
    And I should see following grid:
      | Item                                                                       | Qty | Unit   | Price  | Subtotal                 |
      | Configurable Product 2 BB6 In Stock Color: Blue Size: S Note 6 text        | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | Configurable Product 2 BB7 Out of Stock Color: Red Size: M Note 7 text     | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3                                                     | 10  | pieces |        | $170.00                  |
      | BB8 In Stock Color: Green Size: L Note 8 text                              | 5   | pieces | $17.00 | $85.00                   |
      | BB9 Out of Stock Color: Blue Size: S Note 9 text                           | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3 BB10 In Stock Color: Red Size: M Note 10 text       | 7   | sets   | $19.00 | $133.00                  |
      | Configurable Product 2 BB11 Out of Stock Color: Green Size: L Note 11 text | 7   | sets   | $19.00 | $133.00                  |
      | Configurable Product 1                                                     | 20  | items  |        | $304.00 -$152.00 $152.00 |
      | BB4 In Stock Color: Red Size: M Note 4 text                                | 10  | items  | $11.00 | $110.00 -$55.00 $55.00   |
      | BB5 Out of Stock Color: Green Size: L Note 5 text                          | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB12 In Stock Color: Blue Size: S Note 12 text                             | 7   | items  | $23.00 | $161.00 -$80.50 $80.50   |
      | Product 13 BB13 Out of Stock Note 13 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 14 BB14 In Stock Note 14 text                                      | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 15 BB15 Out of Stock Note 15 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 16 BB16 In Stock Note 16 text                                      | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 17 BB17 Out of Stock Note 17 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 18 BB18 In Stock Note 18 text                                      | 11  | sets   | $29.00 | $319.00                  |
      | Product 19 BB19 Out of Stock Note 19 text                                  | 11  | sets   | $29.00 | $319.00                  |
      | Product 20 BB20 In Stock Note 20 text                                      | 11  | sets   | $29.00 | $319.00                  |
      | Product 21 CC21 In Stock Note 21 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 22 CC22 In Stock Note 22 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 23 CC23 In Stock Note 23 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 24 CC24 In Stock Note 24 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 25 CC25 In Stock Note 25 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 26 CC26 In Stock Note 26 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 29 CC29 In Stock Note 29 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 30 CC30 In Stock Note 30 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 31 CC31 In Stock Note 31 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 32 CC32 In Stock Note 32 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 33 CC33 In Stock Note 33 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 34 CC34 In Stock Note 34 text                                      | 13  | pieces | $31.00 | $403.00                  |
      | Product 35 CC35 In Stock Note 35 text                                      | 13  | pieces | $31.00 | $403.00                  |

  Scenario: Check Show Less
    When I click "Show Less"
    Then I should see "27 total records"
    And I should see following grid:
      | Item                                                                       | Qty | Unit   | Price  | Subtotal                 |
      | Configurable Product 2 BB6 In Stock Color: Blue Size: S Note 6 text        | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | Configurable Product 2 BB7 Out of Stock Color: Red Size: M Note 7 text     | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3                                                     | 10  | pieces |        | $170.00                  |
      | BB8 In Stock Color: Green Size: L Note 8 text                              | 5   | pieces | $17.00 | $85.00                   |
      | BB9 Out of Stock Color: Blue Size: S Note 9 text                           | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3 BB10 In Stock Color: Red Size: M Note 10 text       | 7   | sets   | $19.00 | $133.00                  |
      | Configurable Product 2 BB11 Out of Stock Color: Green Size: L Note 11 text | 7   | sets   | $19.00 | $133.00                  |
      | Configurable Product 1                                                     | 20  | items  |        | $304.00 -$152.00 $152.00 |
      | BB4 In Stock Color: Red Size: M Note 4 text                                | 10  | items  | $11.00 | $110.00 -$55.00 $55.00   |
      | BB5 Out of Stock Color: Green Size: L Note 5 text                          | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB12 In Stock Color: Blue Size: S Note 12 text                             | 7   | items  | $23.00 | $161.00 -$80.50 $80.50   |
      | Product 13 BB13 Out of Stock Note 13 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 14 BB14 In Stock Note 14 text                                      | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 15 BB15 Out of Stock Note 15 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 16 BB16 In Stock Note 16 text                                      | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
    When I click "Next"
    Then I should see following grid:
      | Item                                      | Qty | Unit   | Price  | Subtotal                 |
      | Product 17 BB17 Out of Stock Note 17 text | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 18 BB18 In Stock Note 18 text     | 11  | sets   | $29.00 | $319.00                  |
      | Product 19 BB19 Out of Stock Note 19 text | 11  | sets   | $29.00 | $319.00                  |
      | Product 20 BB20 In Stock Note 20 text     | 11  | sets   | $29.00 | $319.00                  |
      | Product 21 CC21 In Stock Note 21 text     | 13  | pieces | $31.00 | $403.00                  |
      | Product 22 CC22 In Stock Note 22 text     | 13  | pieces | $31.00 | $403.00                  |
      | Product 23 CC23 In Stock Note 23 text     | 13  | pieces | $31.00 | $403.00                  |
      | Product 24 CC24 In Stock Note 24 text     | 13  | pieces | $31.00 | $403.00                  |
      | Product 25 CC25 In Stock Note 25 text     | 13  | pieces | $31.00 | $403.00                  |
      | Product 26 CC26 In Stock Note 26 text     | 13  | pieces | $31.00 | $403.00                  |
    When I click "Next"
    Then I should see following grid:
      | Item                                  | Qty | Unit   | Price  | Subtotal |
      | Product 29 CC29 In Stock Note 29 text | 13  | pieces | $31.00 | $403.00  |
      | Product 30 CC30 In Stock Note 30 text | 13  | pieces | $31.00 | $403.00  |
      | Product 31 CC31 In Stock Note 31 text | 13  | pieces | $31.00 | $403.00  |
      | Product 32 CC32 In Stock Note 32 text | 13  | pieces | $31.00 | $403.00  |
      | Product 33 CC33 In Stock Note 33 text | 13  | pieces | $31.00 | $403.00  |
      | Product 34 CC34 In Stock Note 34 text | 13  | pieces | $31.00 | $403.00  |
      | Product 35 CC35 In Stock Note 35 text | 13  | pieces | $31.00 | $403.00  |

  Scenario: Check Ungroup similar
    When I click "Ungroup Similar"
    Then I should see "30 total records"
    And I should see following grid:
      | Item                                  | Qty | Unit   | Price  | Subtotal |
      | Product 24 CC24 In Stock Note 24 text | 13  | pieces | $31.00 | $403.00  |
      | Product 25 CC25 In Stock Note 25 text | 13  | pieces | $31.00 | $403.00  |
      | Product 26 CC26 In Stock Note 26 text | 13  | pieces | $31.00 | $403.00  |
      | Product 29 CC29 In Stock Note 29 text | 13  | pieces | $31.00 | $403.00  |
      | Product 30 CC30 In Stock Note 30 text | 13  | pieces | $31.00 | $403.00  |
      | Product 31 CC31 In Stock Note 31 text | 13  | pieces | $31.00 | $403.00  |
      | Product 32 CC32 In Stock Note 32 text | 13  | pieces | $31.00 | $403.00  |
      | Product 33 CC33 In Stock Note 33 text | 13  | pieces | $31.00 | $403.00  |
      | Product 34 CC34 In Stock Note 34 text | 13  | pieces | $31.00 | $403.00  |
      | Product 35 CC35 In Stock Note 35 text | 13  | pieces | $31.00 | $403.00  |
    When I click "Prev"
    Then I should see following grid:
      | Item                                      | Qty | Unit   | Price  | Subtotal                 |
      | Product 14 BB14 In Stock Note 14 text     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 15 BB15 Out of Stock Note 15 text | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 16 BB16 In Stock Note 16 text     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 17 BB17 Out of Stock Note 17 text | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 18 BB18 In Stock Note 18 text     | 11  | sets   | $29.00 | $319.00                  |
      | Product 19 BB19 Out of Stock Note 19 text | 11  | sets   | $29.00 | $319.00                  |
      | Product 20 BB20 In Stock Note 20 text     | 11  | sets   | $29.00 | $319.00                  |
      | Product 21 CC21 In Stock Note 21 text     | 13  | pieces | $31.00 | $403.00                  |
      | Product 22 CC22 In Stock Note 22 text     | 13  | pieces | $31.00 | $403.00                  |
      | Product 23 CC23 In Stock Note 23 text     | 13  | pieces | $31.00 | $403.00                  |
    When I click "Prev"
    Then I should see following grid:
      | Item                                                                       | Qty | Unit   | Price  | Subtotal                 |
      | Configurable Product 1 BB4 In Stock Color: Red Size: M Note 4 text         | 10  | items  | $11.00 | $110.00 -$55.00 $55.00   |
      | Configurable Product 1 BB5 Out of Stock Color: Green Size: L Note 5 text   | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | Configurable Product 2 BB6 In Stock Color: Blue Size: S Note 6 text        | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | Configurable Product 2 BB7 Out of Stock Color: Red Size: M Note 7 text     | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3 BB8 In Stock Color: Green Size: L Note 8 text       | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3 BB9 Out of Stock Color: Blue Size: S Note 9 text    | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3 BB10 In Stock Color: Red Size: M Note 10 text       | 7   | sets   | $19.00 | $133.00                  |
      | Configurable Product 2 BB11 Out of Stock Color: Green Size: L Note 11 text | 7   | sets   | $19.00 | $133.00                  |
      | Configurable Product 1 BB12 In Stock Color: Blue Size: S Note 12 text      | 7   | items  | $23.00 | $161.00 -$80.50 $80.50   |
      | Product 13 BB13 Out of Stock Note 13 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |

  Scenario: Check Availability filter
    Given I open Order History page on the store frontend
    When I click "Check Out" on row "Shopping List 3" in grid "OpenOrdersGrid"
    Then I should see "30 total records"
    When I click "Group Similar"
    And I check "Out of Stock" in Availability filter
    Then I should see "8 total records"
    And I should see following grid:
      | Item                                                                       | Qty | Unit   | Price  | Subtotal                 |
      | Configurable Product 1                                                     | 20  | items  |        | $304.00 -$152.00 $152.00 |
      | BB5 Out of Stock Color: Green Size: L Note 5 text                          | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | Configurable Product 2 BB7 Out of Stock Color: Red Size: M Note 7 text     | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3                                                     | 10  | pieces |        | $170.00                  |
      | BB9 Out of Stock Color: Blue Size: S Note 9 text                           | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 2 BB11 Out of Stock Color: Green Size: L Note 11 text | 7   | sets   | $19.00 | $133.00                  |
      | Product 13 BB13 Out of Stock Note 13 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 15 BB15 Out of Stock Note 15 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 17 BB17 Out of Stock Note 17 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 19 BB19 Out of Stock Note 19 text                                  | 11  | sets   | $29.00 | $319.00                  |
    When click on "Add 2 More Variants"
    Then I should see "8 total records"
    And I should see following grid:
      | Item                                                                       | Qty | Unit   | Price  | Subtotal                 |
      | Configurable Product 1                                                     | 20  | items  |        | $304.00 -$152.00 $152.00 |
      | BB4 In Stock Color: Red Size: M Note 4 text                                | 10  | items  | $11.00 | $110.00 -$55.00 $55.00   |
      | BB5 Out of Stock Color: Green Size: L Note 5 text                          | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB12 In Stock Color: Blue Size: S Note 12 text                             | 7   | items  | $23.00 | $161.00 -$80.50 $80.50   |
      | Configurable Product 2 BB7 Out of Stock Color: Red Size: M Note 7 text     | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3                                                     | 10  | pieces |        | $170.00                  |
      | BB9 Out of Stock Color: Blue Size: S Note 9 text                           | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 2 BB11 Out of Stock Color: Green Size: L Note 11 text | 7   | sets   | $19.00 | $133.00                  |
      | Product 13 BB13 Out of Stock Note 13 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 15 BB15 Out of Stock Note 15 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 17 BB17 Out of Stock Note 17 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 19 BB19 Out of Stock Note 19 text                                  | 11  | sets   | $29.00 | $319.00                  |
    When click on "Add 1 More Variants"
    Then I should see "8 total records"
    And I should see following grid:
      | Item                                                                       | Qty | Unit   | Price  | Subtotal                 |
      | Configurable Product 1                                                     | 20  | items  |        | $304.00 -$152.00 $152.00 |
      | BB4 In Stock Color: Red Size: M Note 4 text                                | 10  | items  | $11.00 | $110.00 -$55.00 $55.00   |
      | BB5 Out of Stock Color: Green Size: L Note 5 text                          | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB12 In Stock Color: Blue Size: S Note 12 text                             | 7   | items  | $23.00 | $161.00 -$80.50 $80.50   |
      | Configurable Product 2 BB7 Out of Stock Color: Red Size: M Note 7 text     | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3                                                     | 10  | pieces |        | $170.00                  |
      | BB8 In Stock Color: Green Size: L Note 8 text                              | 5   | pieces | $17.00 | $85.00                   |
      | BB9 Out of Stock Color: Blue Size: S Note 9 text                           | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 2 BB11 Out of Stock Color: Green Size: L Note 11 text | 7   | sets   | $19.00 | $133.00                  |
      | Product 13 BB13 Out of Stock Note 13 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 15 BB15 Out of Stock Note 15 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 17 BB17 Out of Stock Note 17 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 19 BB19 Out of Stock Note 19 text                                  | 11  | sets   | $29.00 | $319.00                  |

  Scenario: Check Quantity filter
    Given I open Order History page on the store frontend
    When I click "Check Out" on row "Shopping List 3" in grid "OpenOrdersGrid"
    Then I should see "30 total records"
    When I filter Quantity as less than "10"
    Then I should see "13 total records"
    And I should see following grid:
      | Item                                                                       | Qty | Unit   | Price  | Subtotal                 |
      | Configurable Product 1 BB5 Out of Stock Color: Green Size: L Note 5 text   | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | Configurable Product 2 BB6 In Stock Color: Blue Size: S Note 6 text        | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | Configurable Product 2 BB7 Out of Stock Color: Red Size: M Note 7 text     | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3 BB8 In Stock Color: Green Size: L Note 8 text       | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3 BB9 Out of Stock Color: Blue Size: S Note 9 text    | 5   | pieces | $17.00 | $85.00                   |
      | Configurable Product 3 BB10 In Stock Color: Red Size: M Note 10 text       | 7   | sets   | $19.00 | $133.00                  |
      | Configurable Product 2 BB11 Out of Stock Color: Green Size: L Note 11 text | 7   | sets   | $19.00 | $133.00                  |
      | Configurable Product 1 BB12 In Stock Color: Blue Size: S Note 12 text      | 7   | items  | $23.00 | $161.00 -$80.50 $80.50   |
      | Product 13 BB13 Out of Stock Note 13 text                                  | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | Product 14 BB14 In Stock Note 14 text                                      | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |

  Scenario: Check Unit filter
    Given I open Order History page on the store frontend
    When I click "Check Out" on row "Shopping List 3" in grid "OpenOrdersGrid"
    Then I should see "30 total records"
    When I check "set" in Unit filter
    Then I should see "5 total records"
    And I should see following grid:
      | Item                                                                       | Qty | Unit | Price  | Subtotal |
      | Configurable Product 3 BB10 In Stock Color: Red Size: M Note 10 text       | 7   | sets | $19.00 | $133.00  |
      | Configurable Product 2 BB11 Out of Stock Color: Green Size: L Note 11 text | 7   | sets | $19.00 | $133.00  |
      | Product 18 BB18 In Stock Note 18 text                                      | 11  | sets | $29.00 | $319.00  |
      | Product 19 BB19 Out of Stock Note 19 text                                  | 11  | sets | $29.00 | $319.00  |
      | Product 20 BB20 In Stock Note 20 text                                      | 11  | sets | $29.00 | $319.00  |

  Scenario: Check Image preview
    Given I open Order History page on the store frontend
    When I click "Check Out" on row "Shopping List 3" in grid "OpenOrdersGrid"
    And I filter SKU as is equal "BB4"
    And I click on empty space
    And I should not see an "Popover Image" element
    And I hover on "Checkout Line Item Product Link"
    Then I should see an "Popover Image" element
    And I click on empty space
    And I should not see an "Popover Image" element

  Scenario: Check when no image
    When I should see "Some products have not been added to this order. Please create an RFQ to request price." flash message and I close it
    And I scroll to top
    And I filter SKU as is equal "BB14"
    And I click on empty space
    And I should not see an "Popover Image" element
    And I hover on "Checkout Line Item Product Link"
    Then I should not see an "Popover Image" element

  Scenario: Process checkout
    When I select "ORO, Fifth avenue, 10115 Berlin, Germany" from "Select Billing Address"
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" from "Select Shipping Address"
    And I check "Flat Rate" on the checkout page
    And I check "Payment Terms" on the checkout page
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
