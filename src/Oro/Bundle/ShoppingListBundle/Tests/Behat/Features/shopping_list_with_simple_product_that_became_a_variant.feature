@regression
@ticket-BB-28081
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroShoppingListBundle:shopping_list_with_simple_product_that_became_a_variant.yml

Feature: Shopping list with simple product that became a variant
  In order to keep working with my shopping list
  As a Buyer
  I want the storefront to work when a simple product in my shopping list becomes a variant of a configurable product

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
      | Guest | system_session |

  Scenario: Enable guest shopping list, limit shopping lists to one and hide simple variations completely
    Given I proceed as the Admin
    And I login as administrator
    When I go to System/ Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And I fill "Shopping List Configuration Form" with:
      | Enable Guest Shopping List Default | false |
      | Enable Guest Shopping List         | true  |
      | Shopping List Limit Default        | false |
      | Shopping List Limit                | 1     |
    And I save form
    Then I should see "Configuration saved" flash message
    When I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And I fill "Display Simple Variations Form" with:
      | Display Simple Variations Default | false           |
      | Display Simple Variations         | Hide completely |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Guest adds the simple product to the guest shopping list
    Given I proceed as the Guest
    When I open product with sku "1GB81" on the store frontend
    And I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message and I close it

  Scenario: Prepare product attribute
    Given I proceed as the Admin
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
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Prepare product family
    When I go to Products/ Product Families
    And click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [Color] |
    And save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Make the simple product a variant of the configurable product
    When I go to Products/Products
    And click Edit 1GB81 in grid
    And fill in product attribute "Color" with "Black"
    And I save and close form
    Then I should see "Product has been saved" flash message
    When I go to Products/Products
    And click Edit 1GB83 in grid
    And fill "ProductForm" with:
      | Configurable Attributes | [Color] |
    And check records in grid:
      | 1GB81 |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Shopping list widget shows the variant name without a link
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I am on homepage
    And I open shopping list widget
    Then I should see "Black Slip-On Clog L" in the "Shopping List Widget" element
    And I should not see "Link" element with text "Black Slip-On Clog L" inside "Shopping List Widget" element
    And I close shopping list widget

  Scenario: Shopping list view page shows the variant name without a link
    When Buyer is on "Buyer Shopping List" shopping list
    Then I should see following grid containing rows:
      | SKU   | Product              |
      | 1GB81 | Black Slip-On Clog L |
    And I should not see an "Shopping List Line Item Product View Link" element

  Scenario: Shopping list edit page shows the variant name without a link
    When I click "Shopping List Actions"
    And click "Edit"
    Then I should see following grid containing rows:
      | SKU   | Product              |
      | 1GB81 | Black Slip-On Clog L |
    And I should not see an "Shopping List Line Item Product View Link" element

  Scenario: Checkout shows the variant name without a link
    When Buyer is on "Buyer Shopping List" shopping list
    And I click "Create Order"
    And I click "Order products"
    Then I should see "Black Slip-On Clog L"
    And I should not see an "Checkout Line Item Product Link" element

  Scenario: Guest shopping list shows the variant name without a link
    Given I proceed as the Guest
    When I am on homepage
    And I open shopping list widget
    Then I should see "Black Slip-On Clog L" in the "Shopping List Widget" element
    And I should not see "Link" element with text "Black Slip-On Clog L" inside "Shopping List Widget" element
    When I click "Open List"
    Then I should see following grid containing rows:
      | SKU   | Product              |
      | 1GB81 | Black Slip-On Clog L |
    And I should not see an "Shopping List Line Item Product View Link" element

  Scenario: Display simple variations everywhere
    Given I proceed as the Admin
    When I go to System/ Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And I fill "Display Simple Variations Form" with:
      | Display Simple Variations Default | false      |
      | Display Simple Variations         | everywhere |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Variant name is a link in the shopping list widget and on the shopping list page
    Given I proceed as the Buyer
    When I am on homepage
    And I open shopping list widget
    Then I should see "Link" element with text "Black Slip-On Clog L" inside "Shopping List Widget" element
    And I close shopping list widget
    When Buyer is on "Buyer Shopping List" shopping list
    Then I should see an "Shopping List Line Item Product View Link" element
    When I click "Shopping List Actions"
    And click "Edit"
    Then I should see an "Shopping List Line Item Product View Link" element

  Scenario: Variant name is a link in the guest shopping list
    Given I proceed as the Guest
    When I am on homepage
    And I open shopping list widget
    Then I should see "Link" element with text "Black Slip-On Clog L" inside "Shopping List Widget" element
    When I click "Open List"
    Then I should see an "Shopping List Line Item Product View Link" element
