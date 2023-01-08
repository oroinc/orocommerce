@regression
@ticket-BB-15979
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroOrderBundle:ConfigurableProductsShoppingList.yml

Feature: Configurable products in order history
  In order to use order history with configurable products
  As a customer
  I want to see purchased product variants for configurable products in order line items grid

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Prepare product attributes
    Given I proceed as the Admin
    And I login as administrator
    When I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Size   |
      | Type       | Select |
    And I click "Continue"
    And I set Options with:
      | Label |
      | S     |
      | M     |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Color  |
      | Type       | Select |
    And I click "Continue"
    And I set Options with:
      | Label |
      | Green |
      | Blue  |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I go to Products / Product Attributes
    And I click update schema
    Then I should see "Schema updated" flash message

    Then I go to Products / Product Families
    When I click Edit "Default" in grid
    And set Attribute Groups with:
      | Label           | Visible | Attributes |
      | Attribute group | true    | [Size, Color] |
    And I save form
    Then I should see "Successfully updated" flash message

  Scenario: Prepare configurable product
    Given I go to Products / Products
    When filter SKU as is equal to "PROD_A_1"
    And I click Edit "PROD_A_1" in grid
    And I fill in product attribute "Size" with "S"
    And I fill in product attribute "Color" with "Green"
    And I save form
    Then I should see "Product has been saved" flash message
    When I go to Products / Products
    And filter SKU as is equal to "PROD_A_2"
    And I click Edit "PROD_A_2" in grid
    And I fill in product attribute "Size" with "M"
    And I fill in product attribute "Color" with "Blue"
    And I save form
    Then I should see "Product has been saved" flash message
    When I go to Products / Products
    And filter SKU as is equal to "CNFA"
    And I click Edit "CNFA" in grid
    And I should see "There are no product variants"
    And I fill "ProductForm" with:
      | Configurable Attributes | [Size, Color] |
    And I check PROD_A_1 and PROD_A_2 in grid
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Create order with configurable product
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And Buyer is on "Configurable products list 1" shopping list
    When I press "Create Order"
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I press "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Check configurable is displayed with its variants in orders history grid
    Given I proceed as the Buyer
    When I follow "Account"
    And I click "Order History"
    And I click view "1" in "PastOrdersGrid"
    Then I should see following records in "OrderLineItemsGrid":
      | ConfigurableProductA Item #: PROD_A_1 Size: S Color: Green |
      | ConfigurableProductA Item #: PROD_A_2 Size: M Color: Blue  |

  Scenario: Check configurable is displayed with its variants after refreshing orders history grid
    Given I refresh "OrderLineItemsGrid" grid
    Then I should see following records in "OrderLineItemsGrid":
      | ConfigurableProductA Item #: PROD_A_1 Size: S Color: Green |
      | ConfigurableProductA Item #: PROD_A_2 Size: M Color: Blue  |
