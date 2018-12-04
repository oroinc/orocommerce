@regression
@feature-BB-15511
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroOrderBundle:ConfigurableProductsShoppingList.yml

Feature: Previously purchased configurable products
  In order to quickly re-order goods I have bought recently
  As a customer
  I want to see a list of previously purchased configurable products

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Time restrictions option is present in the Management console and it is 90 days by default
    Given I operate as the Admin
    And I login as administrator
    When I go to System / Configuration
    And I follow "Commerce/Orders/Purchase History" on configuration sidebar
    And fill "Purchase History Settings Form" with:
      | Enable Purchase History Use Default | false |
      | Enable Purchase History             | true  |
    And I save setting
    Then the "Display products purchased within" field should contain "90"

  Scenario: Prepare product attribute
    Given I proceed as the Admin
    And I login as administrator
    When I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | BooleanAttribute |
      | Type       | Boolean          |
    And I click "Continue"
    And I fill form with:
      | Label | BooleanAttribute |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

    When I go to Products / Product Attributes
    And I click update schema
    Then I should see "Schema updated" flash message

    Then I go to Products / Product Families
    When I click Edit "Attribute Family" in grid
    And set Attribute Groups with:
      | Label           | Visible | Attributes                                                                                                                                                                            |
      | Attribute group | true    | [SKU, Name, Is Featured, New Arrival, Brand, Description, Short Description, Images, Inventory Status, Meta title, Meta description, Meta keywords, Product prices, BooleanAttribute] |
    And I save form
    Then I should see "Successfully updated" flash message

  Scenario: Prepare configurable product
    Given I go to Products / Products
    When filter SKU as is equal to "PROD_A_1"
    And I click Edit "PROD_A_1" in grid
    And I fill in product attribute "BooleanAttribute" with "Yes"
    And I save form
    Then I should see "Product has been saved" flash message
    When I go to Products / Products
    And filter SKU as is equal to "PROD_A_2"
    And I click Edit "PROD_A_2" in grid
    And I fill in product attribute "BooleanAttribute" with "No"
    And I save form
    Then I should see "Product has been saved" flash message
    When I go to Products / Products
    And filter SKU as is equal to "CNFA"
    And I click Edit "CNFA" in grid
    And I should see "There are no product variants"
    And I fill "ProductForm" with:
      | Configurable Attributes | [BooleanAttribute] |
    And I check PROD_A_1 and PROD_A_2 in grid
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Create order with configurable product
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And Buyer is on Configurable products list 1
    When I press "Create Order"
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I press "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Check configurable product is shown in Previously Purchased grid
    Given I proceed as the Buyer
    When I click "Account"
    And I click "Previously Purchased"
    Then I should see "ConfigurableProductA"
    And I should not see "Product A 1"
    And I should not see "Product A 2"

  Scenario: Change configuration to display simple variations everywhere
    Given I proceed as the Admin
    And go to System/ Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And I fill "Display Simple Variations Form" with:
      | Display Simple Variations Default | false      |
      | Display Simple Variations         | everywhere |
    When save form
    Then I should see "Configuration saved" flash message

  Scenario: Check no variations appear in Previously Purchased grid
    Given I proceed as the Buyer
    When I click "Account"
    And I click "Previously Purchased"
    Then I should not see "Product A 1"
    And I should not see "Product A 2"

  Scenario: Cancel order with configurable product
    Given I proceed as the Admin
    And I go to Sales/Orders
    When I click "View" on first row in grid
    Then I should see that order internal status is "Open"
    When I click on page action "Cancel"
    Then I should see "Order #1 has been cancelled." flash message

  Scenario: Check configurable product is not shown in Previously Purchased grid
    Given I proceed as the Buyer
    When I click "Account"
    And I click "Previously Purchased"
    Then I should not see "ConfigurableProductA"

  Scenario: Order simple variation product
    Given I proceed as the Buyer
    And I type "PROD_A_1" in "search"
    And I click "Search Button"
    And I filter SKU as is equal to "PROD_A_1"
    Then I should see "Product A 1"
    When I click "Add to Shopping List" for "PROD_A_1" product
    And I follow "Shopping List" link within flash message "Product has been added to \"Shopping list\""
    When I open page with shopping list "Shopping List"
    When I press "Create Order"
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I press "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Check that simple variation product is shown in Previously Purchased grid
    Given I proceed as the Buyer
    When I click "Account"
    And I click "Previously Purchased"
    Then I should see "Product A 1"
    And I should not see "ConfigurableProductA"
