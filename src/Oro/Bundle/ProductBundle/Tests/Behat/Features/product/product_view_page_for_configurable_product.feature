@regression
@ticket-BB-13580
@ticket-BB-16438
@ticket-BB-17585
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroProductBundle:ProductConfigurableFixture.yml

Feature: Product view page for configurable product
  In order to use product view page for configurable products
  As an Admin
  I need to be able to add configurable products to shopping list from their view page

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Prepare first product attribute
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products/Product Attributes
    And I click "Create Attribute"
    When I fill form with:
      | Field Name | Color  |
      | Type       | Select |
    And I click "Continue"
    And I set Options with:
      | Label |
      | Black |
      | White |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Prepare second product attribute
    Given I go to Products/Product Attributes
    And I click "Create Attribute"
    When I fill form with:
      | Field Name | Refurbished |
      | Type       | Boolean     |
    And I click "Continue"
    And I save form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Schema update
    Given I go to Products/Product Attributes
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Update product family
    Given I go to Products/ Product Families
    And I click "Edit" on row "default_family" in grid
    When I fill "Product Family Form" with:
      | Attributes | [Color,Refurbished] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Prepare first simple product
    Given I go to Products/Products
    And I click Edit tpc_b_r in grid
    When I fill in product attribute "Color" with "Black"
    And I fill in product attribute "Refurbished" with "Yes"
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Prepare second simple product
    Given I go to Products/Products
    And I click Edit tpc_w in grid
    When I fill in product attribute "Color" with "White"
    And I fill in product attribute "Refurbished" with "No"
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Prepare configurable product
    Given I go to Products/Products
    And I filter SKU as is equal to "tpc"
    And I click Edit tpc in grid
    When I fill "ProductForm" with:
      | Configurable Attributes | [Color,Refurbished] |
      | Is Featured             | Yes                 |
      | URL Slug                | tablet_pc           |
    And I check tpc_b_r and tpc_w in grid
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Update system configuration
    Given I go to System/Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    When uncheck "Use default" for "Product Views" field
    And I fill in "Product Views" with "No Matrix Form"
    And uncheck "Use default" for "Product Listings" field
    And I fill in "Product Listings" with "No Matrix Form"
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Simple product variations made visible in search result
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I type "tpc" in "search"
    And I click "Search Button"
    And I click "View Details" for "tpc" product
    When I fill "Configurable Product Form" with:
      | Color       | White |
      | Refurbished | No    |
    When I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message and I close it
    And I should see "In shopping list"
    When I fill "Configurable Product Form" with:
      | Color       | Black |
      | Refurbished | Yes   |
    And I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message and I close it
    And I should see "In shopping list"

  Scenario: Check that product links from Shopping List navigates to product view page with preselected attributes
    Given I open page with shopping list "Shopping List"
    And I should see following grid:
      | SKU     | Qty Update All | Price     |
      | tpc_w   | 1 item        | $1,100.00  |
      | tpc_b_r | 1 item        | $800.00    |
    When I click on "tpc_b_r" configurable product in "Shopping List Line Items Table"
    Then "Configurable Product Form" must contains values:
      | Color       | Black |
      | Refurbished | Yes   |
    When I open page with shopping list "Shopping List"
    When I click on "tpc_w" configurable product in "Shopping List Line Items Table"
    Then "Configurable Product Form" must contains values:
      | Color       | White |
      | Refurbished | No    |

  Scenario: Create order with configurable product and check product view pages for product variants
    Given I open page with shopping list "Shopping List"
    When I click "Create Order"
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And "Order Review" checkout step "Order Summary Products Grid" contains products
      | Tablet PC | 1 | item | $1,100.00 |
      | Tablet PC | 1 | item | $800.00   |
    And I click on "tpc_b_r" configurable product in "Checkout Line Items Table" with the following attributes:
      | Color       | Black |
      | Refurbished | Yes   |
    Then "Configurable Product Form" must contains values:
      | Color       | Black |
      | Refurbished | Yes   |
    When I open page with shopping list "Shopping List"
    And I click "Create Order"
    And I click on "tpc_w" configurable product in "Checkout Line Items Table" with the following attributes:
      | Color       | White |
      | Refurbished | No    |
    Then "Configurable Product Form" must contains values:
      | Color       | White |
      | Refurbished | No    |
    When I open page with shopping list "Shopping List"
    And I click "Create Order"
    When I uncheck "Delete this shopping list after submitting order" on the "Order Review" checkout step and press Submit Order
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    When I click "click here to review"
    And I click on "tpc_w" configurable product in "Order Line Items Table" with the following attributes:
      | Color       | White |
      | Refurbished | No    |
    Then "Configurable Product Form" must contains values:
      | Color       | White |
      | Refurbished | No    |
    When I open Order History page on the store frontend
    And I click "view" on first row in "Past Orders Grid"
    And I click on "tpc_b_r" configurable product in "Order Line Items Table" with the following attributes:
      | Color       | Black |
      | Refurbished | Yes   |
    Then "Configurable Product Form" must contains values:
      | Color       | Black |
      | Refurbished | Yes   |

  Scenario: Configurable Product View Details link is accessible for featured products
    Given I am on homepage
    Then I should see "tpc" featured product
    When I click "View Details" for "tpc" product
    Then the url should match "/tablet_pc"

  Scenario: Restore system configuration default values
    Given I proceed as the Admin
    When check "Use default" for "Product Views" field
    And check "Use default" for "Product Listings" field
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Update configurable product variants when resuming product page from Shopping list
    Given I proceed as the Buyer
    When I open page with shopping list "Shopping List"
    And I click "Tablet PC"
    And I fill "Matrix Grid Form" with:
      |       | No | Yes |
      | Black | -  | 2   |
      | White | 1  | -   |
    And click "Update Shopping List"
    And I follow "Shopping List" link within flash message "Shopping list \"Shopping List\" was updated successfully"
    And I should see following grid:
      | SKU     | Item                                    | Qty Update All | Price     |
      | tpc_w   | Tablet PC Color: White Refurbished: No  | 1 item         | $1,100.00 |
      | tpc_b_r | Tablet PC Color: Black Refurbished: Yes | 2 item         | $800.00   |
