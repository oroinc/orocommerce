@regression
@feature-BB-21126
@fixture-OroShoppingListBundle:product_kits_validation_on_shopping_list_page.yml
@pricing-storage-combined
Feature: Product kits validation on shopping list page

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator
    And I go to System / Localization / Translations
    And I filter Key as equal to "oro.frontend.shoppinglist.lineitem.unit.label"
    And I edit "oro.frontend.shoppinglist.lineitem.unit.label" Translated Value as "Unit"

  Scenario: Change the product unit of the product kit item
    Given go to Products / Products
    And I filter SKU as is equal to "product-kit-03"
    And click "Edit" on row "product-kit-03" in grid
    And I click "Kit Items" in scrollspy
    And I fill "ProductKitForm" with:
      | Kit Item 1 Product Unit | each |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Change the product unit precision of the product
    Given go to Products / Products
    And I filter SKU as is equal to "simple-product-with-4-precision"
    And click "Edit" on row "simple-product-with-4-precision" in grid
    And I fill "ProductForm" with:
      | PrimaryPrecision | 0 |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Remove the invalid product from a product kit
    Given go to Products / Products
    And I filter SKU as is equal to "product-kit-03"
    And click "Edit" on row "product-kit-03" in grid
    And I click "Kit Items" in scrollspy
    And I fill "ProductKitForm" with:
      | Kit Item 1 Product Unit | each |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Remove the invalid product from a product kit
    Given go to Products / Products
    And I filter SKU as is equal to "product-kit-01"
    And click "Edit" on row "product-kit-01" in grid
    And I click "Kit Items" in scrollspy
    And I click "Remove" on row "invalid-product" in grid "Kit Item 1 Products Edit Grid"
    And I click "Yes, Delete"
    And I click "Kit Item 2 Toggler"
    And I click "Remove" on row "invalid-product" in grid "Kit Item 2 Products Edit Grid"
    And I click "Yes, Delete"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Change min and max allowed quantity for a product kit
    Given go to Products / Products
    And I filter SKU as is equal to "product-kit-01"
    And click "Edit" on row "product-kit-01" in grid
    And I fill "ProductKitForm" with:
      | Kit Item 1 Minimum Quantity | 2 |
      | Kit Item 1 Maximum Quantity | 3 |
    And I click "Kit Item 2 Toggler"
    And I fill "ProductKitForm" with:
      | Kit Item 2 Minimum Quantity | 1 |
      | Kit Item 2 Maximum Quantity | 2 |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Disable a product
    Given I go to Products/Products
    And I filter SKU as is equal to "disabled-product"
    When I click Edit disabled-product in grid
    And I fill "ProductForm" with:
      | Status | Disabled |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Hide a product
    Given go to Products / Products
    And I filter SKU as is equal to "invisible-product"
    And click "View" on row "invisible-product" in grid
    And click "More actions"
    When click "Manage Visibility"
    And I select "Hidden" from "Visibility to All"
    And I save and close form
    Then I should see "Product visibility has been saved" flash message

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
    When I go to Products/Product Families
    And I click Edit Default in grid
    And set Attribute Groups with:
      | Label           | Visible | Attributes    |
      | Attribute group | true    | [Color, Size] |
    And I save form
    Then I should see "Successfully updated" flash message

  Scenario Outline: Prepare simple products
    When I go to Products/Products
    And I filter SKU as is equal to "<SKU>"
    And I click Edit <SKU> in grid
    And I fill in product attribute "Color" with "<Color>"
    And I fill in product attribute "Size" with "<Size>"
    And I save and close form
    Then I should see "Product has been saved" flash message

    Examples:
      | SKU               | Color | Size |
      | simple-product-04 | Red   | M    |
      | simple-product-05 | Green | L    |
      | simple-product-06 | Blue  | S    |

  Scenario Outline: Prepare configurable products
    When I go to Products/Products
    And I filter SKU as is equal to "<MainSKU>"
    And I click Edit <MainSKU> in grid
    And I fill "ProductForm" with:
      | Configurable Attributes | [Color, Size] |
    And I check records in grid:
      | <SKU1> |
      | <SKU2> |
      | <SKU3> |
    And I save and close form
    Then I should see "Product has been saved" flash message

    Examples:
      | MainSKU                 | SKU1              | SKU2              | SKU3              |
      | configurable-product-01 | simple-product-04 | simple-product-05 | simple-product-06 |

  Scenario: Check shopping list view page
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When Buyer is on "Product Kit Shopping List" shopping list
    Then I should see following grid:
      | SKU                                                                                                                                                                                                      | Product                                                                                                        | Availability | Qty    | Unit   | Price  | Subtotal |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Quantity is greater than max quantity to order                                                   | In Stock     | 4      | pieces | $20.00 | $80.00   |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1      | piece  | $10.00 |          |
      | You cannot order more than 3 units                                                                                                                                                                       |                                                                                                                |              |        |        |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Quantity is less than min quantity to order                                                      | In Stock     | 1      | piece  | $20.00 | $20.00   |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1      | piece  | $10.00 |          |
      | You cannot order less than 2 units                                                                                                                                                                       |                                                                                                                |              |        |        |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Kit item quantity greater than max quantity                                                      | In Stock     | 2      | pieces | $60.00 | $120.00  |
      | simple-product-02                                                                                                                                                                                        | Barcode Scanner: Product 2                                                                                     |              | 4      | pieces | $10.00 |          |
      |                                                                                                                                                                                                          | The quantity should be between 2 and 3.                                                                        |              |        |        |        |          |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1      | piece  | $10.00 |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |        |        |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Kit item quantity is less than min quantity                                                      | In Stock     | 2      | pieces | $30.00 | $60.00   |
      | simple-product-02                                                                                                                                                                                        | Barcode Scanner: Product 2                                                                                     |              | 1      | piece  | $10.00 |          |
      |                                                                                                                                                                                                          | The quantity should be between 2 and 3.                                                                        |              |        |        |        |          |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1      | piece  | $10.00 |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |        |        |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Missing required kit item                                                                        | In Stock     | 2      | pieces | $10.00 | $20.00   |
      | Product kit "product-kit-01" is missing the required kit item "Base Unit"                                                                                                                                |                                                                                                                |              |        |        |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Invalid required kit item                                                                        | In Stock     | 2      | pieces |        |          |
      |                                                                                                                                                                                                          | Base Unit:                                                                                                     |              | 1      | piece  |        |          |
      |                                                                                                                                                                                                          | Selection required                                                                                             |              |        |        |        |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |        |        |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Invalid optional kit item                                                                        | In Stock     | 2      | pieces | $20.00 | $40.00   |
      |                                                                                                                                                                                                          | Barcode Scanner:                                                                                               |              | 2      | pieces |        |          |
      |                                                                                                                                                                                                          | Original selection no longer available                                                                         |              |        |        |        |          |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1      | piece  | $10.00 |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |        |        |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Disabled required kit item                                                                       | In Stock     | 2      | pieces | $20.00 | $40.00   |
      |                                                                                                                                                                                                          | Base Unit:                                                                                                     |              | 1      | piece  | $10.00 |          |
      |                                                                                                                                                                                                          | Selection required                                                                                             |              |        |        |        |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |        |        |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Disabled optional kit item                                                                       | In Stock     | 2      | pieces | $40.00 | $80.00   |
      |                                                                                                                                                                                                          | Barcode Scanner:                                                                                               |              | 2      | pieces | $10.00 |          |
      |                                                                                                                                                                                                          | Original selection no longer available                                                                         |              |        |        |        |          |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1      | piece  | $10.00 |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |        |        |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Priceless required kit item                                                                      | In Stock     | 2      | pieces |        |          |
      | priceless-product                                                                                                                                                                                        | Base Unit: Priceless product                                                                                   |              | 1      | piece  |        |          |
      |                                                                                                                                                                                                          | This item can't be added to checkout because the price is not available Selection required                     |              |        |        |        |          |
      | This item can't be added to checkout because the price is not available The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list. |                                                                                                                |              |        |        |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Priceless optional kit item                                                                      | In Stock     | 2      | pieces | $20.00 | $40.00   |
      | priceless-product                                                                                                                                                                                        | Barcode Scanner: Priceless product                                                                             |              | 2      | pieces |        |          |
      |                                                                                                                                                                                                          | This item can't be added to checkout because the price is not available Original selection no longer available |              |        |        |        |          |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1      | piece  | $10.00 |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |        |        |        |          |

      | product-kit-02                                                                                                                                                                                           | Product Kit 2 Invalid kit item precision                                                                       | In Stock     | 2      | pieces | $22.35 | $44.70   |
      | simple-product-with-4-precision                                                                                                                                                                          | Base Unit: Product with 4 precision                                                                            |              | 1.2345 | items  | $10.00 |          |
      |                                                                                                                                                                                                          | Only whole numbers are allowed for unit "item"                                                                 |              |        |        |        |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |        |        |        |          |

      | product-kit-03                                                                                                                                                                                           | Product Kit 3 Invalid kit item unit                                                                            | In Stock     | 2      | pieces | $20.00 | $40.00   |
      | simple-product-with-each-unit                                                                                                                                                                            | Base Unit: Product with each unit                                                                              |              | 1      | item   | $10.00 |          |
      |                                                                                                                                                                                                          | The selected product unit is not allowed                                                                       |              |        |        |        |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |        |        |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Valid                                                                                            | In Stock     | 2      | pieces | $40.00 | $80.00   |
      | simple-product-02                                                                                                                                                                                        | Barcode Scanner: Product 2                                                                                     |              | 2      | pieces | $10.00 |          |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1      | piece  | $10.00 |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Invisible required kit item                                                                      | In Stock     | 2      | pieces | $20.00 | $40.00   |
      | invisible-product                                                                                                                                                                                        | Base Unit: Invisible product                                                                                   |              | 1      | piece  | $10.00 |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Invisible optional kit item                                                                      | In Stock     | 2      | pieces | $40.00 | $80.00   |
      | invisible-product                                                                                                                                                                                        | Barcode Scanner: Invisible product                                                                             |              | 2      | pieces | $10.00 |          |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1      | piece  | $10.00 |          |

      | simple-product-02                                                                                                                                                                                        | Product 2                                                                                                      | In Stock     | 1      | piece  | $10.00 | $10.00   |

      | simple-product-04                                                                                                                                                                                        | Configurable Product 1 Red M                                                                                   | In Stock     | 1      | piece  | $10.00 | $10.00   |
      | simple-product-05                                                                                                                                                                                        | Configurable Product 1 Green L                                                                                 | Out of Stock | 2      | pieces | $10.00 | $20.00   |
      | simple-product-06                                                                                                                                                                                        | Configurable Product 1 Blue S                                                                                  | Out of Stock | 3      | pieces | $10.00 | $30.00   |

    And I should see "Summary 20 Items"
    And I should see "Subtotal $854.70"
    And I should see "Total $854.70"

  Scenario: Check shopping list edit page
    When I click "Shopping List Actions"
    And click "Edit"
    Then I should see following grid:
      | SKU                                                                                                                                                                                                      | Product                                                                                                        | Availability | Qty Update All | Price  | Subtotal |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Quantity is greater than max quantity to order                                                   | In Stock     | 4 piece        | $20.00 | $80.00   |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1 piece        | $10.00 |          |
      | You cannot order more than 3 units                                                                                                                                                                       |                                                                                                                |              |                |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Quantity is less than min quantity to order                                                      | In Stock     | 1 piece        | $20.00 | $20.00   |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1 piece        | $10.00 |          |
      | You cannot order less than 2 units                                                                                                                                                                       |                                                                                                                |              |                |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Kit item quantity greater than max quantity                                                      | In Stock     | 2 pieces       | $60.00 | $120.00  |
      | simple-product-02                                                                                                                                                                                        | Barcode Scanner: Product 2                                                                                     |              | 4 pieces       | $10.00 |          |
      |                                                                                                                                                                                                          | The quantity should be between 2 and 3.                                                                        |              |                |        |          |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1 piece        | $10.00 |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |                |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Kit item quantity is less than min quantity                                                      | In Stock     | 2 pieces       | $30.00 | $60.00   |
      | simple-product-02                                                                                                                                                                                        | Barcode Scanner: Product 2                                                                                     |              | 1 piece        | $10.00 |          |
      |                                                                                                                                                                                                          | The quantity should be between 2 and 3.                                                                        |              |                |        |          |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1 piece        | $10.00 |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |                |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Missing required kit item                                                                        | In Stock     | 2 piece        | $10.00 | $20.00   |
      | Product kit "product-kit-01" is missing the required kit item "Base Unit"                                                                                                                                |                                                                                                                |              |                |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Invalid required kit item                                                                        | In Stock     | 2 pieces       |        |          |
      |                                                                                                                                                                                                          | Base Unit:                                                                                                     |              | 1 piece        |        |          |
      |                                                                                                                                                                                                          | Selection required                                                                                             |              |                |        |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |                |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Invalid optional kit item                                                                        | In Stock     | 2 pieces       | $20.00 | $40.00   |
      |                                                                                                                                                                                                          | Barcode Scanner:                                                                                               |              | 2 pieces       |        |          |
      |                                                                                                                                                                                                          | Original selection no longer available                                                                         |              |                |        |          |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1 piece        | $10.00 |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |                |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Disabled required kit item                                                                       | In Stock     | 2 pieces       | $20.00 | $40.00   |
      |                                                                                                                                                                                                          | Base Unit:                                                                                                     |              | 1 piece        | $10.00 |          |
      |                                                                                                                                                                                                          | Selection required                                                                                             |              |                |        |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |                |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Disabled optional kit item                                                                       | In Stock     | 2 pieces       | $40.00 | $80.00   |
      |                                                                                                                                                                                                          | Barcode Scanner:                                                                                               |              | 2 pieces       | $10.00 |          |
      |                                                                                                                                                                                                          | Original selection no longer available                                                                         |              |                |        |          |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1 piece        | $10.00 |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |                |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Priceless required kit item                                                                      | In Stock     | 2 pieces       |        |          |
      | priceless-product                                                                                                                                                                                        | Base Unit: Priceless product                                                                                   |              | 1 piece        |        |          |
      |                                                                                                                                                                                                          | This item can't be added to checkout because the price is not available Selection required                     |              |                |        |          |
      | This item can't be added to checkout because the price is not available The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list. |                                                                                                                |              |                |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Priceless optional kit item                                                                      | In Stock     | 2 pieces       | $20.00 | $40.00   |
      | priceless-product                                                                                                                                                                                        | Barcode Scanner: Priceless product                                                                             |              | 2 pieces       |        |          |
      |                                                                                                                                                                                                          | This item can't be added to checkout because the price is not available Original selection no longer available |              |                |        |          |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1 piece        | $10.00 |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |                |        |          |

      | product-kit-02                                                                                                                                                                                           | Product Kit 2 Invalid kit item precision                                                                       | In Stock     | 2 pieces       | $22.35 | $44.70   |
      | simple-product-with-4-precision                                                                                                                                                                          | Base Unit: Product with 4 precision                                                                            |              | 1.2345 items   | $10.00 |          |
      |                                                                                                                                                                                                          | Only whole numbers are allowed for unit "item"                                                                 |              |                |        |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |                |        |          |

      | product-kit-03                                                                                                                                                                                           | Product Kit 3 Invalid kit item unit                                                                            | In Stock     | 2 pieces       | $20.00 | $40.00   |
      | simple-product-with-each-unit                                                                                                                                                                            | Base Unit: Product with each unit                                                                              |              | 1 item         | $10.00 |          |
      |                                                                                                                                                                                                          | The selected product unit is not allowed                                                                       |              |                |        |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |                |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Valid                                                                                            | In Stock     | 2 piece        | $40.00 | $80.00   |
      | simple-product-02                                                                                                                                                                                        | Barcode Scanner: Product 2                                                                                     |              | 2 pieces       | $10.00 |          |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1 piece        | $10.00 |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Invisible required kit item                                                                      | In Stock     | 2 piece        | $20.00 | $40.00   |
      | invisible-product                                                                                                                                                                                        | Base Unit: Invisible product                                                                                   |              | 1 piece        | $10.00 |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Invisible optional kit item                                                                      | In Stock     | 2 piece        | $40.00 | $80.00   |
      | invisible-product                                                                                                                                                                                        | Barcode Scanner: Invisible product                                                                             |              | 2 pieces       | $10.00 |          |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1 piece        | $10.00 |          |

      | simple-product-02                                                                                                                                                                                        | Product 2                                                                                                      | In Stock     | 1 piece        | $10.00 | $10.00   |

      | simple-product-04                                                                                                                                                                                        | Configurable Product 1 Red M                                                                                   | In Stock     | 1 piece        | $10.00 | $10.00   |
      | simple-product-05                                                                                                                                                                                        | Configurable Product 1 Green L                                                                                 | Out of Stock | 2 piece        | $10.00 | $20.00   |
      | simple-product-06                                                                                                                                                                                        | Configurable Product 1 Blue S                                                                                  | Out of Stock | 3 piece        | $10.00 | $30.00   |

    And I should see "Summary 20 Items"
    And I should see "Subtotal $854.70"
    And I should see "Total $854.70"

  Scenario: Check shopping list edit page with grouped product variants
    When I click "Group Product Variants"
    Then I should see following grid:
      | SKU                                                                                                                                                                                                      | Product                                                                                                        | Availability | Qty Update All | Price  | Subtotal |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Disabled required kit item                                                                       | In Stock     | 2 pieces       | $20.00 | $40.00   |
      |                                                                                                                                                                                                          | Base Unit:                                                                                                     |              | 1 piece        | $10.00 |          |
      |                                                                                                                                                                                                          | Selection required                                                                                             |              |                |        |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |                |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Priceless optional kit item                                                                      | In Stock     | 2 pieces       | $20.00 | $40.00   |
      | priceless-product                                                                                                                                                                                        | Barcode Scanner: Priceless product                                                                             |              | 2 pieces       |        |          |
      |                                                                                                                                                                                                          | This item can't be added to checkout because the price is not available Original selection no longer available |              |                |        |          |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1 piece        | $10.00 |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |                |        |          |

      | product-kit-02                                                                                                                                                                                           | Product Kit 2 Invalid kit item precision                                                                       | In Stock     | 2 pieces       | $22.35 | $44.70   |
      | simple-product-with-4-precision                                                                                                                                                                          | Base Unit: Product with 4 precision                                                                            |              | 1.2345 items   | $10.00 |          |
      |                                                                                                                                                                                                          | Only whole numbers are allowed for unit "item"                                                                 |              |                |        |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |                |        |          |

      | product-kit-03                                                                                                                                                                                           | Product Kit 3 Invalid kit item unit                                                                            | In Stock     | 2 pieces       | $20.00 | $40.00   |
      | simple-product-with-each-unit                                                                                                                                                                            | Base Unit: Product with each unit                                                                              |              | 1 item         | $10.00 |          |
      |                                                                                                                                                                                                          | The selected product unit is not allowed                                                                       |              |                |        |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |                |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Disabled optional kit item                                                                       | In Stock     | 2 pieces       | $40.00 | $80.00   |
      |                                                                                                                                                                                                          | Barcode Scanner:                                                                                               |              | 2 pieces       | $10.00 |          |
      |                                                                                                                                                                                                          | Original selection no longer available                                                                         |              |                |        |          |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1 piece        | $10.00 |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |                |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Priceless required kit item                                                                      | In Stock     | 2 pieces       |        |          |
      | priceless-product                                                                                                                                                                                        | Base Unit: Priceless product                                                                                   |              | 1 piece        |        |          |
      |                                                                                                                                                                                                          | This item can't be added to checkout because the price is not available Selection required                     |              |                |        |          |
      | This item can't be added to checkout because the price is not available The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list. |                                                                                                                |              |                |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Quantity is greater than max quantity to order                                                   | In Stock     | 4 piece        | $20.00 | $80.00   |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1 piece        | $10.00 |          |
      | You cannot order more than 3 units                                                                                                                                                                       |                                                                                                                |              |                |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Quantity is less than min quantity to order                                                      | In Stock     | 1 piece        | $20.00 | $20.00   |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1 piece        | $10.00 |          |
      | You cannot order less than 2 units                                                                                                                                                                       |                                                                                                                |              |                |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Kit item quantity greater than max quantity                                                      | In Stock     | 2 pieces       | $60.00 | $120.00  |
      | simple-product-02                                                                                                                                                                                        | Barcode Scanner: Product 2                                                                                     |              | 4 pieces       | $10.00 |          |
      |                                                                                                                                                                                                          | The quantity should be between 2 and 3.                                                                        |              |                |        |          |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1 piece        | $10.00 |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |                |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Kit item quantity is less than min quantity                                                      | In Stock     | 2 pieces       | $30.00 | $60.00   |
      | simple-product-02                                                                                                                                                                                        | Barcode Scanner: Product 2                                                                                     |              | 1 piece        | $10.00 |          |
      |                                                                                                                                                                                                          | The quantity should be between 2 and 3.                                                                        |              |                |        |          |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1 piece        | $10.00 |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |                |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Missing required kit item                                                                        | In Stock     | 2 piece        | $10.00 | $20.00   |
      | Product kit "product-kit-01" is missing the required kit item "Base Unit"                                                                                                                                |                                                                                                                |              |                |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Invalid required kit item                                                                        | In Stock     | 2 pieces       |        |          |
      |                                                                                                                                                                                                          | Base Unit:                                                                                                     |              | 1 piece        |        |          |
      |                                                                                                                                                                                                          | Selection required                                                                                             |              |                |        |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |                |        |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Invalid optional kit item                                                                        | In Stock     | 2 pieces       | $20.00 | $40.00   |
      |                                                                                                                                                                                                          | Barcode Scanner:                                                                                               |              | 2 pieces       |        |          |
      |                                                                                                                                                                                                          | Original selection no longer available                                                                         |              |                |        |          |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1 piece        | $10.00 |          |
      | The selected kit configuration is not available for purchase. Please modify or remove this configuration from the shopping list.                                                                         |                                                                                                                |              |                |        |          |

      |                                                                                                                                                                                                          | Configurable Product 1                                                                                         |              | 6 pieces       |        | $60.00   |
      | simple-product-04                                                                                                                                                                                        | Red M                                                                                                          | In Stock     | 1 piece        | $10.00 | $10.00   |
      | simple-product-05                                                                                                                                                                                        | Green L                                                                                                        | Out of Stock | 2 piece        | $10.00 | $20.00   |
      | simple-product-06                                                                                                                                                                                        | Blue S                                                                                                         | Out of Stock | 3 piece        | $10.00 | $30.00   |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Invisible required kit item                                                                      | In Stock     | 2 piece        | $20.00 | $40.00   |
      | invisible-product                                                                                                                                                                                        | Base Unit: Invisible product                                                                                   |              | 1 piece        | $10.00 |          |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Invisible optional kit item                                                                      | In Stock     | 2 piece        | $40.00 | $80.00   |
      | invisible-product                                                                                                                                                                                        | Barcode Scanner: Invisible product                                                                             |              | 2 pieces       | $10.00 |          |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1 piece        | $10.00 |          |

      | simple-product-02                                                                                                                                                                                        | Product 2                                                                                                      | In Stock     | 1 piece        | $10.00 | $10.00   |

      | product-kit-01                                                                                                                                                                                           | Product Kit 1 Valid                                                                                            | In Stock     | 2 piece        | $40.00 | $80.00   |
      | simple-product-02                                                                                                                                                                                        | Barcode Scanner: Product 2                                                                                     |              | 2 pieces       | $10.00 |          |
      | simple-product-01                                                                                                                                                                                        | Base Unit: Product 1                                                                                           |              | 1 piece        | $10.00 |          |

    And I should see "Summary 20 Items"
    And I should see "Subtotal $854.70"
    And I should see "Total $854.70"
