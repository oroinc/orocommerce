@ticket-BB-15015
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRate2Integration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:ShippingRuleForFlatRate2.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml
@regression

Feature: Single Page Checkout With Popup for Guest
  In order to complete the checkout process
  As a Guest
  I want to fill billing address and shipping address in dialog window and complete checkout

  Scenario: Create different window session
    Given sessions active:
      | Admin     |first_session |
      | Guest     |second_session|

  Scenario: Enable Single Page Checkout Workflow
    Given I proceed as the Admin
    And There is USD currency in the system configuration
    And I login as administrator
    And I go to System/Workflows
    When I click "Activate" on row "Single Page Checkout" in grid
    And I click "Activate"
    Then I should see "Workflow activated" flash message

  Scenario: Enable Guest Checkout and Guest Shopping List in system configuration
    Given I go to System/Configuration
    And I follow "Commerce/Sales/Checkout" on configuration sidebar
    And uncheck "Use default" for "Guest Checkout" field
    And I check "Guest Checkout"
    When I click "Save settings"
    Then I should see "Configuration saved" flash message
    When I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Enable guest shopping list" field
    And I check "Enable guest shopping list"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Set payment term for Non-Authenticated Visitors group
    Given go to Customers/ Customer Groups
    And I click Edit Non-Authenticated Visitors in grid
    And I fill form with:
      | Payment Term | net 10 |
    When I save form
    Then I should see "Customer group has been saved" flash message

  Scenario: Validate new billing address form and empty billing address notification
    Given I proceed as the Guest
    And I am on homepage
    And type "SKU123" in "search"
    And I click "Search Button"
    And I click "View Details" for "SKU123" product
    And I click "Add to Shopping List"
    And I open page with shopping list Shopping List
    And I scroll to top
    And I wait line items are initialized
    And I click "Create Order"
    And I should not see flash messages
    And I uncheck "Save my data and create an account" on the checkout page
    And I click "Submit Order"
    And I should see "Please enter correct billing address."
    When I click on "Billing Address Select"
    And I click on "New Address Option"
    Then I should see "UiDialog" with elements:
      | Title        | Billing Address |
      | okButton     | Continue        |
    And "New Address Popup Form" must contains values:
      | Email        |  |
      | Label        |  |
      | Organization |  |
      | Street       |  |
      | City         |  |
      | Postal Code  |  |
    And I click "Continue"
    Then I should see "New Address Popup Form" validation errors:
      | Email        | This value should not be blank.                               |
      | First Name   | First Name and Last Name or Organization should not be blank. |
      | Last Name    | Last Name and First Name or Organization should not be blank. |
      | Organization | Organization or First Name and Last Name should not be blank. |
      | Street       | This value should not be blank.                               |
      | City         | This value should not be blank.                               |
      | Postal Code  | This value should not be blank.                               |
    When I close ui dialog
    Then I should see "New address" for "Select Single Page Checkout Billing Address" select

    Scenario: Check empty shipping address notification
    And I click on "Billing Address Select"
    And I click on "New Address Option"
    And I fill "New Address Popup Form" with:
      | Email        | test@example.com |
      | Label        | B Address        |
      | Name Prefix  | B Prefix         |
      | First Name   | B Fname          |
      | Middle Name  | B Mname          |
      | Last Name    | B Lname          |
      | Name Suffix  | B Suffix         |
      | Organization | B Organization   |
      | Phone        | 12345            |
      | Street       | B Street         |
      | Street 2     | B Street 2       |
      | City         | B City           |
      | Country      | Albania          |
      | State        | Has              |
      | Postal Code  | 12345            |
    And I click "Continue"
    Then I should see "New address (B Prefix B Fname B Mname B Lname B Suffix, B Organization, B Street B Street 2, B CITY HA AL 12345, 12345)" for "Select Single Page Checkout Billing Address" select
    And I click "Submit Order"
    And I should see "Please enter correct shipping address."

  Scenario: Configure shipping rules
    Given I proceed as the Admin
    Given I go to System/Shipping Rules
    And I click Edit "Default" in grid
    And I click "Add"
    And fill "Shipping Rule" with:
      | Country1 | Germany |
    When I save and close form
    Then I should see "Shipping rule has been saved" flash message
    When I go to System/Shipping Rules
    And I click Edit "Flat Rate 2$" in grid
    And I click "Add"
    And fill "Shipping Rule" with:
      | Country1 | Albania |
    And I click "Add"
    And fill "Shipping Rule" with:
      | Country2 | Georgia |
    And I save and close form
    Then I should see "Shipping rule has been saved" flash message

  Scenario: Fill and save billing address via popup
    Given I proceed as the Guest
    Given I scroll to top
    And I click on "Billing Address Select"
    And I click on "New Address Option"
    And I fill "New Address Popup Form" with:
      | Email        | test@example.com |
      | Label        | B Address        |
      | Name Prefix  | B Prefix         |
      | First Name   | B Fname          |
      | Middle Name  | B Mname          |
      | Last Name    | B Lname          |
      | Name Suffix  | B Suffix         |
      | Organization | B Organization   |
      | Phone        | 12345            |
      | Street       | B Street         |
      | Street 2     | B Street 2       |
      | City         | B City           |
      | Country      | Albania          |
      | State        | Has              |
      | Postal Code  | 12345            |
    And I click "Continue"
    Then I should see "New address (B Prefix B Fname B Mname B Lname B Suffix, B Organization, B Street B Street 2, B CITY HA AL 12345, 12345)" for "Select Single Page Checkout Billing Address" select

  Scenario: Check guest email is saved in popup
    And I click on "Billing Address Select"
    And I click on "New Address Option"
    And "New Address Popup Form" must contains values:
      | Email        | test@example.com |
    And I close ui dialog

  Scenario: Fill and save shipping address via popup and create order
    And I click on "Shipping Address Select"
    And I click on "New Address Option"
    And I fill "New Address Popup Form" with:
      | Label        | S Address      |
      | Name Prefix  | S Prefix       |
      | First Name   | S Fname        |
      | Middle Name  | S Mname        |
      | Last Name    | S Lname        |
      | Name Suffix  | S Suffix       |
      | Organization | S Organization |
      | Phone        | 67890          |
      | Street       | S Street       |
      | Street 2     | S Street 2     |
      | City         | S City         |
      | Country      | Georgia        |
      | State        | Guria          |
      | Postal Code  | 67890          |
    And I click "Continue"
    Then I should see "New address (S Prefix S Fname S Mname S Lname S Suffix, S Organization, S Street S Street 2, 67890 S City, Georgia, 67890)" for "Select Single Page Checkout Shipping Address" select
    When I click "Delete this shopping list after submitting order"
    And I fill "Checkout Order Form" with:
      | PO Number | Order1 |
    And I wait "Submit Order" button
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Check guest order with new shipping address and new billing address
    Given I proceed as the Admin
    And I go to Sales/Orders
    When I click View "Order1" in grid
    And I should see "B Address B Prefix B Fname B Mname B Lname B Suffix B Organization B Street B Street 2 B CITY HA AL 12345 12345"
    And I should see "S Address S Prefix S Fname S Mname S Lname S Suffix S Organization S Street S Street 2 67890 S City Georgia 67890"
    And I should see "Flat Rate 2"

  Scenario: Create order with new billing address and ship to this address
    Given I proceed as the Guest
    And I open page with shopping list Shopping List
    And I scroll to top
    And I wait line items are initialized
    When I click "Create Order"
    And I uncheck "Save my data and create an account" on the checkout page
    And I click on "Billing Address Select"
    And I click on "New Address Option"
    And I fill "New Address Popup Form" with:
      | Email        | test@example.com |
      | Label        | B Address        |
      | Name Prefix  | B Prefix         |
      | First Name   | B Fname          |
      | Middle Name  | B Mname          |
      | Last Name    | B Lname          |
      | Name Suffix  | B Suffix         |
      | Organization | B Organization   |
      | Phone        | 12345            |
      | Street       | B Street         |
      | Street 2     | B Street 2       |
      | City         | B City           |
      | Country      | Germany          |
      | State        | Berlin           |
      | Postal Code  | 12345            |
    And I click "Continue"
    Then I should see "New address (B Prefix B Fname B Mname B Lname B Suffix, B Organization, B Street B Street 2, 12345 B City, Germany, 12345)" for "Select Single Page Checkout Billing Address" select
    And There is no shipping method available for this order
    When I click on "Shipping Address Select"
    And I click on "New Address Option"
    And I fill "New Address Popup Form" with:
      | Label        | S Address      |
      | Name Prefix  | S Prefix       |
      | First Name   | S Fname        |
      | Middle Name  | S Mname        |
      | Last Name    | S Lname        |
      | Name Suffix  | S Suffix       |
      | Organization | S Organization |
      | Phone        | 67890          |
      | Street       | S Street       |
      | Street 2     | S Street 2     |
      | City         | S City         |
      | Country      | Albania        |
      | State        | Has            |
      | Postal Code  | 67890          |
    And I click "Continue"
    Then I should see "New address (S Prefix S Fname S Mname S Lname S Suffix, S Organization, S Street S Street 2, S CITY HA AL 67890, 67890)" for "Select Single Page Checkout Shipping Address" select
    And I should see "Flat Rate 2: $2.00"
    When I check "Use billing address" on the checkout page
    Then I should see "Flat Rate: $3.00"
    When I click on "Billing Address Select"
    And I click on "New Address Option"
    Then "New Address Popup Form" must contains values:
      | Email        | test@example.com |
      | Label        | B Address        |
      | Name Prefix  | B Prefix         |
      | First Name   | B Fname          |
      | Middle Name  | B Mname          |
      | Last Name    | B Lname          |
      | Name Suffix  | B Suffix         |
      | Organization | B Organization   |
      | Phone        | 12345            |
      | Street       | B Street         |
      | Street 2     | B Street 2       |
      | City         | B City           |
      | Country      | Germany          |
      | State        | Berlin           |
      | Postal Code  | 12345            |
    When I fill "New Address Popup Form" with:
      | Country      | Ukraine            |
      | State        | Cherkas'ka Oblast' |
    And I click "Continue"
    Then There is no shipping method available for this order
    When I uncheck "Use billing address" on the checkout page
    Then I should see "New address (12345 Ukraine B City, B Street B Street 2, B Organization, B Prefix B Fname B Mname B Lname B Suffix, 12345)" for "Select Single Page Checkout Shipping Address" select
    And There is no shipping method available for this order
    When I click on "Billing Address Select"
    And I click on "New Address Option"
    And I fill "New Address Popup Form" with:
      | Country      | Germany          |
      | State        | Berlin           |
    And I click "Continue"
    And I click "Delete this shopping list after submitting order"
    And I fill "Checkout Order Form" with:
      | PO Number | Order2 |
    And I check "Use billing address" on the checkout page
    Then I should see "Flat Rate: $3.00"
    And I wait "Submit Order" button
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Check guest order with new billing address and ship to this address
    Given I proceed as the Admin
    And I go to Sales/Orders
    When I click View "Order2" in grid
    And I should see "B Address B Prefix B Fname B Mname B Lname B Suffix B Organization B Street B Street 2 12345 B City Germany 12345"
    And I should not see "S Address S Prefix S Fname S Mname S Lname S Suffix S Organization S Street S Street 2 67890 S City Georgia 67890"
    And I should see "Flat Rate"

  Scenario: Create order and check that no flash messages on success page
    Given I proceed as the Guest
    And I open page with shopping list Shopping List
    And I scroll to top
    And I wait line items are initialized
    And I click "Create Order"
    And I click "Delete this shopping list after submitting order"
    And I click on "Billing Address Select"
    And I click on "New Address Option"
    And I fill "New Address Popup Form" with:
      | Email        | test@example.com |
      | Label        | B Address        |
      | Name Prefix  | B Prefix         |
      | First Name   | B Fname          |
      | Middle Name  | B Mname          |
      | Last Name    | B Lname          |
      | Name Suffix  | B Suffix         |
      | Organization | B Organization   |
      | Phone        | 12345            |
      | Street       | B Street         |
      | Street 2     | B Street 2       |
      | City         | B City           |
      | Country      | Germany          |
      | State        | Berlin           |
      | Postal Code  | 12345            |
    And I click "Continue"
    And I check "Use billing address" on the checkout page
    And I uncheck "Save my data and create an account" on the checkout page
    And I wait "Submit Order" button
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And I should not see flash messages
