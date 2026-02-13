@regression
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroShoppingListBundle:ConfigurableProductFixtures.yml
@fixture-OroShoppingListBundle:SimpleProductFixture.yml

Feature: Check invalid shopping list items for configurable products

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Prepare product attributes
    Given I proceed as the Admin
    And I login as administrator
    When I go to Products/ Product Attributes
    And click "Create Attribute"
    And fill form with:
      | Field Name | Color  |
      | Type       | Select |
    And click "Continue"
    And set Options with:
      | Label |
      | Black |
      | White |
    And save and close form
    And I click "Create Attribute"
    And fill form with:
      | Field Name | Size   |
      | Type       | Select |
    And click "Continue"
    And set Options with:
      | Label |
      | L     |
      | M     |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Prepare product family
    When I go to Products/ Product Families
    And click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [Color, Size] |
    And save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Prepare first simple product
    When I go to Products/Products
    And click Edit 1GB81 in grid
    And fill in product attribute "Color" with "Black"
    And fill in product attribute "Size" with "L"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Prepare second simple product
    When I go to Products/Products
    And click Edit 1GB82 in grid
    And fill in product attribute "Color" with "White"
    And fill in product attribute "Size" with "M"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Prepare configurable product
    When I go to Products/Products
    And click Edit 1GB83 in grid
    And fill "ProductForm" with:
      | Configurable Attributes | [Color, Size] |
    And check records in grid:
      | 1GB81 |
      | 1GB82 |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Add products to Shopping List
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When type "simple-product" in "search"
    And click "Search Button"
    And I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message and I close it

    When I type "1GB83" in "search"
    And I click "Search Button"
    And I click "Add to Shopping List"
    Then I should see 'Shopping list "Shopping List" was updated successfully' flash message and I close it

  Scenario: Check the invalid line items and ensure that the open dialogs are correctly named
    When I open page with shopping list Shopping List
    Then I should see "Some items in your shopping list need a quick review. Take a moment to update them before continuing."
    And I should see following "Frontend Shopping List Edit Grid" grid:
      | SKU                                                                           | Product        | Qty Update All  | Price  | Subtotal |
      | 1GB83                                                                         | Slip-On Clog   | Select Variants |        |          |
      | Please select product variants before placing an order or requesting a quote. |                |                 |        |          |
      | simple-product                                                                | Simple product | 1 item          | $10.00 | $10.00   |
    When I click "Request Quote"
    Then I should see "Invalid Items In The Request For Quote" in the "UiDialog Title" element
    And I click on "DialogClose"

    When I click "Create Order"
    Then I should see "Invalid Items In The Order" in the "UiDialog Title" element
    And I click on "DialogClose"

  Scenario: Buyer successfully proceeds to Checkout after fixing issues
    When I click "Checkout"
    Then I should see "Some items in your shopping list need a quick review. Take a moment to update them before continuing. All changes will be applied immediately." flash message
    And I should see next rows in "Frontend Customer User Shopping List Invalid Line Items Table" table without headers
      | Slip-On Clog 1GB83 Select Variants                                            |
      | Please select product variants before placing an order or requesting a quote. |

    When I click "Select Variants" in "Frontend Customer User Shopping List Invalid Line Items Grid" element
    And I fill "Matrix Grid Form" with:
      |       | L | M |
      | Black | 1 | - |
      | White | - | 1 |
    And I click "Save Changes"
    Then I should see "All issues have been fixed. You can now proceed to checkout." flash message

    When I click "Proceed"
    Then I should see "Checkout Continue" button enabled
    When I click "Order products"
    Then I should see following "Multi Shipping Checkout Line Items Grid" grid:
      | SKU            | Product              | Qty | Price  | Subtotal | Availability |
      | simple-product | Simple product       | 1   | $10.00 | $10.00   | In Stock     |
      | 1GB81          | Slip-On Clog Black L | 1   | $10.00 | $10.00   | In Stock     |
      | 1GB82          | Slip-On Clog White M | 1   | $7.00  | $7.00    | In Stock     |

  Scenario: Editing configurable product for Shopping List
    When I type "1GB83" in "search"
    And I click "Search Button"
    And I click "Clear All"
    And I click "Update Shopping List"
    Then I should see 'Shopping list "Shopping List" was updated successfully' flash message and I close it

  Scenario: Buyer successfully proceeds to RFQ after fixing issues
    When I open page with shopping list Shopping List
    And I click "Request Quote"
    Then I should see "Some items in your shopping list need a quick review. Take a moment to update them so you can continue to request a quote. All changes will be applied immediately" flash message
    And I should see next rows in "Frontend Customer User Shopping List Invalid Line Items Table" table without headers
      | Slip-On Clog 1GB83 Select Variants                                            |
      | Please select product variants before placing an order or requesting a quote. |

    When I click "Select Variants" in "Frontend Customer User Shopping List Invalid Line Items Grid" element
    And I fill "Matrix Grid Form" with:
      |       | L | M |
      | Black | 1 | - |
      | White | - | 1 |
    And I click "Save Changes"
    Then I should see "All issues have been fixed. You can now proceed to request a quote." flash message

    When I click "Proceed"
    Then Request a Quote contains products
      | simple-product - Simple product | 1 | item |
      | 1GB81 - Black Slip-On Clog L    | 1 | item |
      | 1GB82 - White Slip-On Clog M    | 1 | item |
