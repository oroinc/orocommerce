@ticket-BB-24728
@regression
@fixture-OroCustomerBundle:CustomerUserAddressFixture.yml
@fixture-OroCheckoutBundle:merging_shopping_list_and_checkout.yml

Feature: Merging shopping list and checkout
  As a guest I have a possibility to fill one shopping list and it should be added (or merged depending on limit)
  to customer user on login from checkout

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I enable configuration options:
      | oro_shopping_list.availability_for_guests              |
      | oro_checkout.guest_checkout                            |
      | oro_checkout.allow_checkout_without_email_confirmation |
    And I wait for a "search_reindex" job

  Scenario: Create payment and shipping integration
    Given I proceed as the Admin
    And login as administrator
    When I go to System/ Integrations/ Manage Integrations
    And click "Create Integration"
    And fill "Integration Form" with:
      | Type  | Flat Rate Shipping |
      | Name  | Flat Rate          |
      | Label | Flat_Rate          |
    And save and close form
    Then I should see "Integration saved" flash message
    When I go to System/ Integrations/ Manage Integrations
    And click "Create Integration"
    And fill "Integration Form" with:
      | Type        | Payment Terms |
      | Name        | Payment Terms |
      | Label       | Payment_Terms |
      | Short Label | Payment Terms |
    And save and close form
    Then I should see "Integration saved" flash message

  Scenario: Create payment and shipping rules
    Given I proceed as the Admin
    When I go to System/ Shipping Rules
    And click "Create Shipping Rule"
    And fill "Shipping Rule" with:
      | Enable     | true      |
      | Name       | Flat Rate |
      | Sort Order | 1         |
      | Currency   | $         |
      | Method     | Flat Rate |
    And fill form with:
      | Price | 5 |
    And save and close form
    Then should see "Shipping rule has been saved" flash message
    When I go to System/ Payment Rules
    And click "Create Payment Rule"
    And fill "Payment Rule Form" with:
      | Enable     | true            |
      | Name       | Payment Terms   |
      | Sort Order | 1               |
      | Currency   | $               |
      | Method     | [Payment Terms] |
    When save and close form
    Then should see "Payment rule has been saved" flash message

  Scenario: Create Payment Term
    Given I go to Sales/ Payment terms
    When I click "Create Payment Term"
    And type "Payment Term" in "Label"
    And save and close form
    Then I should see "Payment term has been saved" flash message

  Scenario: Set payment term for Customer
    Given I go to Customers/ Customer
    And click Edit first customer in grid
    And fill form with:
      | Payment Term | Payment Term |
    When I save form
    Then I should see "Customer has been saved" flash message

  Scenario: Guest authorization with a shopping list and checkout to an account without a shopping list and checkout and without restrictions on the number of shopping lists
    Given I proceed as the Buyer
    And I am on homepage
    And should see "No Shopping Lists"
    When I type "ORO_PRODUCT_1" in "search"
    And click "Search Button"
    Then I should see "ORO_PRODUCT_1"
    When I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message and I close it
    And should see "In shopping list"
    When I hover on "Shopping List Widget"
    And I should see "1 Item | $10.00" in the "Shopping List Widget" element
    And click "Create Order"
    And type "AmandaRCole@example.org" in "Email Address"
    And type "AmandaRCole@example.org" in "Password"
    And click "Sign In and Continue"
    Then I should see "Signed in as: Amanda Cole"
    And click "Ship to This Address"
    And click "Continue"
    And click "Continue"
    And should see following grid:
      | SKU    | Item          | Subtotal |
      | PSKU_1 | ORO_PRODUCT_1 | $10.00   |
    # Stop to check that the newly created checkout does not overwrite the existing one

  Scenario: Guest authorization with a shopping list and a started checkout to an account with a shopping list and checkout and without restrictions on the number of shopping lists
    Given I click "Sign Out"
    And I am on homepage
    And should see "No Shopping Lists"
    When I type "ORO_PRODUCT_2" in "search"
    And click "Search Button"
    Then I should see "ORO_PRODUCT_2"
    When I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message and I close it
    And should see "In shopping list"
    When I hover on "Shopping List Widget"
    And I should see "1 Item | $10.00" in the "Shopping List Widget" element
    And click "Create Order"
    And type "AmandaRCole@example.org" in "Email Address"
    And type "AmandaRCole@example.org" in "Password"
    And click "Sign In and Continue"
    Then I should see "Signed in as: Amanda Cole"
    And click "Ship to This Address"
    And click "Continue"
    And click "Continue"
    And should see following grid:
      | SKU    | Item          | Subtotal |
      | PSKU_2 | ORO_PRODUCT_2 | $10.00   |
    And click "Continue"
    And I check "Delete this shopping list after submitting order" on the "Order Review" checkout step and press Submit Order
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Set limit to One shopping list in configuration
    Given I proceed as the Admin
    When I go to System/Configuration
    And follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Shopping List Limit" field
    And fill in "Shopping List Limit" with "1"
    And uncheck "Use default" for "Enable Guest Shopping List" field
    And check "Enable Guest Shopping List"
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario: Guest authorization with a shopping list and a started checkout to an account with a shopping list and checkout and with restrictions on the number of shopping lists(1)
    Given I proceed as the Buyer
    And I click "Sign Out"
    And I am on homepage
    And should see "No Shopping Lists"
    When I type "ORO_PRODUCT_2" in "search"
    And click "Search Button"
    Then I should see "ORO_PRODUCT_2"
    When I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message and I close it
    And should see "In shopping list"
    When I hover on "Shopping List Widget"
    And I should see "1 Item | $10.00" in the "Shopping List Widget" element
    And click "Create Order"
    And type "AmandaRCole@example.org" in "Email Address"
    And type "AmandaRCole@example.org" in "Password"
    And click "Sign In and Continue"
    Then I should see "Signed in as: Amanda Cole"
    And should see "Upon signing in, any previously added items have been combined with the contents of your current shopping list." flash message
    And click "Ship to This Address"
    And click "Continue"
    And click "Continue"
    And should see following grid:
      | SKU    | Item          | Subtotal |
      | PSKU_1 | ORO_PRODUCT_1 | $10.00   |
      | PSKU_2 | ORO_PRODUCT_2 | $10.00   |
    And click "Continue"
    Then I should not see "Delete this shopping list after submitting order"
    And should see Checkout Totals with data:
      | Subtotal | $20.00 |
      | Shipping | $10.00 |
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    # We have combined 2 shopping lists as we have a limit on their number, so the result will be their absence
    # in the shopping list widget.
    When I hover on "Shopping List Widget"
    And I should see "Your Shopping List is empty" in the "Shopping List Widget" element
