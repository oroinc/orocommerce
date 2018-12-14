@community-edition-only
@ticket-BB-7523
@ticket-BB-13978
@ticket-BB-14758
@fixture-OroCheckoutBundle:Products_quick_order_form_ce.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroProductBundle:ProductUnitItemZuluTranslation.yml
@automatically-ticket-tagged
@regression

Feature: Quick order form
  In order to provide customers with ability to quickly start an order
  As customer
  I need to be able to enter products' skus and quantities and start checkout
  I need to be able to see localized product names and units in Import Validation popup

  Scenario: Feature Background
    Given I enable the existing localizations

  Scenario: Submit forms with empty fields to check validation error
    Given I login as AmandaRCole@example.org buyer
    When I click "Quick Order Form"
    Then I should see that "Product Line Item Input Field" contains "Enter Product Name or Item Number" placeholder
    And I should see that "Qty Line Item Input Field" contains "Qty #" placeholder
    And I should see that "Paste Your Order Input Field" contains "Copy and paste your order." placeholder
    When I click "Get Quote"
    Then I should see that "Quick Add Form Validation" contains "Please add at least one item"
    And I reload the page
    When I click "Create Order"
    Then I should see that "Quick Add Form Validation" contains "Please add at least one item"
    And I reload the page
    When I click "Add to List 2"
    Then I should see that "Quick Add Form Validation" contains "Please add at least one item"

  Scenario: Check if the price depends on quantity
    When I click "Quick Order Form"
    And I fill "QuickAddForm" with:
      | SKU1 | psku1 |
    And I wait for products to load
    And I type "1" in "Quick Order Form > QTY1"
    And I click on empty space
    And "PSKU1" product should has "$45.00" value in price field
    And I wait for products to load
    And I type "2" in "Quick Order Form > QTY1"
    And I wait for "PSKU1" price recalculation
    Then "PSKU1" product should has "$90.00" value in price field

  Scenario: Get A Quote from quick order page
    Given I click "Quick Order Form"
    And I fill "QuickAddForm" with:
      | SKU1 | PSKU2 |
      | SKU2 | PSKU2 |
      | SKU3 | PSKU3 |
      | SKU4 | PSKU3 |
    And I wait for products to load
    And I fill "QuickAddForm" with:
      | QTY1  | 2   |
      | QTY2  | 4   |
      | UNIT2 | set |
      | QTY3  | 1   |
      | QTY4  | 1   |
    When I click "Get Quote"
    Then Page title equals to "Request A Quote - Requests For Quote - My Account"
    And I should see that "Phone Number" contains "Phone Number" placeholder
    And I should see that "Role" contains "Role" placeholder
    And I should see that "Note" contains "Note" placeholder
    And I should see that "PO Number" contains "PO Number" placeholder
    And I should see that "Assigned To Input Field" contains "Assigned To" placeholder
    And Request a Quote contains products
      | Product2 | 2 | item |
      | Product2 | 4 | set  |
      | Product3 | 2 | item |
    And I click "Submit Request"
    And I should see "Request has been saved" flash message

  Scenario: Create an order from quick order page
    Given There are products in the system available for order
    And I click "Quick Order Form"
    And I fill "QuickAddForm" with:
      | SKU1 | PSKU1 |
      | SKU2 | PSKU2 |
      | SKU3 | PSKU3 |
      | SKU4 | PSKU3 |
    And I wait for products to load
    And I fill "QuickAddForm" with:
      | QTY1  | 2   |
      | QTY2  | 4   |
      | UNIT2 | set |
      | QTY3  | 1   |
      | QTY4  | 1   |
    When I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And "Order Review" checkout step "Order Summary Products Grid" contains products
      | Product1 | 2 | items |
      | Product2 | 4 | sets  |
      | Product3 | 2 | items |
    And I check "Delete this shopping list after submitting order" on the "Order Review" checkout step and press Submit Order
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Add to shopping list from quick order page
    Given I click "Quick Order Form"
    And I fill "QuickAddForm" with:
      | SKU1 | PSKU1 |
      | SKU2 | PSKU2 |
      | SKU3 | PSKU3 |
      | SKU4 | PSKU3 |
    And I wait for products to load
    And I fill "QuickAddForm" with:
      | QTY1  | 2   |
      | QTY2  | 4   |
      | UNIT2 | set |
      | QTY3  | 1   |
      | QTY4  | 1   |
    When I click "Add to List 2"
    Then I should see "3 products were added (view shopping list)." flash message
    When I open page with shopping list List 2
    Then Buyer is on view shopping list "List 2" page and clicks create order button
    And Page title equals to "Billing Information - Checkout"

  Scenario: Get A Quote from quick order page with product without price
    Given I click "Quick Order Form"
    And I fill "QuickAddForm" with:
      | SKU1 | PSKUwithlowercase |
    And I wait for products to load
    When I click "Get Quote"
    Then Page title equals to "Request A Quote - Requests For Quote - My Account"
    And Request a Quote contains products
      | Product4 | 1 | item |
    And I click "Submit Request"
    And I should see "Request has been saved" flash message

  Scenario: Create an order from quick order page with product without price
    Given I click "Quick Order Form"
    And I fill "QuickAddForm" with:
      | SKU1 | PSKUwithlowercase |
    And I wait for products to load
    And I fill "QuickAddForm" with:
      | QTY1 | 1     |
    When I click "Create Order"
    Then I should see "Cannot create order because Shopping List has no items with price" flash message
    When I fill "QuickAddForm" with:
      | SKU1 | PSKUwithlowercase |
      | SKU2 | PSKU1             |
    And I wait for products to load
    And I fill "QuickAddForm" with:
      | QTY1 | 1     |
      | QTY2 | 2     |
    And I click "Create Order"
    Then Page title equals to "Billing Information - Checkout"
    And I should see "Some products have not been added to this order. Please create an RFQ to request price." flash message
    And "Billing Information" checkout step "Order Summary Products Grid" contains products
      | Product1 | 2 | items |

  Scenario: Verify disabled products are cannot be added via quick order form
    Given I click "Quick Order Form"
    When I fill "QuickAddForm" with:
      | SKU1 | pskulowercaseonly |
    And I wait for products to load
    And I fill "QuickAddForm" with:
      | QTY1 | 1     |
    And I click "Get Quote"
    Then I should see text matching "Item Number Cannot Be Found"
    And I click "Quick Order Form"
    When I fill "QuickAddForm" with:
      | SKU1 | pskulowercaseonly |
    And I wait for products to load
    And I fill "QuickAddForm" with:
      | QTY1 | 1     |
    And I click "Create Order"
    Then I should see text matching "Item Number Cannot Be Found"
    And I click "Quick Order Form"
    When I fill "QuickAddForm" with:
      | SKU1 | pskulowercaseonly |
    And I wait for products to load
    And I fill "QuickAddForm" with:
      | QTY1 | 1     |
    And I click on "Shopping List Dropdown"
    And I click "Add to List 2"
    Then I should see text matching "Item Number Cannot Be Found"

  Scenario: User is able to use Quick Order Form (copy paste) and create RFQ
    Given I click "Quick Order Form"
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | PSKU1 5 |
    And I click "Verify Order"
    And I click "Get Quote"
    Then Page title equals to "Request A Quote - Requests For Quote - My Account"
    And Request a Quote contains products
      | Product1 | 5 | item |
    And I click "Submit Request"
    And I should see "Request has been saved" flash message

  Scenario: User is able to use Quick Order Form (copy paste) with specific unit info
    Given I click "Quick Order Form"
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | PSKU2 3 set |
    And I click "Verify Order"
    And I click "Get Quote"
    Then Page title equals to "Request A Quote - Requests For Quote - My Account"
    And Request a Quote contains products
      | Product2 | 3 | set |
    And I click "Submit Request"
    And I should see "Request has been saved" flash message

  Scenario: User is able to use Quick Order Form (copy paste) and create RFQ with product without price
    Given I click "Quick Order Form"
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | PSKUwithlowercase 2 |
    And I click "Verify Order"
    And I click "Get Quote"
    Then Page title equals to "Request A Quote - Requests For Quote - My Account"
    And Request a Quote contains products
      | Product4 | 2 | item |
    And I click "Submit Request"
    And I should see "Request has been saved" flash message

  Scenario: Check format validation
    Given I click "Quick Order Form"
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | PSKU1 5 test |
    And I click "Verify Order"
    Then I should see that "Quick Add Copy Paste Validation" contains "Some of the products SKUs or units you have provided were not found. Correct them and try again."
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | PSKU1 test item |
    Then I should see that "Quick Add Copy Paste Validation" contains "Invalid format"
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | test 5 item |
    And I click "Verify Order"
    Then I should see that "Quick Add Copy Paste Validation" contains "Some of the products SKUs or units you have provided were not found. Correct them and try again."
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | test |
    Then I should see that "Quick Add Copy Paste Validation" contains "Invalid format"
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | test test test |
    Then I should see that "Quick Add Copy Paste Validation" contains "Invalid format"

  Scenario: Check copy paste validation if use semicolons or commas
    Given I click "Quick Order Form"
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | PSKU1,2,item |
    And I click "Verify Order"
    And I wait for products to load
    And "QuickAddForm" must contains values:
      | SKU1  | psku1 - Product1 |
      | QTY1  | 2                |
      | UNIT1 | item             |
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | PSKU2;2;item |
    And I click "Verify Order"
    And I wait for products to load
    And "QuickAddForm" must contains values:
      | SKU1  | psku1 - Product1 |
      | QTY1  | 2                |
      | UNIT1 | item             |
      | SKU2  | PSKU2 - Product2 |
      | QTY2  | 2                |
      | UNIT2 | item             |

  Scenario: Check upload import validation with empty file
    Given I click "Quick Order Form"
    And I click "Get Directions"
    And I should see that "UiDialog Title" contains "Import Excel .CSV File"
    And I download "the CSV template"
    And I close ui dialog
    And I fill quick order template with data:
      | Item Number | Quantity | Unit |
    When I import file for quick order
    Then I should see text matching "We have not been able to identify any product references in the uploaded file."
    And I close ui dialog

  Scenario: Check upload import validation in case when all rows are in wrong format
    Given I click "Quick Order Form"
    And I click "Get Directions"
    And I should see that "UiDialog Title" contains "Import Excel .CSV File"
    And I download "the CSV template"
    And I close ui dialog
    And I fill quick order template with data:
      | Item Number   | Quantity      | Unit          |
      | Wrong format1 | Wrong format1 | Wrong format1 |
      | Wrong format2 | Wrong format2 | Wrong format2 |
      | Wrong format3 | Wrong format3 | Wrong format3 |
    When I import file for quick order
    Then I should see text matching "We have not been able to identify any product references in the uploaded file."
    And I close ui dialog

  Scenario: Check upload import validation in case when just some rows are in wrong format
    Given I click "Quick Order Form"
    And I click "Get Directions"
    And I should see that "UiDialog Title" contains "Import Excel .CSV File"
    And I download "the CSV template"
    And I close ui dialog
    And I fill quick order template with data:
      | Item Number   | Quantity      | Unit          |
      | Wrong format1 | Wrong format1 | Wrong format1 |
      | Wrong format2 | Wrong format2 | Wrong format2 |
      | PSKU1         | 2             | item          |
    When I import file for quick order
    Then I should see text matching "We have not been able to identify some of the product references in the uploaded file. Please review the data below and make necessary corrections to upload again."
    And I close ui dialog

  Scenario: Check upload import validation using unsupported file type
    Given I click "Quick Order Form"
    When I import unsupported file for quick order
    Then I should see that "UiDialog Title" contains "Import Validation"
    And I should see text matching "We have not been able to identify any product references in the uploaded file."
    And I close ui dialog

  Scenario: Check product and unit names are localized in Import Validation popup
    Given I click "Localization Switcher"
    And I select "Localization 1" localization
    And I click "Quick Order Form"
    And I click "Get Directions"
    And I should see that "UiDialog Title" contains "Import Excel .CSV File"
    And I download "the CSV template"
    And I close ui dialog
    And I fill quick order template with data:
      | Item Number | Quantity | Unit         |
      | PSKU1       | 1        | item         |
      | PSKU1       | 1        | item (lang1) |
      | PSKU2       | 3        | item         |
      | PSKU2       | 3        | item (lang1) |
    When I import file for quick order
    Then I should see next rows in "Quick Order Import Validation" table
      | Item #                           | Qty | Unit         | Price    |
      | PSKU1 - Product1 (Localization1) | 2   | item (lang1) | US$90.00 |
      | PSKU2 - Product2                 | 6   | item (lang1) | N/A      |
    And I click "Add to Form"
    And "QuickAddForm" must contains values:
      | SKU1  | psku1 - Product1 (Localization1) |
      | QTY1  | 2                                |
      | UNIT1 | item (lang1)                     |
      | SKU2  | PSKU2 - Product2                 |
      | QTY2  | 6                                |
      | UNIT2 | item (lang1)                     |

  Scenario: Check unit names are localized in copy paste form
    Given I click "Localization Switcher"
    And I select "Zulu" localization
    And I click "Quick Order Form"
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | PSKU2,1,item |
    And I click "Verify Order"
    And I wait for products to load
    Then "QuickAddForm" must contains values:
      | SKU1  | PSKU2 - Product2 |
      | QTY1  | 1                |
      | UNIT1 | Element          |
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | PSKU2;3;Element |
    And I click "Verify Order"
    And I wait for products to load
    Then "QuickAddForm" must contains values:
      | SKU1  | PSKU2 - Product2 |
      | QTY1  | 4                |
      | UNIT1 | Element          |
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | PSKU2;2;element |
    And I click "Verify Order"
    And I wait for products to load
    Then "QuickAddForm" must contains values:
      | SKU1  | PSKU2 - Product2 |
      | QTY1  | 6                |
      | UNIT1 | Element          |

  #@todo check with Serhii Polishchuk how can we manipulate xlsx files
# Scenario: Verify user is able to upload .xlsx file
#   Given I login as AmandaRCole@example.org buyer
#   And I click "Quick Order Form"
#   And I fill xlsx template with data:
#     | Item Number | Quantity | Unit |
#     | PSKU1 | 2 | item |
#     | PSKU2 | 3 | set |
#   When I import products file
#   Then I should see product "PSKU1" with quantity "2" and "item" unit in quick order form
#   And I should see product "PSKU2" with quantity "3" and "set" unit in quick order form

  #@todo check with Serhii Polishchuk how can we manipulate ods files
# Scenario: Verify user is able to upload .ods file
#   Given I login as AmandaRCole@example.org buyer
#   And I click "Quick Order Form"
#   And I fill ods template with data:
#     | Item Number | Quantity | Unit |
#     | PSKU1 | 2 | item |
#     | PSKU2 | 3 | set |
#   When I import products file
#   Then I should see product "PSKU1" with quantity "2" and "item" unit in quick order form
#   And I should see product "PSKU2" with quantity "3" and "set" unit in quick order form
