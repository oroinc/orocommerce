@regression
@ticket-BB-24733
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml

Feature: Pricing for guest in shopping list and checkout pages

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create price list
    Given I proceed as the Admin
    And I login as administrator
    When I go to Sales/ Price Lists
    And I click "Create Price List"
    And I fill form with:
      | Name       | Guest Price List          |
      | Currencies | [US Dollar ($), Euro (€)] |
      | Active     | true                      |
    And I save and close form
    Then should see "Price List has been saved" flash message

  Scenario: Set price list for guest
    When go to Customers/ Customer Groups
    And I click Edit Non-Authenticated Visitors in grid
    And fill "Customer Group Form" with:
      | Price List | Guest Price List |
    And I save and close form
    Then I should see "Customer group has been saved" flash message

  Scenario: Enable guest shopping list setting
    When I go to System/ Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Enable Guest Shopping List" field
    And I check "Enable Guest Shopping List"
    And I save form
    Then I should see "Configuration saved" flash message
    And the "Enable Guest Shopping List" checkbox should be checked

  Scenario: Enable guest checkout setting
    When I follow "Commerce/Sales/Checkout" on configuration sidebar
    And uncheck "Use default" for "Enable Guest Checkout" field
    And I check "Enable Guest Checkout"
    And I save form
    Then I should see "Configuration saved" flash message
    And the "Enable Guest Checkout" checkbox should be checked

  Scenario: Create product
    When go to Products/ Products
    And click "Create Product"
    And fill form with:
      | Type | Simple |
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU              | testProduct |
      | Name             | testProduct |
      | Status           | Enable      |
      | Unit Of Quantity | each        |
    And click "AddPrice"
    And fill "Product Price Form" with:
      | Price List | Default Price List |
      | Quantity   | 1                  |
      | Value      | 10                 |
      | Currency   | $                  |
    And click "AddPrice"
    And fill "Product Price Form" with:
      | Price List 2 | Guest Price List |
      | Quantity 2   | 1                |
      | Value 2      | 2                |
      | Currency 2   | $                |
    And save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check shopping list and checkout total for guest
    Given I proceed as the Buyer
    And I am on homepage
    When type "testProduct" in "search"
    And I click "Search Button"
    Then I should see "testProduct"
    And I should see "$2.00" in the "Product Price Your" element
    When I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message and I close it
    When I open shopping list widget
    And I click "Open List"
    Then I should see following grid:
      | SKU         | Product     | Qty Update All | Price | Subtotal |
      | testProduct | testProduct | 1 each         | $2.00 | $2.00    |
    And I should see Checkout Totals with data:
      | Subtotal | $2.00 |
    And I should see "Total: $2.00"
    When click "Create Order"
    And I click "Proceed to Guest Checkout?"
    Then should see "Total: $2.00"
    When I click "Order products"
    Then I should see following grid:
      | SKU         | Product     | Qty | Price | Subtotal |
      | testProduct | testProduct | 1   | $2.00 | $2.00    |

  Scenario: Check shopping list total from backoffice
    Given I proceed as the Admin
    When go to Sales/ Shopping Lists
    And click on Shopping List in grid
    And should see "Subtotal $2.00"
    And should see "Total $2.00"
