@regression
@ticket-BB-19141
@feature-BAP-19790
@fixture-OroShoppingListBundle:MyShoppingListsFixture.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml

Feature: My Shopping List
  In order to allow customers to see products they want to purchase
  As a Buyer
  I need to be able to view a shopping list

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator
    And I go to System / Localization / Translations
    And I filter Key as equal to "oro.frontend.shoppinglist.lineitem.unit.label"
    And I edit "oro.frontend.shoppinglist.lineitem.unit.label" Translated Value as "Unit"

  Scenario: Create Color product attribute
    When I go to Products/Product Attributes
    And I click "Create Attribute"
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
    When I go to Products/Product Attributes
    And I click "Create Attribute"
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

  Scenario: Update product family
    Given I go to Products/Product Families
    When I click Edit Attribute Family in grid
    And set Attribute Groups with:
      | Label           | Visible | Attributes    |
      | Attribute group | true    | [Color, Size] |
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
      | BB04 | Red   | M    | cat1.jpg |
      | BB05 | Green | L    | cat2.jpg |
      | BB06 | Blue  | S    | cat3.jpg |
      | BB07 | Red   | M    | cat1.jpg |
      | BB08 | Green | L    | cat2.jpg |
      | BB09 | Blue  | S    | cat3.jpg |
      | BB10 | Red   | M    | cat1.jpg |
      | BB11 | Green | L    | cat2.jpg |
      | BB12 | Blue  | S    | cat3.jpg |
      | BB13 | Blue  | S    | cat1.jpg |

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
      | AA01    | BB04 | BB05 | BB12 |
      | AA02    | BB06 | BB07 | BB11 |
      | AA03    | BB08 | BB09 | BB10 |

  Scenario: Set additional units for product BB04
    When I go to Products/Products
    And I filter SKU as is equal to "BB04"
    And I click Edit BB04 in grid
    And set Additional Unit with:
      | Unit | Precision | Rate |
      | each | 1         | 2    |
    And I check "ProductAdditionalSellField" element
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check index page
    Given I operate as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I click "Account Dropdown"
    When I click on "Shopping Lists"
    Then Page title equals to "Shopping Lists - My Account"
    And should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,818.00 | 32    |
      | Shopping List 1 | $1,581.00 | 3     |
    And records in grid should be 2
    And I should see "My Shopping Lists"
    And I should not see an "Frontend Customer User Shopping Lists Grid Edited Label" element
    When I filter Name as contains "List 3"
    Then I should see an "Frontend Customer User Shopping Lists Grid Edited Label" element
    When I switch to "All Shopping Lists" grid view in "Frontend Customer User Shopping Lists Grid" frontend grid
    Then should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,818.00 | 32    |
      | Shopping List 2 | $1,178.00 | 2     |
      | Shopping List 1 | $1,581.00 | 3     |
    And I should see "All Shopping Lists"
    And I should not see an "Frontend Customer User Shopping Lists Grid Edited Label" element
    And I open shopping list widget
    And I should see "Shopping List 1" in the "Shopping List Widget" element
    And I should not see "Shopping List 2" in the "Shopping List Widget" element
    And I should see "Shopping List 3" in the "Shopping List Widget" element
    And reload the page

  Scenario: Change products status and inventory status
    Given I proceed as the Admin
    And I go to Products/Products
    And I filter SKU as is equal "CC27"
    When I edit "CC27" Inventory Status as "Discontinued"
    Then I should see "Record has been successfully updated" flash message
    And I filter SKU as is equal "CC28"
    When I edit "CC28" Status as "Disabled"
    Then I should see "Record has been successfully updated" flash message

  Scenario: Check subtotals are recalculated if product is disabled or has been discontinued
    Given I operate as the Buyer
    And I open page with shopping list "Shopping List 3"
    And I click "Account Dropdown"
    When I click on "Shopping Lists"
    Then Page title equals to "Shopping Lists - My Account"
    And should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,012.00 | 30    |
      | Shopping List 1 | $1,581.00 | 3     |

  Scenario: Check Name filter
    Given I operate as the Buyer
    When I reset grid
    Then records in grid should be 3
    And I should see "All Shopping Lists"
    And I should not see an "Frontend Customer User Shopping Lists Grid Edited Label" element
    When I filter Name as contains "List 3"
    Then I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,012.00 | 30    |
    And records in grid should be 1
    And I should see an "Frontend Customer User Shopping Lists Grid Edited Label" element

  Scenario: Sort by Name
    Given I reset grid
    And I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,012.00 | 30    |
      | Shopping List 2 | $1,178.00 | 2     |
      | Shopping List 1 | $1,581.00 | 3     |
    When I sort grid by "Name"
    Then I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 1 | $1,581.00 | 3     |
      | Shopping List 2 | $1,178.00 | 2     |
      | Shopping List 3 | $8,012.00 | 30    |
    When I sort grid by "Name" again
    Then I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,012.00 | 30    |
      | Shopping List 2 | $1,178.00 | 2     |
      | Shopping List 1 | $1,581.00 | 3     |

  Scenario: Check Subtotal filter
    Given I reset grid
    And records in grid should be 3
    When I filter Subtotal as equals "8012"
    Then I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,012.00 | 30    |
    And records in grid should be 1

  Scenario: Sort by Subtotal
    Given I reset grid
    And I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,012.00 | 30    |
      | Shopping List 2 | $1,178.00 | 2     |
      | Shopping List 1 | $1,581.00 | 3     |
    When I sort grid by "Subtotal"
    Then I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 2 | $1,178.00 | 2     |
      | Shopping List 1 | $1,581.00 | 3     |
      | Shopping List 3 | $8,012.00 | 30    |
    When I sort grid by "Subtotal" again
    Then I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,012.00 | 30    |
      | Shopping List 1 | $1,581.00 | 3     |
      | Shopping List 2 | $1,178.00 | 2     |

  Scenario: Check Items filter
    Given I reset grid
    And records in grid should be 3
    When I filter Items as equals "30"
    Then I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,012.00 | 30    |
    And records in grid should be 1

  Scenario: Sort by Items
    Given I reset grid
    And I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,012.00 | 30    |
      | Shopping List 2 | $1,178.00 | 2     |
      | Shopping List 1 | $1,581.00 | 3     |
    When I sort grid by "Items"
    Then I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 2 | $1,178.00 | 2     |
      | Shopping List 1 | $1,581.00 | 3     |
      | Shopping List 3 | $8,012.00 | 30    |
    When I sort grid by "Items" again
    Then I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,012.00 | 30    |
      | Shopping List 1 | $1,581.00 | 3     |
      | Shopping List 2 | $1,178.00 | 2     |

  Scenario: Check view page
    When I reset grid
    And I filter Name as contains "List 3"
    And I click View "Shopping List 3" in grid
    Then Page title equals to "Shopping List 3 - Shopping Lists - My Account"
    And I should see "Shopping List 3"
    And I should see "Default"
    And I should see "Assigned To: Amanda Cole"
    And I should see following grid:
      | SKU  | Product                                     | Availability | Qty | Unit   | Price  | Subtotal                 |
      | BB04 | Configurable Product 1 Red M Note 4 text    | IN STOCK     | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB05 | Configurable Product 1 Green L Note 5 text  | OUT OF STOCK | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB06 | Configurable Product 2 Blue S Note 6 text   | IN STOCK     | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB07 | Configurable Product 2 Red M Note 7 text    | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      | BB08 | Configurable Product 3 Green L Note 8 text  | IN STOCK     | 5   | pieces | $17.00 | $85.00                   |
      | BB09 | Configurable Product 3 Blue S Note 9 text   | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      | BB10 | Configurable Product 3 Red M Note 10 text   | IN STOCK     | 7   | sets   | $19.00 | $133.00                  |
      | BB11 | Configurable Product 2 Green L Note 11 text | OUT OF STOCK | 7   | sets   | $19.00 | $133.00                  |
      | BB12 | Configurable Product 1 Blue S Note 12 text  | IN STOCK     | 7   | items  | $23.00 | $161.00 -$80.50 $80.50   |
      | BB13 | Product 13 Note 13 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB14 | Product 14 Note 14 text                     | IN STOCK     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB15 | Product 15 Note 15 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB16 | Product 16 Note 16 text                     | IN STOCK     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB17 | Product 17 Note 17 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB18 | Product 18 Note 18 text                     | IN STOCK     | 11  | sets   | $29.00 | $319.00                  |
      | BB19 | Product 19 Note 19 text                     | OUT OF STOCK | 11  | sets   | $29.00 | $319.00                  |
      | BB20 | Product 20 Note 20 text                     | IN STOCK     | 11  | sets   | $29.00 | $319.00                  |
      | CC21 | Product 21 Note 21 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC22 | Product 22 Note 22 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC23 | Product 23 Note 23 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC24 | Product 24 Note 24 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC25 | Product 25 Note 25 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC26 | Product 26 Note 26 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
    And I should see "Summary 30 Items"
    And I should see "Subtotal $8,012.00"
    And I should see "Discount -$647.50"
    And I should see "Total: $7,364.50"

  Scenario: Check view second page
    When I click "Next"
    Then I should see following grid:
      | SKU  | Product                 | Availability | Qty | Unit   | Price  | Subtotal |
      | CC31 | Product 31 Note 31 text | IN STOCK     | 13  | pieces | $31.00 | $403.00  |
      | CC32 | Product 32 Note 32 text | IN STOCK     | 13  | pieces | $31.00 | $403.00  |
      | CC33 | Product 33 Note 33 text | IN STOCK     | 13  | pieces | $31.00 | $403.00  |
      | CC34 | Product 34 Note 34 text | IN STOCK     | 13  | pieces | $31.00 | $403.00  |
      | CC35 | Product 35 Note 35 text | IN STOCK     | 13  | pieces | $31.00 | $403.00  |
    And I scroll to top

  Scenario: Check SKU filter
    When I filter SKU as contains "CC3"
    Then I should see following grid:
      | SKU  | Product                 | Availability | Qty | Unit   | Price  | Subtotal |
      | CC30 | Product 30 Note 30 text | IN STOCK     | 13  | pieces | $31.00 | $403.00  |
      | CC31 | Product 31 Note 31 text | IN STOCK     | 13  | pieces | $31.00 | $403.00  |
      | CC32 | Product 32 Note 32 text | IN STOCK     | 13  | pieces | $31.00 | $403.00  |
      | CC33 | Product 33 Note 33 text | IN STOCK     | 13  | pieces | $31.00 | $403.00  |
      | CC34 | Product 34 Note 34 text | IN STOCK     | 13  | pieces | $31.00 | $403.00  |
      | CC35 | Product 35 Note 35 text | IN STOCK     | 13  | pieces | $31.00 | $403.00  |
    And I scroll to top
    When I reset grid
    And I filter SKU as contains "BB04"
    Then I should see following grid:
      | SKU  | Product                                  | Availability | Qty | Unit  | Price  | Subtotal              |
      | BB04 | Configurable Product 1 Red M Note 4 text | IN STOCK     | 3   | items | $11.00 | $33.00 -$16.50 $16.50 |

  Scenario: Sort by SKU
    Given I reset grid
    Then I should see following grid:
      | SKU  | Product                                     | Availability | Qty | Unit   | Price  | Subtotal                 |
      | BB04 | Configurable Product 1 Red M Note 4 text    | IN STOCK     | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB05 | Configurable Product 1 Green L Note 5 text  | OUT OF STOCK | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB06 | Configurable Product 2 Blue S Note 6 text   | IN STOCK     | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB07 | Configurable Product 2 Red M Note 7 text    | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      | BB08 | Configurable Product 3 Green L Note 8 text  | IN STOCK     | 5   | pieces | $17.00 | $85.00                   |
      | BB09 | Configurable Product 3 Blue S Note 9 text   | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      | BB10 | Configurable Product 3 Red M Note 10 text   | IN STOCK     | 7   | sets   | $19.00 | $133.00                  |
      | BB11 | Configurable Product 2 Green L Note 11 text | OUT OF STOCK | 7   | sets   | $19.00 | $133.00                  |
      | BB12 | Configurable Product 1 Blue S Note 12 text  | IN STOCK     | 7   | items  | $23.00 | $161.00 -$80.50 $80.50   |
      | BB13 | Product 13 Note 13 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB14 | Product 14 Note 14 text                     | IN STOCK     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB15 | Product 15 Note 15 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB16 | Product 16 Note 16 text                     | IN STOCK     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB17 | Product 17 Note 17 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB18 | Product 18 Note 18 text                     | IN STOCK     | 11  | sets   | $29.00 | $319.00                  |
      | BB19 | Product 19 Note 19 text                     | OUT OF STOCK | 11  | sets   | $29.00 | $319.00                  |
      | BB20 | Product 20 Note 20 text                     | IN STOCK     | 11  | sets   | $29.00 | $319.00                  |
      | CC21 | Product 21 Note 21 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC22 | Product 22 Note 22 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC23 | Product 23 Note 23 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC24 | Product 24 Note 24 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC25 | Product 25 Note 25 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC26 | Product 26 Note 26 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
    When I sort grid by "SKU"
    Then I should see following grid:
      | SKU  | Product                                     | Availability | Qty | Unit   | Price  | Subtotal                 |
      | BB04 | Configurable Product 1 Red M Note 4 text    | IN STOCK     | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB05 | Configurable Product 1 Green L Note 5 text  | OUT OF STOCK | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB06 | Configurable Product 2 Blue S Note 6 text   | IN STOCK     | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB07 | Configurable Product 2 Red M Note 7 text    | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      | BB08 | Configurable Product 3 Green L Note 8 text  | IN STOCK     | 5   | pieces | $17.00 | $85.00                   |
      | BB09 | Configurable Product 3 Blue S Note 9 text   | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      | BB10 | Configurable Product 3 Red M Note 10 text   | IN STOCK     | 7   | sets   | $19.00 | $133.00                  |
      | BB11 | Configurable Product 2 Green L Note 11 text | OUT OF STOCK | 7   | sets   | $19.00 | $133.00                  |
      | BB12 | Configurable Product 1 Blue S Note 12 text  | IN STOCK     | 7   | items  | $23.00 | $161.00 -$80.50 $80.50   |
      | BB13 | Product 13 Note 13 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB14 | Product 14 Note 14 text                     | IN STOCK     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB15 | Product 15 Note 15 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB16 | Product 16 Note 16 text                     | IN STOCK     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB17 | Product 17 Note 17 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB18 | Product 18 Note 18 text                     | IN STOCK     | 11  | sets   | $29.00 | $319.00                  |
      | BB19 | Product 19 Note 19 text                     | OUT OF STOCK | 11  | sets   | $29.00 | $319.00                  |
      | BB20 | Product 20 Note 20 text                     | IN STOCK     | 11  | sets   | $29.00 | $319.00                  |
      | CC21 | Product 21 Note 21 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC22 | Product 22 Note 22 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC23 | Product 23 Note 23 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC24 | Product 24 Note 24 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC25 | Product 25 Note 25 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC26 | Product 26 Note 26 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
    When I sort grid by "SKU" again
    Then I should see following grid:
      | SKU  | Product                                     | Availability | Qty | Unit   | Price  | Subtotal                 |
      | CC35 | Product 35 Note 35 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC34 | Product 34 Note 34 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC33 | Product 33 Note 33 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC32 | Product 32 Note 32 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC31 | Product 31 Note 31 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC30 | Product 30 Note 30 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC29 | Product 29 Note 29 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC26 | Product 26 Note 26 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC25 | Product 25 Note 25 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC24 | Product 24 Note 24 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC23 | Product 23 Note 23 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC22 | Product 22 Note 22 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC21 | Product 21 Note 21 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | BB20 | Product 20 Note 20 text                     | IN STOCK     | 11  | sets   | $29.00 | $319.00                  |
      | BB19 | Product 19 Note 19 text                     | OUT OF STOCK | 11  | sets   | $29.00 | $319.00                  |
      | BB18 | Product 18 Note 18 text                     | IN STOCK     | 11  | sets   | $29.00 | $319.00                  |
      | BB17 | Product 17 Note 17 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB16 | Product 16 Note 16 text                     | IN STOCK     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB15 | Product 15 Note 15 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB14 | Product 14 Note 14 text                     | IN STOCK     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB13 | Product 13 Note 13 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB12 | Configurable Product 1 Blue S Note 12 text  | IN STOCK     | 7   | items  | $23.00 | $161.00 -$80.50 $80.50   |
      | BB11 | Configurable Product 2 Green L Note 11 text | OUT OF STOCK | 7   | sets   | $19.00 | $133.00                  |

  Scenario: Check Show All and Group Product Variants
    When I reset grid
    And I click "Show All"
    And I click "Group Product Variants"
    Then I should see following grid:
      | SKU  | Product                                     | Availability | Qty | Unit   | Price  | Subtotal                 |
      | BB06 | Configurable Product 2 Blue S Note 6 text   | IN STOCK     | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB07 | Configurable Product 2 Red M Note 7 text    | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      |      | Configurable Product 3                      |              | 10  | pieces |        | $170.00                  |
      | BB08 | Green L Note 8 text                         | IN STOCK     | 5   | pieces | $17.00 | $85.00                   |
      | BB09 | Blue S Note 9 text                          | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      | BB10 | Configurable Product 3 Red M Note 10 text   | IN STOCK     | 7   | sets   | $19.00 | $133.00                  |
      | BB11 | Configurable Product 2 Green L Note 11 text | OUT OF STOCK | 7   | sets   | $19.00 | $133.00                  |
      |      | Configurable Product 1                      |              | 13  | items  |        | $227.00 -$113.50 $113.50 |
      | BB04 | Red M Note 4 text                           | IN STOCK     | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB05 | Green L Note 5 text                         | OUT OF STOCK | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB12 | Blue S Note 12 text                         | IN STOCK     | 7   | items  | $23.00 | $161.00 -$80.50 $80.50   |
      | BB13 | Product 13 Note 13 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB14 | Product 14 Note 14 text                     | IN STOCK     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB15 | Product 15 Note 15 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB16 | Product 16 Note 16 text                     | IN STOCK     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB17 | Product 17 Note 17 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB18 | Product 18 Note 18 text                     | IN STOCK     | 11  | sets   | $29.00 | $319.00                  |
      | BB19 | Product 19 Note 19 text                     | OUT OF STOCK | 11  | sets   | $29.00 | $319.00                  |
      | BB20 | Product 20 Note 20 text                     | IN STOCK     | 11  | sets   | $29.00 | $319.00                  |
      | CC21 | Product 21 Note 21 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC22 | Product 22 Note 22 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC23 | Product 23 Note 23 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC24 | Product 24 Note 24 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC25 | Product 25 Note 25 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC26 | Product 26 Note 26 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC29 | Product 29 Note 29 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC30 | Product 30 Note 30 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC31 | Product 31 Note 31 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC32 | Product 32 Note 32 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC33 | Product 33 Note 33 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC34 | Product 34 Note 34 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC35 | Product 35 Note 35 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
    When I reload the page
    Then I should see "Ungroup Product Variants"
    And I should see following grid:
      | SKU  | Product                                     | Availability | Qty | Unit   | Price  | Subtotal                 |
      | BB06 | Configurable Product 2 Blue S Note 6 text   | IN STOCK     | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB07 | Configurable Product 2 Red M Note 7 text    | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      |      | Configurable Product 3                      |              | 10  | pieces |        | $170.00                  |
      | BB08 | Green L Note 8 text                         | IN STOCK     | 5   | pieces | $17.00 | $85.00                   |
      | BB09 | Blue S Note 9 text                          | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      | BB10 | Configurable Product 3 Red M Note 10 text   | IN STOCK     | 7   | sets   | $19.00 | $133.00                  |
      | BB11 | Configurable Product 2 Green L Note 11 text | OUT OF STOCK | 7   | sets   | $19.00 | $133.00                  |
      |      | Configurable Product 1                      |              | 13  | items  |        | $227.00 -$113.50 $113.50 |
      | BB04 | Red M Note 4 text                           | IN STOCK     | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB05 | Green L Note 5 text                         | OUT OF STOCK | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB12 | Blue S Note 12 text                         | IN STOCK     | 7   | items  | $23.00 | $161.00 -$80.50 $80.50   |
      | BB13 | Product 13 Note 13 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB14 | Product 14 Note 14 text                     | IN STOCK     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB15 | Product 15 Note 15 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB16 | Product 16 Note 16 text                     | IN STOCK     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB17 | Product 17 Note 17 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB18 | Product 18 Note 18 text                     | IN STOCK     | 11  | sets   | $29.00 | $319.00                  |
      | BB19 | Product 19 Note 19 text                     | OUT OF STOCK | 11  | sets   | $29.00 | $319.00                  |
      | BB20 | Product 20 Note 20 text                     | IN STOCK     | 11  | sets   | $29.00 | $319.00                  |
      | CC21 | Product 21 Note 21 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC22 | Product 22 Note 22 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC23 | Product 23 Note 23 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC24 | Product 24 Note 24 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC25 | Product 25 Note 25 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC26 | Product 26 Note 26 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC29 | Product 29 Note 29 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC30 | Product 30 Note 30 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC31 | Product 31 Note 31 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC32 | Product 32 Note 32 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC33 | Product 33 Note 33 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC34 | Product 34 Note 34 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC35 | Product 35 Note 35 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |

  Scenario: Check Show Less
    When I click "Show Less"
    Then I should see following grid:
      | SKU  | Product                                     | Availability | Qty | Unit   | Price  | Subtotal                 |
      | BB06 | Configurable Product 2 Blue S Note 6 text   | IN STOCK     | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB07 | Configurable Product 2 Red M Note 7 text    | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      |      | Configurable Product 3                      |              | 10  | pieces |        | $170.00                  |
      | BB08 | Green L Note 8 text                         | IN STOCK     | 5   | pieces | $17.00 | $85.00                   |
      | BB09 | Blue S Note 9 text                          | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      | BB10 | Configurable Product 3 Red M Note 10 text   | IN STOCK     | 7   | sets   | $19.00 | $133.00                  |
      | BB11 | Configurable Product 2 Green L Note 11 text | OUT OF STOCK | 7   | sets   | $19.00 | $133.00                  |
      |      | Configurable Product 1                      |              | 13  | items  |        | $227.00 -$113.50 $113.50 |
      | BB04 | Red M Note 4 text                           | IN STOCK     | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB05 | Green L Note 5 text                         | OUT OF STOCK | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB12 | Blue S Note 12 text                         | IN STOCK     | 7   | items  | $23.00 | $161.00 -$80.50 $80.50   |
      | BB13 | Product 13 Note 13 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB14 | Product 14 Note 14 text                     | IN STOCK     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB15 | Product 15 Note 15 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB16 | Product 16 Note 16 text                     | IN STOCK     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB17 | Product 17 Note 17 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB18 | Product 18 Note 18 text                     | IN STOCK     | 11  | sets   | $29.00 | $319.00                  |
      | BB19 | Product 19 Note 19 text                     | OUT OF STOCK | 11  | sets   | $29.00 | $319.00                  |
      | BB20 | Product 20 Note 20 text                     | IN STOCK     | 11  | sets   | $29.00 | $319.00                  |
      | CC21 | Product 21 Note 21 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC22 | Product 22 Note 22 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC23 | Product 23 Note 23 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC24 | Product 24 Note 24 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC25 | Product 25 Note 25 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC26 | Product 26 Note 26 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC29 | Product 29 Note 29 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC30 | Product 30 Note 30 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC31 | Product 31 Note 31 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC32 | Product 32 Note 32 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC33 | Product 33 Note 33 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
    When I click "Next"
    Then I should see following grid:
      | SKU  | Product                 | Availability | Qty | Unit   | Price  | Subtotal |
      | CC34 | Product 34 Note 34 text | IN STOCK     | 13  | pieces | $31.00 | $403.00  |
      | CC35 | Product 35 Note 35 text | IN STOCK     | 13  | pieces | $31.00 | $403.00  |

  Scenario: Check Ungroup Product Variants
    When I click "Ungroup Product Variants"
    Then I should see following grid:
      | SKU  | Product                 | Availability | Qty | Unit   | Price  | Subtotal |
      | CC31 | Product 31 Note 31 text | IN STOCK     | 13  | pieces | $31.00 | $403.00  |
      | CC32 | Product 32 Note 32 text | IN STOCK     | 13  | pieces | $31.00 | $403.00  |
      | CC33 | Product 33 Note 33 text | IN STOCK     | 13  | pieces | $31.00 | $403.00  |
      | CC34 | Product 34 Note 34 text | IN STOCK     | 13  | pieces | $31.00 | $403.00  |
      | CC35 | Product 35 Note 35 text | IN STOCK     | 13  | pieces | $31.00 | $403.00  |
    When I click "Prev"
    Then I should see following grid:
      | SKU  | Product                                     | Availability | Qty | Unit   | Price  | Subtotal                 |
      | BB04 | Configurable Product 1 Red M Note 4 text    | IN STOCK     | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB05 | Configurable Product 1 Green L Note 5 text  | OUT OF STOCK | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB06 | Configurable Product 2 Blue S Note 6 text   | IN STOCK     | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB07 | Configurable Product 2 Red M Note 7 text    | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      | BB08 | Configurable Product 3 Green L Note 8 text  | IN STOCK     | 5   | pieces | $17.00 | $85.00                   |
      | BB09 | Configurable Product 3 Blue S Note 9 text   | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      | BB10 | Configurable Product 3 Red M Note 10 text   | IN STOCK     | 7   | sets   | $19.00 | $133.00                  |
      | BB11 | Configurable Product 2 Green L Note 11 text | OUT OF STOCK | 7   | sets   | $19.00 | $133.00                  |
      | BB12 | Configurable Product 1 Blue S Note 12 text  | IN STOCK     | 7   | items  | $23.00 | $161.00 -$80.50 $80.50   |
      | BB13 | Product 13 Note 13 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB14 | Product 14 Note 14 text                     | IN STOCK     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB15 | Product 15 Note 15 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB16 | Product 16 Note 16 text                     | IN STOCK     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB17 | Product 17 Note 17 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB18 | Product 18 Note 18 text                     | IN STOCK     | 11  | sets   | $29.00 | $319.00                  |
      | BB19 | Product 19 Note 19 text                     | OUT OF STOCK | 11  | sets   | $29.00 | $319.00                  |
      | BB20 | Product 20 Note 20 text                     | IN STOCK     | 11  | sets   | $29.00 | $319.00                  |
      | CC21 | Product 21 Note 21 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC22 | Product 22 Note 22 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC23 | Product 23 Note 23 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC24 | Product 24 Note 24 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC25 | Product 25 Note 25 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |
      | CC26 | Product 26 Note 26 text                     | IN STOCK     | 13  | pieces | $31.00 | $403.00                  |

  Scenario: Check Availability filter
    Given I scroll to top
    When I click "Group Product Variants"
    And I check "OUT OF STOCK" in Availability filter
    Then I should see following grid:
      | SKU  | Product                                     | Availability | Qty | Unit   | Price  | Subtotal                 |
      |      | Configurable Product 1                      |              | 13  | items  |        | $227.00 -$113.50 $113.50 |
      | BB05 | Green L Note 5 text And 2 more              | OUT OF STOCK | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB07 | Configurable Product 2 Red M Note 7 text    | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      |      | Configurable Product 3                      |              | 10  | pieces |        | $170.00                  |
      | BB09 | Blue S Note 9 text And 1 more               | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      | BB11 | Configurable Product 2 Green L Note 11 text | OUT OF STOCK | 7   | sets   | $19.00 | $133.00                  |
      | BB13 | Product 13 Note 13 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB15 | Product 15 Note 15 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB17 | Product 17 Note 17 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB19 | Product 19 Note 19 text                     | OUT OF STOCK | 11  | sets   | $29.00 | $319.00                  |
    When click on "Add 2 More Variants"
    Then I should see following grid:
      | SKU  | Product                                     | Availability | Qty | Unit   | Price  | Subtotal                 |
      |      | Configurable Product 1                      |              | 13  | items  |        | $227.00 -$113.50 $113.50 |
      | BB04 | Red M Note 4 text                           | IN STOCK     | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB05 | Green L Note 5 text                         | OUT OF STOCK | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB12 | Blue S Note 12 text                         | IN STOCK     | 7   | items  | $23.00 | $161.00 -$80.50 $80.50   |
      | BB07 | Configurable Product 2 Red M Note 7 text    | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      |      | Configurable Product 3                      |              | 10  | pieces |        | $170.00                  |
      | BB09 | Blue S Note 9 text And 1 more               | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      | BB11 | Configurable Product 2 Green L Note 11 text | OUT OF STOCK | 7   | sets   | $19.00 | $133.00                  |
      | BB13 | Product 13 Note 13 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB15 | Product 15 Note 15 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB17 | Product 17 Note 17 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB19 | Product 19 Note 19 text                     | OUT OF STOCK | 11  | sets   | $29.00 | $319.00                  |
    When click on "Add 1 More Variants"
    Then I should see following grid:
      | SKU  | Product                                     | Availability | Qty | Unit   | Price  | Subtotal                 |
      |      | Configurable Product 1                      |              | 13  | items  |        | $227.00 -$113.50 $113.50 |
      | BB04 | Red M Note 4 text                           | IN STOCK     | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB05 | Green L Note 5 text                         | OUT OF STOCK | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB12 | Blue S Note 12 text                         | IN STOCK     | 7   | items  | $23.00 | $161.00 -$80.50 $80.50   |
      | BB07 | Configurable Product 2 Red M Note 7 text    | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      |      | Configurable Product 3                      |              | 10  | pieces |        | $170.00                  |
      | BB08 | Green L Note 8 text                         | IN STOCK     | 5   | pieces | $17.00 | $85.00                   |
      | BB09 | Blue S Note 9 text                          | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      | BB11 | Configurable Product 2 Green L Note 11 text | OUT OF STOCK | 7   | sets   | $19.00 | $133.00                  |
      | BB13 | Product 13 Note 13 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB15 | Product 15 Note 15 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB17 | Product 17 Note 17 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB19 | Product 19 Note 19 text                     | OUT OF STOCK | 11  | sets   | $29.00 | $319.00                  |
    And I scroll to top

  Scenario: Check Quantity filter
    Given I reset grid
    When I filter Quantity as less than "10"
    Then I should see following grid:
      | SKU  | Product                                     | Availability | Qty | Unit   | Price  | Subtotal                 |
      | BB04 | Configurable Product 1 Red M Note 4 text    | IN STOCK     | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB05 | Configurable Product 1 Green L Note 5 text  | OUT OF STOCK | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB06 | Configurable Product 2 Blue S Note 6 text   | IN STOCK     | 3   | items  | $11.00 | $33.00 -$16.50 $16.50    |
      | BB07 | Configurable Product 2 Red M Note 7 text    | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      | BB08 | Configurable Product 3 Green L Note 8 text  | IN STOCK     | 5   | pieces | $17.00 | $85.00                   |
      | BB09 | Configurable Product 3 Blue S Note 9 text   | OUT OF STOCK | 5   | pieces | $17.00 | $85.00                   |
      | BB10 | Configurable Product 3 Red M Note 10 text   | IN STOCK     | 7   | sets   | $19.00 | $133.00                  |
      | BB11 | Configurable Product 2 Green L Note 11 text | OUT OF STOCK | 7   | sets   | $19.00 | $133.00                  |
      | BB12 | Configurable Product 1 Blue S Note 12 text  | IN STOCK     | 7   | items  | $23.00 | $161.00 -$80.50 $80.50   |
      | BB13 | Product 13 Note 13 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB14 | Product 14 Note 14 text                     | IN STOCK     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB15 | Product 15 Note 15 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB16 | Product 16 Note 16 text                     | IN STOCK     | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
      | BB17 | Product 17 Note 17 text                     | OUT OF STOCK | 9   | items  | $23.00 | $207.00 -$103.50 $103.50 |
    And I scroll to top

  Scenario: Check Unit filter
    Given I reset grid
    When I check "set" in Unit filter
    Then I should see following grid:
      | SKU  | Product                                     | Availability | Qty | Unit | Price  | Subtotal |
      | BB10 | Configurable Product 3 Red M Note 10 text   | IN STOCK     | 7   | sets | $19.00 | $133.00  |
      | BB11 | Configurable Product 2 Green L Note 11 text | OUT OF STOCK | 7   | sets | $19.00 | $133.00  |
      | BB18 | Product 18 Note 18 text                     | IN STOCK     | 11  | sets | $29.00 | $319.00  |
      | BB19 | Product 19 Note 19 text                     | OUT OF STOCK | 11  | sets | $29.00 | $319.00  |
      | BB20 | Product 20 Note 20 text                     | IN STOCK     | 11  | sets | $29.00 | $319.00  |
    And I scroll to top

  Scenario: Check Image preview for configurable row
    Given I reset grid
    When I filter SKU as is equal "BB04"
    And I should not see an "Popup Gallery Widget" element
    And I should see product picture in the "Product Item Preview"
    And I click "Product Item Gallery Trigger"
    Then I should see an "Popup Gallery Widget" element
    And I should see gallery image with alt "Configurable Product 1 product BB04"
    And I click "Popup Gallery Widget Close"
    And I should not see an "Popup Gallery Widget" element

  Scenario: Check Image preview for simple row
    Given I reset grid
    When I filter SKU as is equal "BB13"
    And I should not see an "Popup Gallery Widget" element
    And I should see product picture in the "Product Item Preview"
    And I click "Product Item Gallery Trigger"
    Then I should see an "Popup Gallery Widget" element
    And I should see gallery image with alt "Product 13"
    And I click "Popup Gallery Widget Close"
    And I should not see an "Popup Gallery Widget" element

  Scenario: Check when no image
    Given I reset grid
    When I filter SKU as is equal "BB14"
    And I should not see an "Popup Gallery Widget" element
    And I should see an "Empty Product Image" element
    Then I should not see an "Product Item Gallery Trigger" element

  Scenario: Check owner link
    And I click "ShoppingList Owner"
    Then Page title equals to "Amanda Cole - Users - My Account"
    And I should see "CUSTOMER USER - AMANDA COLE"

  Scenario: Check create order button
    When Buyer is on "Shopping List 3" shopping list
    Then I should see "Checkout"
    And I click "Create Order"
    And Page title equals to "Billing Information - Checkout"

  Scenario: Check request quote button
    When Buyer is on "Shopping List 3" shopping list
    Then I should see "Request Quote"
    And I click "Request Quote"
    And Page title equals to "Request A Quote - Requests For Quote - My Account"

  Scenario: Inline edit quantity and unit with Group Product Variants
    When I open page with shopping list Shopping List 3
    And I click "Group Product Variants"
    And I sort grid by "SKU"
    Then I should see following grid with exact columns order:
      | SKU  | Product                | Availability | Qty Update All  | Price  | Subtotal                 |
      |      | Configurable Product 1 |              | 13 items        |        | $227.00 -$113.50 $113.50 |
      | BB04 | Red M Note 4 text      | IN STOCK     | 3 ( item ) each | $11.00 | $33.00 -$16.50 $16.50    |
      | BB05 | Green L Note 5 text    | OUT OF STOCK | 3 item          | $11.00 | $33.00 -$16.50 $16.50    |
      | BB12 | Blue S Note 12 text    | IN STOCK     | 7 item          | $23.00 | $161.00 -$80.50 $80.50   |
    When I click on "Shopping List Line Item 2 Quantity"
    Then the "Shopping List Line Item 2 Quantity Input" field element should contain "3"
    When I fill "Shopping List Line Item Form" with:
      | Quantity | 10   |
      | Unit     | each |
    And I click on "Shopping List Line Item 2 Save Changes Button"
    Then I should see following grid containing rows:
      | SKU  | Product                                  | Availability | Qty Update All   | Price  | Subtotal               |
      | BB04 | Configurable Product 1 Red M Note 4 text | IN STOCK     | 10 item ( each ) |        |                        |
      |      | Configurable Product 1                   |              | 10 items         |        | $194.00 -$97.00 $97.00 |
      | BB05 | Green L Note 5 text                      | OUT OF STOCK | 3 item           | $11.00 | $33.00 -$16.50 $16.50  |
      | BB12 | Blue S Note 12 text                      | IN STOCK     | 7 item           | $23.00 | $161.00 -$80.50 $80.50 |

  Scenario: Update all with Group Product Variants
    When I set quantity for shopping list line item with sku "BB04" to "3"
    And I set unit for shopping list line item with sku "BB04" to "item"
    And I set quantity for shopping list line item with sku "BB05" to "5"
    And I click "Update All"
    Then I should see following grid containing rows:
      | SKU  | Product                | Availability | Qty Update All  | Price  | Subtotal                 |
      |      | Configurable Product 1 |              | 15 items        |        | $249.00 -$124.50 $124.50 |
      | BB04 | Red M Note 4 text      | IN STOCK     | 3 ( item ) each | $11.00 | $33.00 -$16.50 $16.50    |
      | BB05 | Green L Note 5 text    | OUT OF STOCK | 5 item          | $11.00 | $55.00 -$27.50 $27.50    |
      | BB12 | Blue S Note 12 text    | IN STOCK     | 7 item          | $23.00 | $161.00 -$80.50 $80.50   |

  Scenario: Change VIEW permission
    And I click "Account Dropdown"
    And click "Roles"
    And click edit "Administrator" in grid
    And I should see 'Predefined roles cannot be edited directly. We copied all the original data so that you can save it as a new user role for your organization. All users will be moved from the original role to this new role after you click "Save".' flash message and I close it
    And click "Shopping"
    When select following permissions:
      | Shopping List | View:None |
    And I scroll to top
    And click "Save"
    Then should see "Customer User Role has been saved" flash message
    And should see "View - None"
    And I click "Account Dropdown"
    And click "Sign Out"

  Scenario: Check resources
    Given I login as AmandaRCole@example.org buyer
    Then I should not see a "Shopping Lists" element
