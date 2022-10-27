@regression
@ticket-BB-21105
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTerm30Integration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroCheckoutBundle:PaymentRuleForPaymentTerm30.yml
@fixture-OroShoppingListBundle:ConfigurableProductFixtures.yml
@fixture-OroShoppingListBundle:SimpleProductFixture.yml

Feature: Shopping list confirmation popup
  Сheck whether the confirmation popup is displayed only when an empty configurable product is added to shopping list.

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Guest | second_session |

  Scenario: Enable guest shopping list setting
    Given I proceed as the Admin
    And login as administrator
    And go to System/ Configuration
    And follow "Commerce/Sales/Shopping List" on configuration sidebar
    And fill "Shopping List Configuration Form" with:
      | Enable Guest Shopping List Default | false |
      | Enable Guest Shopping List         | true  |
    When I click "Save settings"
    Then the "Enable Guest Shopping List" checkbox should be checked

  Scenario: Enable guest checkout setting
    Given I follow "Commerce/Sales/Checkout" on configuration sidebar
    And fill "Checkout Configuration Form" with:
      | Enable Guest Checkout Default | false |
      | Enable Guest Checkout         | true  |
    When I click "Save settings"
    Then the "Enable Guest Checkout" checkbox should be checked

  Scenario: Prepare product attributes
    Given I go to Products/ Product Attributes
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
    When I save and close form
    And click update schema
    Then I should see Schema updated flash message

  Scenario: Prepare product family
    Given I go to Products/ Product Families
    And click "Edit" on row "default_family" in grid
    When I fill "Product Family Form" with:
      | Attributes | [Color, Size] |
    And save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Prepare first simple product
    Given I go to Products/Products
    And click Edit 1GB81 in grid
    And fill in product attribute "Color" with "Black"
    And fill in product attribute "Size" with "L"
    When I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Prepare second simple product
    Given I go to Products/Products
    And click Edit 1GB82 in grid
    And fill in product attribute "Color" with "White"
    And fill in product attribute "Size" with "L"
    When I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Prepare configurable product
    Given I go to Products/Products
    And click Edit 1GB83 in grid
    And fill "ProductForm" with:
      | Configurable Attributes | [Color, Size] |
    And check records in grid:
      | 1GB81 |
      | 1GB82 |
    When I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check create order with simple product
    Given I proceed as the Guest
    And I am on homepage
    And type "simple-product" in "search"
    And click "Search Button"
    When I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message and I close it
    When I open shopping list widget
    And click "Create Order"
    Then I should not see "This shopping list contains configurable products with no variations. Proceed to checkout without these products?"

  Scenario: Check create order with configurable product
    Given I type "1GB83" in "search"
    And click "Search Button"
    When I click "Add to Shopping List"
    Then should see 'Shopping list "Shopping List" was updated successfully' flash message
    When I open shopping list widget
    And click "Create Order"
    Then I should see "This shopping list contains configurable products with no variations. Proceed to checkout without these products?"
    And click "Cancel"
    When I fill "Matrix Grid Form" with:
      |       | L | M |
      | Black | 1 | - |
      | White | - | - |
    And click "Add to Shopping List"
    Then I should see 'Shopping list "Shopping list" was updated successfully' flash message
    When I open shopping list widget
    And click "Create Order"
    Then I should not see "This shopping list contains configurable products with no variations. Proceed to checkout without these products?"
