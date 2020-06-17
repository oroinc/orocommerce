@regression
@ticket-BB-19141
@fixture-OroShoppingListBundle:MyShoppingListsFixture.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml

Feature: My Shopping List
  In order to allow customers to see products they want to purchase
  As a Buyer
  I need to be able to view a shopping list (without being able to modify it)

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I set configuration property "oro_shopping_list.my_shopping_lists_page_enabled" to "1"
    And I proceed as the Admin
    And I login as administrator
    And I go to System / Localization / Translations
    And I filter Key as equal to "oro.frontend.shoppinglist.lineitem.unit.label"
    And I edit "oro.frontend.shoppinglist.lineitem.unit.label" Translated Value as "Unit"
    And I click "Update Cache"
    And I should see "Translation Cache has been updated" flash message

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

  Scenario: Update schema
    When I go to Products/Product Attributes
    Then I confirm schema update

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
    And I save form
    Then I should see "Product has been saved" flash message
    Examples:
      | SKU  | Color | Size |
      | BB4  | Red   | M    |
      | BB5  | Green | L    |
      | BB6  | Blue  | S    |
      | BB7  | Red   | M    |
      | BB8  | Green | L    |
      | BB9  | Blue  | S    |
      | BB10 | Red   | M    |
      | BB11 | Green | L    |
      | BB12 | Blue  | S    |

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
    And I set Images with:
      | Main | Listing | Additional |
      | 1    | 1       | 1          |
    And I click on "Digital Asset Choose"
    And I fill "Digital Asset Dialog Form" with:
      | File  | <Image> |
      | Title | <Image> |
    And I click "Upload"
    And click on <Image> in grid
    And I save and close form
    Then I should see "Product has been saved" flash message
    Examples:
      | MainSKU | SKU1 | SKU2 | SKU3 | Image    |
      | AA1     | BB4  | BB5  | BB12 | cat1.jpg |
      | AA2     | BB6  | BB7  | BB11 | cat2.jpg |
      | AA3     | BB8  | BB9  | BB10 | cat3.jpg |

  Scenario: Check index page
    Given I operate as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I follow "Account"
    When I click "My Shopping Lists"
    Then Page title equals to "My Shopping Lists - My Account"
    And should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,790.00 | 29    |
      | Shopping List 1 | $1,581.00 | 3     |
    And I open shopping list widget
    And I should see "Shopping List 1" in the "Shopping List Widget" element
    And I should see "Shopping List 2" in the "Shopping List Widget" element
    And I should see "Shopping List 3" in the "Shopping List Widget" element
    And reload the page

  Scenario: Check Name filter
    Given I reset grid
    And records in grid should be 2
    When I filter Name as contains "List 3"
    Then I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,790.00 | 29    |
    And records in grid should be 1

  Scenario: Sort by Name
    Given I reset grid
    And I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,790.00 | 29    |
      | Shopping List 1 | $1,581.00 | 3     |
    When I sort grid by "Name"
    Then I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 1 | $1,581.00 | 3     |
      | Shopping List 3 | $8,790.00 | 29    |
    When I sort grid by "Name" again
    Then I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,790.00 | 29    |
      | Shopping List 1 | $1,581.00 | 3     |

  Scenario: Check Subtotal filter
    Given I reset grid
    And records in grid should be 2
    When I filter Subtotal as equals "8790"
    Then I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,790.00 | 29    |
    And records in grid should be 1

  Scenario: Sort by Subtotal
    Given I reset grid
    And I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,790.00 | 29    |
      | Shopping List 1 | $1,581.00 | 3     |
    When I sort grid by "Subtotal"
    Then I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 1 | $1,581.00 | 3     |
      | Shopping List 3 | $8,790.00 | 29    |
    When I sort grid by "Subtotal" again
    Then I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,790.00 | 29    |
      | Shopping List 1 | $1,581.00 | 3     |

  Scenario: Check Items filter
    Given I reset grid
    And records in grid should be 2
    When I filter Items as equals "29"
    Then I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,790.00 | 29    |
    And records in grid should be 1

  Scenario: Sort by Items
    Given I reset grid
    And I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,790.00 | 29    |
      | Shopping List 1 | $1,581.00 | 3     |
    When I sort grid by "Items"
    Then I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 1 | $1,581.00 | 3     |
      | Shopping List 3 | $8,790.00 | 29    |
    When I sort grid by "Items" again
    Then I should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,790.00 | 29    |
      | Shopping List 1 | $1,581.00 | 3     |

  Scenario: Check view page
    When I reset grid
    And I filter Name as contains "List 3"
    And I click View "Shopping List 3" in grid
    Then Page title equals to "Shopping List 3 - My Shopping Lists - My Account"
    And I should see "Shopping List 3"
    And I should see "Default"
    And I should see "Assigned To: Amanda Cole"
    And I should see "29 total records"
    And I should see following grid:
      | SKU  | Item                                   |              | Qty | Unit   | Price  | Subtotal |
      | AA1  | Configurable Product 1                 |              | 13  | items  |        | $227.00  |
      |      | BB4 Color: Red Size: M Note 4 text     | In Stock     | 3   | items  | $11.00 | $33.00   |
      |      | BB5 Color: Green Size: L Note 5 text   | Out of Stock | 3   | items  | $11.00 | $33.00   |
      |      | BB12 Color: Blue Size: S Note 12 text  | In Stock     | 7   | items  | $23.00 | $161.00  |
      | AA2  | Configurable Product 2                 |              | 3   | items  |        | $33.00   |
      |      | BB6 Color: Blue Size: S Note 6 text    | In Stock     | 3   | items  | $11.00 | $33.00   |
      | AA2  | Configurable Product 2                 |              | 5   | pieces |        | $85.00   |
      |      | BB7 Color: Red Size: M Note 7 text     | Out of Stock | 5   | pieces | $17.00 | $85.00   |
      | AA2  | Configurable Product 2                 |              | 7   | sets   |        | $133.00  |
      |      | BB11 Color: Green Size: L Note 11 text | Out of Stock | 7   | sets   | $19.00 | $133.00  |
      | AA3  | Configurable Product 3                 |              | 10  | pieces |        | $170.00  |
      |      | BB8 Color: Green Size: L Note 8 text   | In Stock     | 5   | pieces | $17.00 | $85.00   |
      |      | BB9 Color: Blue Size: S Note 9 text    | Out of Stock | 5   | pieces | $17.00 | $85.00   |
      | AA3  | Configurable Product 3                 |              | 7   | sets   |        | $133.00  |
      |      | BB10 Color: Red Size: M Note 10 text   | In Stock     | 7   | sets   | $19.00 | $133.00  |
      | BB13 | Product 13 Note 13 text                | Out of Stock | 9   | items  | $23.00 | $207.00  |
      | BB14 | Product 14 Note 14 text                | In Stock     | 9   | items  | $23.00 | $207.00  |
      | BB15 | Product 15 Note 15 text                | Out of Stock | 9   | items  | $23.00 | $207.00  |
      | BB16 | Product 16 Note 16 text                | In Stock     | 9   | items  | $23.00 | $207.00  |
      | BB17 | Product 17 Note 17 text                | Out of Stock | 9   | items  | $23.00 | $207.00  |
      | BB18 | Product 18 Note 18 text                | In Stock     | 11  | sets   | $29.00 | $319.00  |
      | BB19 | Product 19 Note 19 text                | Out of Stock | 11  | sets   | $29.00 | $319.00  |
      | BB20 | Product 20 Note 20 text                | In Stock     | 11  | sets   | $29.00 | $319.00  |
      | CC21 | Product 21 Note 21 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC22 | Product 22 Note 22 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC23 | Product 23 Note 23 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC24 | Product 24 Note 24 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC25 | Product 25 Note 25 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC26 | Product 26 Note 26 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC27 | Product 27 Note 27 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC28 | Product 28 Note 28 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC29 | Product 29 Note 29 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC30 | Product 30 Note 30 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC31 | Product 31 Note 31 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |

  Scenario: Check view second page
    When I click "Next"
    Then I should see following grid:
      | SKU  | Item                    |          | Qty | Unit   | Price  | Subtotal |
      | CC32 | Product 32 Note 32 text | In Stock | 13  | pieces | $31.00 | $403.00  |
      | CC33 | Product 33 Note 33 text | In Stock | 13  | pieces | $31.00 | $403.00  |
      | CC34 | Product 34 Note 34 text | In Stock | 13  | pieces | $31.00 | $403.00  |
      | CC35 | Product 35 Note 35 text | In Stock | 13  | pieces | $31.00 | $403.00  |

  Scenario: Check SKU filter
    Given I reset grid
    And records in grid should be 34
    When I filter SKU as contains "AA"
    Then I should see following grid:
      | SKU | Item                                   |              | Qty | Unit   | Price  | Subtotal |
      | AA1 | Configurable Product 1                 |              | 13  | items  |        | $227.00  |
      |     | BB4 Color: Red Size: M Note 4 text     | In Stock     | 3   | items  | $11.00 | $33.00   |
      |     | BB5 Color: Green Size: L Note 5 text   | Out of Stock | 3   | items  | $11.00 | $33.00   |
      |     | BB12 Color: Blue Size: S Note 12 text  | In Stock     | 7   | items  | $23.00 | $161.00  |
      | AA2 | Configurable Product 2                 |              | 3   | items  |        | $33.00   |
      |     | BB6 Color: Blue Size: S Note 6 text    | In Stock     | 3   | items  | $11.00 | $33.00   |
      | AA2 | Configurable Product 2                 |              | 5   | pieces |        | $85.00   |
      |     | BB7 Color: Red Size: M Note 7 text     | Out of Stock | 5   | pieces | $17.00 | $85.00   |
      | AA2 | Configurable Product 2                 |              | 7   | sets   |        | $133.00  |
      |     | BB11 Color: Green Size: L Note 11 text | Out of Stock | 7   | sets   | $19.00 | $133.00  |
      | AA3 | Configurable Product 3                 |              | 10  | pieces |        | $170.00  |
      |     | BB8 Color: Green Size: L Note 8 text   | In Stock     | 5   | pieces | $17.00 | $85.00   |
      |     | BB9 Color: Blue Size: S Note 9 text    | Out of Stock | 5   | pieces | $17.00 | $85.00   |
      | AA3 | Configurable Product 3                 |              | 7   | sets   |        | $133.00  |
      |     | BB10 Color: Red Size: M Note 10 text   | In Stock     | 7   | sets   | $19.00 | $133.00  |
    And records in grid should be 15

  Scenario: Sort by SKU
    Given I reset grid
    And I should see following grid:
      | SKU  | Item                                   |              | Qty | Unit   | Price  | Subtotal |
      | AA1  | Configurable Product 1                 |              | 13  | items  |        | $227.00  |
      |      | BB4 Color: Red Size: M Note 4 text     | In Stock     | 3   | items  | $11.00 | $33.00   |
      |      | BB5 Color: Green Size: L Note 5 text   | Out of Stock | 3   | items  | $11.00 | $33.00   |
      |      | BB12 Color: Blue Size: S Note 12 text  | In Stock     | 7   | items  | $23.00 | $161.00  |
      | AA2  | Configurable Product 2                 |              | 3   | items  |        | $33.00   |
      |      | BB6 Color: Blue Size: S Note 6 text    | In Stock     | 3   | items  | $11.00 | $33.00   |
      | AA2  | Configurable Product 2                 |              | 5   | pieces |        | $85.00   |
      |      | BB7 Color: Red Size: M Note 7 text     | Out of Stock | 5   | pieces | $17.00 | $85.00   |
      | AA2  | Configurable Product 2                 |              | 7   | sets   |        | $133.00  |
      |      | BB11 Color: Green Size: L Note 11 text | Out of Stock | 7   | sets   | $19.00 | $133.00  |
      | AA3  | Configurable Product 3                 |              | 10  | pieces |        | $170.00  |
      |      | BB8 Color: Green Size: L Note 8 text   | In Stock     | 5   | pieces | $17.00 | $85.00   |
      |      | BB9 Color: Blue Size: S Note 9 text    | Out of Stock | 5   | pieces | $17.00 | $85.00   |
      | AA3  | Configurable Product 3                 |              | 7   | sets   |        | $133.00  |
      |      | BB10 Color: Red Size: M Note 10 text   | In Stock     | 7   | sets   | $19.00 | $133.00  |
      | BB13 | Product 13 Note 13 text                | Out of Stock | 9   | items  | $23.00 | $207.00  |
      | BB14 | Product 14 Note 14 text                | In Stock     | 9   | items  | $23.00 | $207.00  |
      | BB15 | Product 15 Note 15 text                | Out of Stock | 9   | items  | $23.00 | $207.00  |
      | BB16 | Product 16 Note 16 text                | In Stock     | 9   | items  | $23.00 | $207.00  |
      | BB17 | Product 17 Note 17 text                | Out of Stock | 9   | items  | $23.00 | $207.00  |
      | BB18 | Product 18 Note 18 text                | In Stock     | 11  | sets   | $29.00 | $319.00  |
      | BB19 | Product 19 Note 19 text                | Out of Stock | 11  | sets   | $29.00 | $319.00  |
      | BB20 | Product 20 Note 20 text                | In Stock     | 11  | sets   | $29.00 | $319.00  |
      | CC21 | Product 21 Note 21 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC22 | Product 22 Note 22 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC23 | Product 23 Note 23 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC24 | Product 24 Note 24 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC25 | Product 25 Note 25 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC26 | Product 26 Note 26 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC27 | Product 27 Note 27 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC28 | Product 28 Note 28 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC29 | Product 29 Note 29 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC30 | Product 30 Note 30 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC31 | Product 31 Note 31 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
    When I sort grid by "SKU"
    Then I should see following grid:
      | SKU  | Item                                 |              | Qty | Unit   | Price  | Subtotal |
      | CC35 | Product 35 Note 35 text              | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC34 | Product 34 Note 34 text              | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC33 | Product 33 Note 33 text              | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC32 | Product 32 Note 32 text              | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC31 | Product 31 Note 31 text              | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC30 | Product 30 Note 30 text              | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC29 | Product 29 Note 29 text              | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC28 | Product 28 Note 28 text              | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC27 | Product 27 Note 27 text              | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC26 | Product 26 Note 26 text              | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC25 | Product 25 Note 25 text              | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC24 | Product 24 Note 24 text              | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC23 | Product 23 Note 23 text              | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC22 | Product 22 Note 22 text              | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC21 | Product 21 Note 21 text              | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | BB20 | Product 20 Note 20 text              | In Stock     | 11  | sets   | $29.00 | $319.00  |
      | BB19 | Product 19 Note 19 text              | Out of Stock | 11  | sets   | $29.00 | $319.00  |
      | BB18 | Product 18 Note 18 text              | In Stock     | 11  | sets   | $29.00 | $319.00  |
      | BB17 | Product 17 Note 17 text              | Out of Stock | 9   | items  | $23.00 | $207.00  |
      | BB16 | Product 16 Note 16 text              | In Stock     | 9   | items  | $23.00 | $207.00  |
      | BB15 | Product 15 Note 15 text              | Out of Stock | 9   | items  | $23.00 | $207.00  |
      | BB14 | Product 14 Note 14 text              | In Stock     | 9   | items  | $23.00 | $207.00  |
      | BB13 | Product 13 Note 13 text              | Out of Stock | 9   | items  | $23.00 | $207.00  |
      | AA3  | Configurable Product 3               |              | 10  | pieces |        | $170.00  |
      |      | BB8 Color: Green Size: L Note 8 text | In Stock     | 5   | pieces | $17.00 | $85.00   |
      |      | BB9 Color: Blue Size: S Note 9 text  | Out of Stock | 5   | pieces | $17.00 | $85.00   |
      | AA3  | Configurable Product 3               |              | 7   | sets   |        | $133.00  |
      |      | BB10 Color: Red Size: M Note 10 text | In Stock     | 7   | sets   | $19.00 | $133.00  |
    When I sort grid by "SKU" again
    Then I should see following grid:
      | SKU  | Item                                   |              | Qty | Unit   | Price  | Subtotal |
      | AA1  | Configurable Product 1                 |              | 13  | items  |        | $227.00  |
      |      | BB4 Color: Red Size: M Note 4 text     | In Stock     | 3   | items  | $11.00 | $33.00   |
      |      | BB5 Color: Green Size: L Note 5 text   | Out of Stock | 3   | items  | $11.00 | $33.00   |
      |      | BB12 Color: Blue Size: S Note 12 text  | In Stock     | 7   | items  | $23.00 | $161.00  |
      | AA2  | Configurable Product 2                 |              | 3   | items  |        | $33.00   |
      |      | BB6 Color: Blue Size: S Note 6 text    | In Stock     | 3   | items  | $11.00 | $33.00   |
      | AA2  | Configurable Product 2                 |              | 5   | pieces |        | $85.00   |
      |      | BB7 Color: Red Size: M Note 7 text     | Out of Stock | 5   | pieces | $17.00 | $85.00   |
      | AA2  | Configurable Product 2                 |              | 7   | sets   |        | $133.00  |
      |      | BB11 Color: Green Size: L Note 11 text | Out of Stock | 7   | sets   | $19.00 | $133.00  |
      | AA3  | Configurable Product 3                 |              | 10  | pieces |        | $170.00  |
      |      | BB8 Color: Green Size: L Note 8 text   | In Stock     | 5   | pieces | $17.00 | $85.00   |
      |      | BB9 Color: Blue Size: S Note 9 text    | Out of Stock | 5   | pieces | $17.00 | $85.00   |
      | AA3  | Configurable Product 3                 |              | 7   | sets   |        | $133.00  |
      |      | BB10 Color: Red Size: M Note 10 text   | In Stock     | 7   | sets   | $19.00 | $133.00  |
      | BB13 | Product 13 Note 13 text                | Out of Stock | 9   | items  | $23.00 | $207.00  |
      | BB14 | Product 14 Note 14 text                | In Stock     | 9   | items  | $23.00 | $207.00  |
      | BB15 | Product 15 Note 15 text                | Out of Stock | 9   | items  | $23.00 | $207.00  |
      | BB16 | Product 16 Note 16 text                | In Stock     | 9   | items  | $23.00 | $207.00  |
      | BB17 | Product 17 Note 17 text                | Out of Stock | 9   | items  | $23.00 | $207.00  |
      | BB18 | Product 18 Note 18 text                | In Stock     | 11  | sets   | $29.00 | $319.00  |
      | BB19 | Product 19 Note 19 text                | Out of Stock | 11  | sets   | $29.00 | $319.00  |
      | BB20 | Product 20 Note 20 text                | In Stock     | 11  | sets   | $29.00 | $319.00  |
      | CC21 | Product 21 Note 21 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC22 | Product 22 Note 22 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC23 | Product 23 Note 23 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC24 | Product 24 Note 24 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC25 | Product 25 Note 25 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC26 | Product 26 Note 26 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC27 | Product 27 Note 27 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC28 | Product 28 Note 28 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC29 | Product 29 Note 29 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC30 | Product 30 Note 30 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |
      | CC31 | Product 31 Note 31 text                | In Stock     | 13  | pieces | $31.00 | $403.00  |

  Scenario: Check Availability filter
    Given I reset grid
    And records in grid should be 34
    When I check "Out of Stock" in Availability filter
    Then I should see following grid:
      | SKU  | Item                                   |              | Qty | Unit   | Price  | Subtotal |
      | AA1  | Configurable Product 1                 |              | 13  | items  |        | $227.00  |
      |      | BB4 Color: Red Size: M Note 4 text     | In Stock     | 3   | items  | $11.00 | $33.00   |
      |      | BB5 Color: Green Size: L Note 5 text   | Out of Stock | 3   | items  | $11.00 | $33.00   |
      |      | BB12 Color: Blue Size: S Note 12 text  | In Stock     | 7   | items  | $23.00 | $161.00  |
      | AA2  | Configurable Product 2                 |              | 5   | pieces |        | $85.00   |
      |      | BB7 Color: Red Size: M Note 7 text     | Out of Stock | 5   | pieces | $17.00 | $85.00   |
      | AA2  | Configurable Product 2                 |              | 7   | sets   |        | $133.00  |
      |      | BB11 Color: Green Size: L Note 11 text | Out of Stock | 7   | sets   | $19.00 | $133.00  |
      | AA3  | Configurable Product 3                 |              | 10  | pieces |        | $170.00  |
      |      | BB8 Color: Green Size: L Note 8 text   | In Stock     | 5   | pieces | $17.00 | $85.00   |
      |      | BB9 Color: Blue Size: S Note 9 text    | Out of Stock | 5   | pieces | $17.00 | $85.00   |
      | BB13 | Product 13 Note 13 text                | Out of Stock | 9   | items  | $23.00 | $207.00  |
      | BB15 | Product 15 Note 15 text                | Out of Stock | 9   | items  | $23.00 | $207.00  |
      | BB17 | Product 17 Note 17 text                | Out of Stock | 9   | items  | $23.00 | $207.00  |
      | BB19 | Product 19 Note 19 text                | Out of Stock | 11  | sets   | $29.00 | $319.00  |
    And records in grid should be 15

  Scenario: Check Quantity filter
    Given I reset grid
    And records in grid should be 34
    When I filter Quantity as less than "10"
    Then I should see following grid:
      | SKU  | Item                                   |              | Qty | Unit   | Price  | Subtotal |
      | AA1  | Configurable Product 1                 |              | 13  | items  |        | $227.00  |
      |      | BB4 Color: Red Size: M Note 4 text     | In Stock     | 3   | items  | $11.00 | $33.00   |
      |      | BB5 Color: Green Size: L Note 5 text   | Out of Stock | 3   | items  | $11.00 | $33.00   |
      |      | BB12 Color: Blue Size: S Note 12 text  | In Stock     | 7   | items  | $23.00 | $161.00  |
      | AA2  | Configurable Product 2                 |              | 3   | items  |        | $33.00   |
      |      | BB6 Color: Blue Size: S Note 6 text    | In Stock     | 3   | items  | $11.00 | $33.00   |
      | AA2  | Configurable Product 2                 |              | 5   | pieces |        | $85.00   |
      |      | BB7 Color: Red Size: M Note 7 text     | Out of Stock | 5   | pieces | $17.00 | $85.00   |
      | AA2  | Configurable Product 2                 |              | 7   | sets   |        | $133.00  |
      |      | BB11 Color: Green Size: L Note 11 text | Out of Stock | 7   | sets   | $19.00 | $133.00  |
      | AA3  | Configurable Product 3                 |              | 10  | pieces |        | $170.00  |
      |      | BB8 Color: Green Size: L Note 8 text   | In Stock     | 5   | pieces | $17.00 | $85.00   |
      |      | BB9 Color: Blue Size: S Note 9 text    | Out of Stock | 5   | pieces | $17.00 | $85.00   |
      | AA3  | Configurable Product 3                 |              | 7   | sets   |        | $133.00  |
      |      | BB10 Color: Red Size: M Note 10 text   | In Stock     | 7   | sets   | $19.00 | $133.00  |
      | BB13 | Product 13 Note 13 text                | Out of Stock | 9   | items  | $23.00 | $207.00  |
      | BB14 | Product 14 Note 14 text                | In Stock     | 9   | items  | $23.00 | $207.00  |
      | BB15 | Product 15 Note 15 text                | Out of Stock | 9   | items  | $23.00 | $207.00  |
      | BB16 | Product 16 Note 16 text                | In Stock     | 9   | items  | $23.00 | $207.00  |
      | BB17 | Product 17 Note 17 text                | Out of Stock | 9   | items  | $23.00 | $207.00  |
    And records in grid should be 20

  Scenario: Check Unit filter
    Given I reset grid
    And records in grid should be 34
    When I check "set" in Unit filter
    Then I should see following grid:
      | SKU  | Item                                   |              | Qty | Unit | Price  | Subtotal |
      | AA2  | Configurable Product 2                 |              | 7   | sets |        | $133.00  |
      |      | BB11 Color: Green Size: L Note 11 text | Out of Stock | 7   | sets | $19.00 | $133.00  |
      | AA3  | Configurable Product 3                 |              | 7   | sets |        | $133.00  |
      |      | BB10 Color: Red Size: M Note 10 text   | In Stock     | 7   | sets | $19.00 | $133.00  |
      | BB18 | Product 18 Note 18 text                | In Stock     | 11  | sets | $29.00 | $319.00  |
      | BB19 | Product 19 Note 19 text                | Out of Stock | 11  | sets | $29.00 | $319.00  |
      | BB20 | Product 20 Note 20 text                | In Stock     | 11  | sets | $29.00 | $319.00  |
    And records in grid should be 7

  Scenario: Check Image preview
    Given I reset grid
    When I filter SKU as is equal "AA1"
    And I should not see an "Popup Gallery Widget" element
    And I click "Product Item Gallery Trigger"
    Then I should see an "Popup Gallery Widget" element
    And I should see gallery image with alt "Configurable Product 1"
    And I click "Popup Gallery Widget Close"
    And I should not see an "Popup Gallery Widget" element

  Scenario: Check when no image
    Given I reset grid
    When I filter SKU as is equal "BB14"
    And I should not see an "Popup Gallery Widget" element
    And I should see an "Empty Product Image" element
    Then I should not see an "Product Item Gallery Trigger" element

  Scenario: Check owner link
    When I click "Amanda Cole"
    Then Page title equals to "Amanda Cole - Users - My Account"
    And I should see "CUSTOMER USER - AMANDA COLE"

  Scenario: Check create order button
    When Buyer is on "Shopping List 3" shopping list
    Then I should see "Create Order"
    And I click "Create Order"
    And Page title equals to "Billing Information - Checkout"

  Scenario: Check request quote button
    When Buyer is on "Shopping List 3" shopping list
    And I click "More Actions"
    Then I should see "Request Quote"
    And I click "Request Quote"
    And Page title equals to "Request A Quote - Requests For Quote - My Account"

  Scenario: Change VIEW permission
    Given I follow "Account"
    And click "Users"
    And click "Roles"
    And click edit "Administrator" in grid
    And click "Shopping"
    When select following permissions:
      | Shopping List | View:None |
    And I scroll to top
    And click "Save"
    Then should see "Customer User Role has been saved" flash message
    And should see "View - None"
    And click "Sign Out"

  Scenario: Check resources
    Given I login as AmandaRCole@example.org buyer
    When I follow "Account"
    Then I should not see "My Shopping Lists"
