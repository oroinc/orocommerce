@regression
@ticket-BB-17102
@fixture-OroLocaleBundle:GermanLocalization.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroProductBundle:product-with-fractional-quantity.yml

Feature: Localized fractional product quantity
  In order to provide same user experience of ordering products in different localizations
  As an Administrator
  I need to be able to create an order for fractional quantity of a product using number format for current localization
  As a Buyer
  I need to be able to order fractional quantity of a product using number format for the current localization

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Feature Background
    Given I operate as the Admin
    And I enable the existing localizations
    And I login as administrator
    And I go to System/Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill "Configuration Localization Form" with:
      | Enabled Localizations | German_Loc |
      | Default Localization  | German_Loc |
    When I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Inventory level inline editing with fractional quantity
    Given I operate as the Admin
    When I go to Inventory/ Manage Inventory
    And I should see following grid:
      | Sku   | Name         | Inventory Status | Quantity | Unit      | Warehouse      |
      | PKILO | Product Kilo | In Stock         | 100      | kilograms | Test Warehouse |
    When I start inline editing on "Test Warehouse" "Quantity" field I should see "100,00" value
    And I click on empty space
    Then I edit "Test Warehouse" Quantity as "50,40" with click on empty space
    When I start inline editing on "Test Warehouse" "Quantity" field I should see "50,40" value
    And I click on empty space
    Then I should see following records in grid:
      | 50,4 |

  Scenario: Create backend order with fractional quantity product
    When I go to Sales/ Orders
    And I click "Create Order"
    And I click "Add Product"
    When fill "Order Form" with:
      | Customer | Company A |
      | Product  | PKILO     |
      | Quantity | 1.000,56  |
    And save and close form
    And I click "Save" in modal window
    Then should see "Order has been saved" flash message
    When I click "Line Items"
    Then I should see following "Backend Order Line Items Grid" grid:
      | SKU   | Product      | Quantity | Product Unit Code | Price   |
      | PKILO | Product Kilo | 1.000,56 | kg                | 50,00 $ |
    Then I should see "Subtotal 50.028,00 $"

  Scenario: Add product with fractional quantity into Shopping List
    Given I operate as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I type "PKILO" in "search"
    And I click "Search Button"
    When I fill "ProductLineItemForm" with:
      | Quantity | 1000,73 |
    Then I should see "Your Price: 50,00 $ / kilogram" for "PKILO" product
    And I should see "Listed Price: 100,00 $ / kilogram" for "PKILO" product
    When I click on "Add to Shopping List"
    Then I should see "Product has been added to" flash message
    When I fill "ProductLineItemForm" with:
      | Quantity | 1005,73 |
    # Is not working properly with the ProductQuantityField element
    Then I focus on "oro_product_frontend_line_item[quantity]" field and press Enter key
    Then I should see "Product has been added to" flash message
    Then I open page with shopping list Shopping List
    And I click on "Shopping List Line Item 1 Quantity"
    And the "Shopping List Line Item 1 Quantity Input" field element should contain "1005,73"
    And I click on "Shopping List Line Item 1 Save Changes Button"
    And I should see "Subtotal 50.286,50 $"
    And I should see "Total 50.286,50 $"

  Scenario: Update product quantity in Shopping List:
    When I type "PKILO" in "search"
    And I click "Search Button"
    And I fill "ProductLineItemForm" with:
      | Quantity | 1.000,83 |
    Then I should see "Your Price: 50,00 $ / kilogram" for "PKILO" product
    And I should see "Listed Price: 100,00 $ / kilogram" for "PKILO" product
    When I click "Update Shopping List" in "ShoppingListButtonGroup" element
    Then I should see "Record has been successfully updated" flash message
    Then I open page with shopping list Shopping List
    And I click on "Shopping List Line Item 1 Quantity"
    And the "Shopping List Line Item 1 Quantity Input" field element should contain "1000,83"
    And I click on "Shopping List Line Item 1 Save Changes Button"
    And I should see "Subtotal 50.041,50 $"
    And I should see "Total 50.041,50 $"

  Scenario: Create backend order with fractional quantity product
    Given I operate as the Admin
    Then I go to Sales/ Shopping Lists
    And I click "View" on row "Shopping List" in grid
    Then I click "Edit" on row "PKILO" in grid
    And I fill form with:
      | Quantity | .1000,83 |
    Then I click "Save" in modal window
    And I should see "This value is not valid."
    Then I fill form with:
      | Quantity | 1.000,83 |
    Then I click "Save" in modal window
    And I should see "Line item has been updated"

  Scenario: Use inline edit popup to change product's quantity
    Given I operate as the Buyer
    When I type "PKILO" in "search"
    And I click "Search Button"
    And I scroll to top
    When I click "Shopping List Edit"
    Then I should see "1.000,83kg" in the "Shopping List Edit Popup Rows" element
    And I fill "Shopping List Form" with:
      | List     | Shopping List |
      | Unit     | kilogram      |
      | Quantity | 2,34          |
    And click "Item Add"
    Then I should not see "Validation Failed"
    Then I should see "2,34kg" in the "Shopping List Edit Popup Rows" element
    And I click "Close" in modal window
    Then I open page with shopping list Shopping List
    And I click on "Shopping List Line Item 1 Quantity"
    And the "Shopping List Line Item 1 Quantity Input" field element should contain "2,34"
    And I click on "Shopping List Line Item 1 Save Changes Button"
    And I should see "Subtotal 234,00 $"
    And I should see "Total 234,00 $"
    When I type "PKILO" in "search"
    And I click "Search Button"
    When I click "Shopping List Edit"
    Then I should see "2,34kg" in the "Shopping List Edit Popup Rows" element
    And I click "Item Edit"
    And I fill "Shopping List First Row Form" with:
      | Quantity | 1,56 |
    And I click "First Row Accept Button"
    And I should see "Record has been successfully updated"
    Then I should see "1,56kg" in the "Shopping List Edit Popup Rows" element
    And I click "Close" in modal window

  Scenario: Check if price recalculated on Quick Order form
    When I click "Quick Order Form"
    And I fill "QuickAddForm" with:
      | SKU1 | PKILO |
    And I wait for products to load
    And I type "1,53" in "Quick Order Form > QTY1"
    And I click on empty space
    And "PKILO" product should has "153,00 $" value in price field
    And I wait for products to load
    And I type "20,45" in "Quick Order Form > QTY1"
    And I wait for "PKILO" price recalculation
    Then "PKILO" product should has "1.022,50 $" value in price field
    And I reload the page

  Scenario: Check Quick Order form past data works with localized qty
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | PKILO 1,45 kilogram |
    And I click "Verify Order"
    Then quick order form contains product with sku PKILO and quantity 1,45
    And I reload the page

  Scenario: Check Quick Order form upload your order works with localized qty
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | PKILO 1,45 kilogram |
    And I click "Verify Order"
    Then quick order form contains product with sku PKILO and quantity 1,45
    And I reload the page

    When I fill "QuickAddForm" with:
      | SKU1 | PKILO |
    And I wait for products to load
    And I type "1,53" in "Quick Order Form > QTY1"
    And I click on empty space
    And "PKILO" product should has "153,00 $" value in price field
    And I wait for products to load
    And I type "20,45" in "Quick Order Form > QTY1"
    And I wait for "PKILO" price recalculation
    Then "PKILO" product should has "1.022,50 $" value in price field
    And I reload the page

  Scenario: Check Quick Order form past data works with localized qty
    Given I click "Get Directions"
    And I should see that "UiDialog Title" contains "Import Excel .CSV File"
    And I download "the CSV template"
    And I close ui dialog
    And I fill quick order template with data:
      | Item Number | Quantity | Unit     |
      | PKILO       | 1,45     | kilogram |
    When I import file for quick order
    Then I should see next rows in "Quick Order Import Validation" table
      | Item #               | Qty  | Unit     | Price    |
      | PKILO - Product Kilo | 1,45 | kilogram | 145,00 $ |
    And I click "Add to Form"
    And "QuickAddForm" must contains values:
      | SKU1  | PKILO - Product Kilo |
      | QTY1  | 1,45                 |
      | UNIT1 | kilogram             |

  Scenario: Create order based on the existing Shopping List
    When I open page with shopping list "Shopping List"
    Then I should see "Subtotal 156,00 $"
    And I should see "Total 156,00 $"
    And I click on "Shopping List Line Item 1 Quantity"
    And the "Shopping List Line Item 1 Quantity Input" field element should contain "1,56"
    And the "Shopping List Line Item 1 Unit Select" field element should contain "kg"
    And I click on "Shopping List Line Item 1 Save Changes Button"
    When I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    Then Checkout "Order Summary Products Grid" should contain products:
      | Product Kilo | 1,56 | kg | 100,00 $ |
    When I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    When I click "click here to review"
    Then I should see "Subtotal 156,00 $"
    And I should see following "Order Line Items Grid" grid:
      | Product                    | Quantity       | Price    |
      | Product Kilo Item #: PKILO | 1,56 kilograms | 100,00 $ |

  Scenario: Create Request For Quote
    When I type "PKILO" in "search"
    And I click "Search Button"
    When I fill "ProductLineItemForm" with:
      | Quantity | 1,01 |
    Then I should see "Your Price: 100,00 $ / kilogram" for "PKILO" product
    And I should see "Listed Price: 100,00 $ / kilogram" for "PKILO" product
    And I fill "ProductLineItemForm" with:
      | Quantity | 15,01 |
    Then I should see "Your Price: 50,00 $ / kilogram" for "PKILO" product
    And I should see "Listed Price: 100,00 $ / kilogram" for "PKILO" product
    When I fill "ProductLineItemForm" with:
      | Quantity | 1,56 |
    Then I should see "Your Price: 100,00 $ / kilogram" for "PKILO" product
    And I should see "Listed Price: 100,00 $ / kilogram" for "PKILO" product
    When I click on "Add to Shopping List"
    Then I should see "Product has been added to" flash message
    When I open page with shopping list "Shopping List"
    Then I should see "Subtotal 156,00 $"
    And I should see "Total 156,00 $"
    And I click "More Actions"
    When I click "Request Quote"
    And I fill form with:
      | First Name    | Amanda                  |
      | Last Name     | Cole                    |
      | Email Address | AmandaRCole@example.org |
      | Company       | Company A               |
      | PO Number     | Test RFQ                |
      | Assigned To   | Amanda Cole             |
    And I should see "QTY: 1,56 kilogram"
    And I should see "Listed Price: 100,00 $"
    And I click "Submit Request"
    Then I should see "Request has been saved" flash message
    And I should see "1,56 kg"
    And I click "Requests For Quote"
    Then I should see following grid:
      | PO Number | Status    |
      | Test RFQ  | Submitted |

  Scenario: Create Order from Request For Quote
    Given I operate as the Admin
    When I go to Sales / Requests For Quote
    And I show column Customer Status in grid
    Then I should see following grid:
      | PO Number | Customer Status |
      | Test RFQ  | Submitted       |
    When I click view "Test RFQ" in grid
    And I click on "RFQ Create Order"
    Then "Order Form" must contains values:
      | Quantity | 1 |
    When I click "Order Form Line Item 1 Offer 1"
    Then "Order Form" must contains values:
      | Quantity | 1,56 |
    When I fill "Order Form" with:
      | Quantity | 3,05 |
    And I save and close form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    And I should see "3,05 kg"

  Scenario: Create Quote from Request For Quote
    When I go to Sales / Requests For Quote
    And I show column Customer Status in grid
    Then I should see following grid:
      | PO Number | Customer Status |
      | Test RFQ  | Submitted       |
    When I click view "Test RFQ" in grid
    And I click "Create Quote"
    Then "Quote Line Items" must contains values:
      | Quantity | 1,56 |
    When fill "Quote Line Items" with:
      | Unit Price | 90   |
      | Quantity   | 1,78 |
    And I save and close form
    And I click "Save" in modal window
    And I click "Send to Customer"
    And click "Send"
    Then I should see "Quote #1 successfully sent to customer" flash message
    And should see following "Quote Line Item Grid" grid:
      | SKU   | Product      | Quantity        | Price   |
      | PKILO | Product Kilo | 1,78 kg or more | 90,00 $ |

  Scenario: Start checkout from the Quote on front store
    Given I operate as the Buyer
    And I click "Quotes"
    And I click view Test RFQ in grid
    When I click "Accept and Submit to Order"
    And I click "Submit"
    Then Checkout "Order Summary Products Grid" should contain products:
      | Product Kilo | 1,78 | kg | 90,00 $ | 160,20 $ |
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    When I click "click here to review"
    Then I should see "Subtotal 160,20 $"

  Scenario: Create and edit quote in admin area
    Given I operate as the Admin
    When I go to Sales / Quotes
    And I click "Create Quote"
    And I fill "Quote Form" with:
      | LineItemProduct  | PKILO |
      | LineItemQuantity | 1,56  |
    And I save and close form
    And agree that shipping cost may have changed
    Then I should see "Quote has been saved" flash message
    And I should see "PKILO Product Kilo 1,56 kg or more 100,00 $"
    When I click "Edit"
    And I fill "Quote Form" with:
      | LineItemQuantity | 2,77 |
    And I click "Submit"
    And agree that shipping cost may have changed
    And I should see "PKILO Product Kilo 2,77 kg or more 100,00 $"
