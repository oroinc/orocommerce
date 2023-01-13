@community-edition-only
@ticket-BB-7523
@ticket-BB-13978
@ticket-BB-14758
@ticket-BB-16275
@ticket-BAP-21444
@ticket-BB-21713
@fixture-OroCheckoutBundle:Products_quick_order_form_ce.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroProductBundle:ProductUnitItemZuluTranslation.yml
@automatically-ticket-tagged
@regression

Feature: Quick order form

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I enable the existing localizations

  Scenario: Submit forms with empty fields to check validation error
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I click "Quick Order Form"
    Then should see that "Quick Add Row Product Field" contains "Enter Product Name or Item Number" placeholder
    And should see that "Quick Add Row Quantity Field" contains "Qty #" placeholder
    And should see that "Paste Your Order Input Field" contains "Copy and paste your order." placeholder
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
    And I fill "Quick Order Form" with:
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
    And I fill "Quick Order Form" with:
      | SKU1 | PSKU2 |
      | SKU2 | PSKU2 |
      | SKU3 | PSKU3 |
      | SKU4 | PSKU3 |
    And I wait for products to load
    And I fill "Quick Order Form" with:
      | QTY1  | 2   |
      | QTY2  | 4   |
      | UNIT2 | set |
      | QTY3  | 1   |
      | QTY4  | 1   |
    When I click "Get Quote"
    Then Page title equals to "Request A Quote - Requests For Quote - My Account"
    And should see that "Phone Number" contains "Phone Number" placeholder
    And should see that "Role" contains "Role" placeholder
    And should see that "Note" contains "Note" placeholder
    And should see that "PO Number" contains "PO Number" placeholder
    And should see that "Assigned To Input Field" contains "Assigned To" placeholder
    And Request a Quote contains products
      | Product2 | 2 | item |
      | Product2 | 4 | set  |
      | Product3 | 2 | item |
    And I click "Submit Request"
    And I should see "Request has been saved" flash message

  Scenario: Create an order from quick order page
    Given There are products in the system available for order
    And I click "Quick Order Form"
    And I fill "Quick Order Form" with:
      | SKU1 | PSKU1 |
      | SKU2 | PSKU2 |
      | SKU3 | PSKU3 |
      | SKU4 | PSKU3 |
    And I wait for products to load
    And I fill "Quick Order Form" with:
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
      | Product1`"'&йёщ®&reg;> | 2 | items |
      | Product2               | 4 | sets  |
      | Product3               | 2 | items |
    And I check "Delete this shopping list after submitting order" on the "Order Review" checkout step and press Submit Order
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Add to shopping list from quick order page
    Given I click "Quick Order Form"
    And I fill "Quick Order Form" with:
      | SKU1 | PSKU1 |
      | SKU2 | PSKU2 |
      | SKU3 | PSKU3 |
      | SKU4 | PSKU3 |
    And I wait for products to load
    And I fill "Quick Order Form" with:
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
    And I fill "Quick Order Form" with:
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
    And I fill "Quick Order Form" with:
      | SKU1 | PSKUwithlowercase |
    And I wait for products to load
    And I fill "Quick Order Form" with:
      | QTY1 | 1 |
    When I click "Create Order"
    Then I should see "Cannot create order because Shopping List has no items with price" flash message
    When I fill "Quick Order Form" with:
      | SKU1 | PSKUwithlowercase |
      | SKU2 | PSKU1             |
    And I wait for products to load
    And I fill "Quick Order Form" with:
      | QTY1 | 1 |
      | QTY2 | 2 |
    And I click "Create Order"
    Then Page title equals to "Billing Information - Checkout"
    And I should see "Some products have not been added to this order. Please create an RFQ to request price." flash message
    And "Billing Information" checkout step "Order Summary Products Grid" contains products
      | Product1`"'&йёщ®&reg;> | 2 | items |

  Scenario: Verify disabled products are cannot be added via quick order form
    Given I click "Quick Order Form"
    When I fill "Quick Order Form" with:
      | SKU1 | pskulowercaseonly |
    And I wait for products to load
    And I fill "Quick Order Form" with:
      | QTY1 | 1 |
    And I click "Get Quote"
    Then I should see text matching "Item number cannot be found"
    And I click "Quick Order Form"
    When I fill "Quick Order Form" with:
      | SKU1 | pskulowercaseonly |
    And I wait for products to load
    And I fill "Quick Order Form" with:
      | QTY1 | 1 |
    And I click "Create Order"
    Then I should see text matching "Item number cannot be found"
    And I click "Quick Order Form"
    When I fill "Quick Order Form" with:
      | SKU1 | pskulowercaseonly |
    And I wait for products to load
    And I fill "Quick Order Form" with:
      | QTY1 | 1 |
    And I click on "Shopping List Dropdown"
    And I click "Add to List 2"
    Then I should see text matching "Item number cannot be found"

  Scenario: User is able to use Quick Order Form (copy paste) and create RFQ
    Given I click "Quick Order Form"
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | PSKU1 5\nPSKU3 2 |
    And I click "Verify Order"
    And I click "Get Quote"
    Then Page title equals to "Request A Quote - Requests For Quote - My Account"
    And Request a Quote contains products
      | Product1`"'&йёщ®&reg;> | 5 | item |
      | Product3               | 2 | item |
    And I click "Submit Request"
    And I should see "Request has been saved" flash message

  Scenario: Create an order from quick order page (copy paste) with non exists quantity
    Given I click "Quick Order Form"
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | SKU123 1 item |
    And I click "Verify Order"
    And I wait for products to load
    Then "Quick Order Form" must contains values:
      | SKU1      | SKU123 - 400-Watt Bulb Work Light |
      | QTY1      | 1                                 |
      | UNIT1     | item                              |
      | SUBTOTAL1 | N/A                               |
    When I click "Create Order"
    Then I should see "Cannot create order because Shopping List has no items with price" flash message

  Scenario: Create an order from quick order page (import) with non exists quantity
    Given I click "Quick Order Form"
    And I click "Get Directions"
    And I should see that "UiDialog Title" contains "Import Excel .CSV File"
    And I download "the CSV template"
    And I close ui dialog
    And I fill quick order template with data:
      | Item Number | Quantity | Unit |
      | SKU123      | 1        | item |
    When I import file for quick order
    Then "Quick Order Form" must contains values:
      | SKU1      | SKU123 - 400-Watt Bulb Work Light |
      | QTY1      | 1                                 |
      | UNIT1     | item                              |
      | SUBTOTAL1 | N/A                               |
    When I click "Create Order"
    Then I should see "Cannot create order because Shopping List has no items with price" flash message

  Scenario: User is able to use Quick Order Form (copy paste) with specific unit info
    Given I click "Quick Order Form"
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | PSKU2 3 set |
    And I click "Verify Order"
    And I wait for products to load
    Then "Quick Order Form" must contains values:
      | SKU1  | PSKU2 - Product2 |
      | QTY1  | 3                |
      | UNIT1 | set              |
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
    Then "Quick Order Form" must contains values:
      | SKU1  | PSKU1 - Product1`"'&йёщ®&reg;> |
      | QTY1  | 5                              |
      | UNIT1 | --                             |
    And I should see "Unit 'test' doesn't exist for product PSKU1."
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | PSKU1 test item |
    Then I should see that "Quick Add Copy Paste Validation" contains "Invalid format"
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | test 5 item |
    And I click "Verify Order"
    Then "Quick Order Form" must contains values:
      | SKU1  | PSKU1 - Product1`"'&йёщ®&reg;> |
      | QTY1  | 5                              |
      | UNIT1 | --                             |
      | SKU2  | TEST                           |
      | QTY2  | 5                              |
      | UNIT2 | --                             |
    And I should see "Item number cannot be found."
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | test |
    Then I should see that "Quick Add Copy Paste Validation" contains "Invalid format"
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | test test test |
    Then I should see that "Quick Add Copy Paste Validation" contains "Invalid format"

  Scenario: Check zero quantity items validation
    Given I click "Quick Order Form"
    When I fill in "Paste your order" with:
      """
      PSKU1,0,item
      PSKU1,0,set
      PSKU1,0
      PSKU2,2,set
      """
    And I click "Verify Order"
    And I wait for products to load
    Then "Quick Order Form" must contains values:
      | SKU1  | PSKU1 - Product1`"'&йёщ®&reg;> |
      | QTY1  | 0                              |
      | UNIT1 | item                           |
      | SKU2  | PSKU1 - Product1`"'&йёщ®&reg;> |
      | QTY2  | 0                              |
      | UNIT2 | --                             |
      | SKU3  | PSKU2 - Product2               |
      | QTY3  | 2                              |
      | UNIT3 | set                            |
    And I should see "Quantity should be greater than 0"
    Then the "Copy and paste your order." field should contain:
      """
      PSKU1,0,item
      PSKU1,0,set
      PSKU1,0
      PSKU2,2,set
      """

  Scenario: Check copy paste validation if use semicolons or commas
    Given I click "Quick Order Form"
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | PSKU1,2,item |
    And I click "Verify Order"
    And I wait for products to load
    And "Quick Order Form" must contains values:
      | SKU1  | PSKU1 - Product1`"'&йёщ®&reg;> |
      | QTY1  | 2                              |
      | UNIT1 | item                           |
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | PSKU2;2;item |
    And I click "Verify Order"
    And I wait for products to load
    And "Quick Order Form" must contains values:
      | SKU1  | PSKU1 - Product1`"'&йёщ®&reg;> |
      | QTY1  | 2                              |
      | UNIT1 | item                           |
      | SKU2  | PSKU2 - Product2               |
      | QTY2  | 2                              |
      | UNIT2 | item                           |

  Scenario: Merge quantities for existing items on verifying order from copy paste form
    Given I click "Quick Order Form"
    And I fill in "Paste your order" with:
      """
      PSKU1,2,item
      PSKU2,2,set
      """
    And I click "Verify Order"
    And I wait for products to load
    And "Quick Order Form" must contains values:
      | SKU1  | PSKU1 - Product1`"'&йёщ®&reg;> |
      | QTY1  | 2                              |
      | UNIT1 | item                           |
      | SKU2  | PSKU2 - Product2               |
      | QTY2  | 2                              |
      | UNIT2 | set                            |
    And I fill in "Paste your order" with:
      """
      PSKU1,3
      PSKU2,3
      """
    And I click "Verify Order"
    And I wait for products to load
    And "Quick Order Form" must contains values:
      | SKU1  | PSKU1 - Product1`"'&йёщ®&reg;> |
      | QTY1  | 5                              |
      | UNIT1 | item                           |
      | SKU2  | PSKU2 - Product2               |
      | QTY2  | 2                              |
      | UNIT2 | set                            |
      | SKU3  | PSKU2 - Product2               |
      | QTY3  | 3                              |
      | UNIT3 | item                           |

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

  Scenario: Check upload import validation in case when all rows are in wrong format
    Given I click "Quick Order Form"
    And I click "Get Directions"
    And I should see that "UiDialog Title" contains "Import Excel .CSV File"
    And I download "the CSV template"
    And I close ui dialog
    And I fill quick order template with data:
      | Item Number   | Quantity      | Unit          |
      | Wrong format1 | Wrong format1 | Wrong format1 |
      |               | Wrong format2 | Wrong format2 |
      | Wrong format3 | Wrong format3 | Wrong format3 |
    When I import file for quick order
    Then "Quick Order Form" must contains values:
      | SKU1  | WRONG FORMAT1 |
      | QTY1  | 0             |
      | UNIT1 | --            |
      | SKU2  |               |
      | QTY2  | 0             |
      | UNIT2 | --            |
      | SKU3  | WRONG FORMAT3 |
      | QTY3  | 0             |
      | UNIT3 | --            |
    And I should see that "Quick Add Form Validation" contains "Item number cannot be found"

  Scenario: Check upload import validation in case when just some rows are in wrong format
    Given I click "Quick Order Form"
    And I click "Get Directions"
    And I should see that "UiDialog Title" contains "Import Excel .CSV File"
    And I download "the CSV template"
    And I close ui dialog
    And I fill quick order template with data:
      | Item Number   | Quantity      | Unit          |
      | Wrong format1 | Wrong format1 | Wrong format1 |
      |               | 2             | Wrong format2 |
      | PSKU1         | 2             | item          |
    When I import file for quick order
    Then "Quick Order Form" must contains values:
      | SKU1  | WRONG FORMAT1                  |
      | QTY1  | 0                              |
      | UNIT1 | --                             |
      | SKU2  |                                |
      | QTY2  | 2                              |
      | UNIT2 | --                             |
      | SKU3  | PSKU1 - Product1`"'&йёщ®&reg;> |
      | QTY3  | 2                              |
      | UNIT3 | item                           |
    And I should see that "Quick Add Form Validation" contains "Item number cannot be found"

  Scenario: Check upload import validation using unsupported file type
    Given I click "Quick Order Form"
    When I import unsupported file for quick order
    Then I should see "The mime type of the file is invalid (\"image/jpeg\"). Allowed mime types are \"text/csv\", \"text/plain\", \"application/zip\", \"application/vnd.oasis.opendocument.spreadsheet\", \"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet\", \"application/octet-stream\"."

  Scenario: Ensure the product has a second unsell unit
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products/ Products
    When click edit "PSKU1" in grid
    And I should see "Add More Rows" element inside "Additional Units Form Section" element
    And set Additional Unit with:
      | Unit | Precision | Rate |
      | Set  | 0         | 10   |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check CSV import with a unit that is not marked as sell unit
    Given I proceed as the Buyer
    And I click "Quick Order Form"
    And I click "Get Directions"
    And I should see that "UiDialog Title" contains "Import Excel .CSV File"
    And I download "the CSV template"
    And I close ui dialog
    And I fill quick order template with data:
      | Item Number | Quantity | Unit |
      | PSKU1       | 1        | set  |
      | PSKU2       | 1        | item |
    When I import file for quick order
    Then "Quick Order Form" must contains values:
      | SKU1  | PSKU1 - Product1`"'&йёщ®&reg;> |
      | QTY1  | 1                              |
      | UNIT1 | --                             |
      | SKU2  | PSKU2 - Product2               |
      | QTY2  | 1                              |
      | UNIT2 | item                           |
    And I should see "Unit 'set' doesn't exist for product PSKU1."

  Scenario: Check product and unit names are localized
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
    Then "Quick Order Form" must contains values:
      | SKU1  | PSKU1 - Product1 (Localization1) |
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
    Then "Quick Order Form" must contains values:
      | SKU1  | PSKU2 - Product2 |
      | QTY1  | 1                |
      | UNIT1 | Element          |
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | PSKU2;3;Element |
    And I click "Verify Order"
    And I wait for products to load
    Then "Quick Order Form" must contains values:
      | SKU1  | PSKU2 - Product2 |
      | QTY1  | 4                |
      | UNIT1 | Element          |
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | PSKU2;2;element |
    And I click "Verify Order"
    And I wait for products to load
    Then "Quick Order Form" must contains values:
      | SKU1  | PSKU2 - Product2 |
      | QTY1  | 6                |
      | UNIT1 | Element          |

  Scenario: Quick order form empty row's validation
    Given I proceed as the Buyer
    And I click "Quick Order Form"
    When I click "Add to List 2"
    Then I should see that "Quick Add Form Validation" contains "Please add at least one item"

  Scenario: Quick order form 20+ rows duplicate submit buttons
    Given I click "Quick Order Form"
    And I click on "Add More Rows"
    And I scroll to bottom
    And I click on "Add More Rows"
    And I scroll to bottom
    And I click on "Add More Rows"
    And I scroll to bottom
    And I click on "Add More Rows"
    And I scroll to top
    When I click "Add to List 2"
    Then I should see that "Quick Add Form Validation" contains "Please add at least one item"
    And I fill "Quick Order Form" with:
      | SKU1 | PSKU2 |
    When I click "Add to List 2" in "After Validation Buttons Controls Group" element
    Then I should see "1 product was added (view shopping list)." flash message
