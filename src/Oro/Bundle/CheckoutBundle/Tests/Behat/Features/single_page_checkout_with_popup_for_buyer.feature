@ticket-BB-15015
@ticket-BB-15624
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRate2Integration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:ShippingRuleForFlatRate2.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml
@regression

Feature: Single Page Checkout With Popup for Buyer
  In order to complete the checkout process
  As a Buyer
  I want to fill billing address and shipping address in dialog window and complete checkout

  Scenario: Create different window session
    Given sessions active:
      | Admin  |first_session |
      | User   |second_session|

  Scenario: Enable Single Page Checkout Workflow
    Given There is USD currency in the system configuration
    And I proceed as the Admin
    And I login as administrator
    And I go to System/Workflows
    When I click "Activate" on row "Single Page Checkout" in grid
    And I click "Activate"
    Then I should see "Workflow activated" flash message

  Scenario: Configure shipping rules
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

  Scenario: Configure payment rule restricted to certain country
    Given I go to System/Payment Rules
    And I click Edit "Default" in grid
    And I click "Add"
    And fill "Payment Rule Form" with:
      | Country1 | Albania |
    When I save and close form
    Then I should see "Payment rule has been saved" flash message

  Scenario: Check notification shown for no payment method selected
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I scroll to top
    And I wait line items are initialized
    And I click "Create Order"
    And I should see "No payment methods are available, please contact us to complete the order submission."
    And I should see "Please select payment method." flash message
    And I should see a "Disabled Submit Order Button" element

  Scenario: Add default country to payment rule to make possible to continue order with predefined address
    Given I proceed as the Admin
    And I go to System/Payment Rules
    And I click Edit "Default" in grid
    And I click "Add"
    And fill "Payment Rule Form" with:
      | Country1 | Germany |
    When I save and close form
    Then I should see "Payment rule has been saved" flash message

  Scenario: Check Submit Order button is not disabled anymore and payment method is available
    Given I proceed as the User
    And I reload the page
    And I should not see a "Disabled Submit Order Button" element
    And I should see "Payment Term"

  Scenario: Create order with predefined billing address and predefined shipping address
    Then Checkout "Order Summary Products Grid" should contain products:
      | 400-Watt Bulb Work Light | 5 | items |
    And I should see Checkout Totals with data:
      | Subtotal | $10.00 |
    When I select "Fifth avenue, 10115 Berlin, Germany" from "Select Billing Address"
    And I select "Fifth avenue, 10115 Berlin, Germany" from "Select Shipping Address"
    And I click "Delete this shopping list after submitting order"
    And I wait "Submit Order" button
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Create order with predefined billing address and ship to this address
    Given I open page with shopping list List 1
    And I scroll to top
    And I wait line items are initialized
    And I click "Create Order"
    And I should not see flash messages
    And I select "Fifth avenue, 10115 Berlin, Germany" from "Select Billing Address"
    And I check "Use billing address" on the checkout page
    And I click "Delete this shopping list after submitting order"
    And I wait "Submit Order" button
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Validate new billing address form
    Given I open page with shopping list List 1
    And I scroll to top
    And I wait line items are initialized
    And I click "Create Order"
    When I click on "Billing Address Select"
    And I click on "New Address Option"
    Then I should see "UiDialog" with elements:
      | Title        | Billing Address |
      | okButton     | Continue        |
    And Email is not required field
    And "New Address Popup Form" must contains values:
      | Label        | Primary address |
      | Organization | ORO             |
      | Street       | Fifth avenue    |
      | City         | Berlin          |
      | Country      | Germany         |
      | State        | Berlin          |
      | Postal Code  | 10115           |
    When I fill "New Address Popup Form" with:
      | Label        | |
      | Organization | |
      | Street       | |
      | City         | |
      | Postal Code  | |
    And I click "Continue"
    Then I should see "New Address Popup Form" validation errors:
      | First Name   | First Name and Last Name or Organization should not be blank. |
      | Last Name    | Last Name and First Name or Organization should not be blank. |
      | Organization | Organization or First Name and Last Name should not be blank. |
      | Street       | This value should not be blank.                               |
      | City         | This value should not be blank.                               |
      | Postal Code  | This value should not be blank.                               |
    When I close ui dialog
    Then I should see "ORO, Fifth avenue, 10115 Berlin, Germany" for "Select Single Page Checkout Billing Address" select

  Scenario: Validate new shipping address form
    Given I scroll to top
    When I click on "Shipping Address Select"
    And I click on "New Address Option"
    Then I should see "UiDialog" with elements:
      | Title        | Shipping Address |
      | okButton     | Continue         |
    And "New Address Popup Form" must contains values:
      | Label        | Primary address |
      | Organization | ORO             |
      | Street       | Fifth avenue    |
      | City         | Berlin          |
      | Country      | Germany         |
      | State        | Berlin          |
      | Postal Code  | 10115           |
    When I fill "New Address Popup Form" with:
      | Label        | |
      | Organization | |
      | Street       | |
      | City         | |
      | Postal Code  | |
    And I click "Continue"
    Then I should see "New Address Popup Form" validation errors:
      | First Name   | First Name and Last Name or Organization should not be blank. |
      | Last Name    | Last Name and First Name or Organization should not be blank. |
      | Organization | Organization or First Name and Last Name should not be blank. |
      | Street       | This value should not be blank.                               |
      | City         | This value should not be blank.                               |
      | Postal Code  | This value should not be blank.                               |
    When I close ui dialog
    Then I should see "ORO, Fifth avenue, 10115 Berlin, Germany" for "Select Single Page Checkout Shipping Address" select

  Scenario: Create order with predefined shipping address and new billing address
    Given I scroll to top
    And I click on "Billing Address Select"
    And I click on "New Address Option"
    And I fill "New Address Popup Form" with:
      | Label        | B Address      |
      | Name Prefix  | B Prefix       |
      | First Name   | B Fname        |
      | Middle Name  | B Mname        |
      | Last Name    | B Lname        |
      | Name Suffix  | B Suffix       |
      | Organization | B Organization |
      | Phone        | 12345          |
      | Street       | B Street       |
      | Street 2     | B Street 2     |
      | City         | B City         |
      | Country      | Albania        |
      | State        | Has            |
      | Postal Code  | 12345          |
    And I click "Continue"
    And I should see "Amanda Cole"
    And I click on "Billing Address Select"
    And I click on "New Address Option"
    And "New Address Popup Form" must contains values:
      | Label        | B Address      |
      | Name Prefix  | B Prefix       |
      | First Name   | B Fname        |
      | Middle Name  | B Mname        |
      | Last Name    | B Lname        |
      | Name Suffix  | B Suffix       |
      | Organization | B Organization |
      | Phone        | 12345          |
      | Street       | B Street       |
      | Street 2     | B Street 2     |
      | City         | B City         |
      | Country      | Albania        |
      | State        | Has            |
      | Postal Code  | 12345          |
    When I close ui dialog
    Then I should see "New address (B Prefix B Fname B Mname B Lname B Suffix, B Organization, B Street B Street 2, B CITY HA AL 12345, 12345)" for "Select Single Page Checkout Billing Address" select
    When I reload the page
    Then I should see "New address (B Prefix B Fname B Mname B Lname B Suffix, B Organization, B Street B Street 2, B CITY HA AL 12345, 12345)" for "Select Single Page Checkout Billing Address" select
    When I click on "Billing Address Select"
    And I click on "New Address Option"
    Then "New Address Popup Form" must contains values:
      | Label        | B Address      |
      | Name Prefix  | B Prefix       |
      | First Name   | B Fname        |
      | Middle Name  | B Mname        |
      | Last Name    | B Lname        |
      | Name Suffix  | B Suffix       |
      | Organization | B Organization |
      | Phone        | 12345          |
      | Street       | B Street       |
      | Street 2     | B Street 2     |
      | City         | B City         |
      | Country      | Albania        |
      | State        | Has            |
      | Postal Code  | 12345          |
    When I close ui dialog
    When I select "Fifth avenue, 10115 Berlin, Germany" from "Select Shipping Address"
    And I click "Delete this shopping list after submitting order"
    And I wait "Submit Order" button
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    When I click "click here to review"
    Then I should be on Order Frontend View page
    And I should see Order with data:
      | Billing Address  | B Address B Prefix B Fname B Mname B Lname B Suffix B Organization B Street B Street 2 B CITY HA AL 12345 12345 |
      | Shipping Address | Primary address ORO Fifth avenue 10115 Berlin Germany                                                           |
      | Shipping Method  | Flat Rate                                                                                                       |
      | Payment Method   | Payment Term                                                                                                    |

  Scenario: Create order with new shipping address and predefined billing address
    Given I open page with shopping list List 1
    And I scroll to top
    And I wait line items are initialized
    And I click "Create Order"
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
    When I click on "Shipping Address Select"
    And I click on "New Address Option"
    Then "New Address Popup Form" must contains values:
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
    When I close ui dialog
    Then I should see "New address (S Prefix S Fname S Mname S Lname S Suffix, S Organization, S Street S Street 2, 67890 S City, Georgia, 67890)" for "Select Single Page Checkout Shipping Address" select
    When I reload the page
    Then I should see "New address (S Prefix S Fname S Mname S Lname S Suffix, S Organization, S Street S Street 2, 67890 S City, Georgia, 67890)" for "Select Single Page Checkout Shipping Address" select
    When I click on "Shipping Address Select"
    And I click on "New Address Option"
    Then "New Address Popup Form" must contains values:
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
    When I close ui dialog
    And I select "Fifth avenue, 10115 Berlin, Germany" from "Select Billing Address"
    And I click "Delete this shopping list after submitting order"
    And I wait "Submit Order" button
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    When I click "click here to review"
    Then I should be on Order Frontend View page
    And I should see Order with data:
      | Billing Address  | Primary address ORO Fifth avenue 10115 Berlin Germany                                                             |
      | Shipping Address | S Address S Prefix S Fname S Mname S Lname S Suffix S Organization S Street S Street 2 67890 S City Georgia 67890 |
      | Shipping Method  | Flat Rate 2                                                                                                       |
      | Payment Method   | Payment Term                                                                                                      |

  Scenario: Create order with new shipping address and new billing address
    Given I open page with shopping list List 1
    And I scroll to top
    And I wait line items are initialized
    And I click "Create Order"
    And I click on "Billing Address Select"
    And I click on "New Address Option"
    And I fill "New Address Popup Form" with:
      | Label        | B Address      |
      | Name Prefix  | B Prefix       |
      | First Name   | B Fname        |
      | Middle Name  | B Mname        |
      | Last Name    | B Lname        |
      | Name Suffix  | B Suffix       |
      | Organization | B Organization |
      | Phone        | 12345          |
      | Street       | B Street       |
      | Street 2     | B Street 2     |
      | City         | B City         |
      | Country      | Albania        |
      | State        | Has            |
      | Postal Code  | 12345          |
    And I click "Continue"
    Then I should see "New address (B Prefix B Fname B Mname B Lname B Suffix, B Organization, B Street B Street 2, B CITY HA AL 12345, 12345)" for "Select Single Page Checkout Billing Address" select
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
    And I wait "Submit Order" button
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    When I click "click here to review"
    Then I should be on Order Frontend View page
    And I should see Order with data:
      | Billing Address  | B Address B Prefix B Fname B Mname B Lname B Suffix B Organization B Street B Street 2 B CITY HA AL 12345 12345   |
      | Shipping Address | S Address S Prefix S Fname S Mname S Lname S Suffix S Organization S Street S Street 2 67890 S City Georgia 67890 |
      | Shipping Method  | Flat Rate 2                                                                                                       |
      | Payment Method   | Payment Term                                                                                                      |

  Scenario: Create order with new billing address and ship to this address
    Given I open page with shopping list List 1
    And I scroll to top
    And I wait line items are initialized
    When I click "Create Order"
    And I click on "Billing Address Select"
    And I click on "New Address Option"
    And I fill "New Address Popup Form" with:
      | Label        | B Address      |
      | Name Prefix  | B Prefix       |
      | First Name   | B Fname        |
      | Middle Name  | B Mname        |
      | Last Name    | B Lname        |
      | Name Suffix  | B Suffix       |
      | Organization | B Organization |
      | Phone        | 12345          |
      | Street       | B Street       |
      | Street 2     | B Street 2     |
      | City         | B City         |
      | Country      | Albania        |
      | State        | Has            |
      | Postal Code  | 12345          |
    And I click "Continue"
    Then I should see "New address (B Prefix B Fname B Mname B Lname B Suffix, B Organization, B Street B Street 2, B CITY HA AL 12345, 12345)" for "Select Single Page Checkout Billing Address" select
    When I check "Use billing address" on the checkout page
    And I click "Delete this shopping list after submitting order"
    And I wait "Submit Order" button
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    When I click "click here to review"
    Then I should be on Order Frontend View page
    And I should see Order with data:
      | Billing Address  | B Address B Prefix B Fname B Mname B Lname B Suffix B Organization B Street B Street 2 B CITY HA AL 12345 12345 |
      | Shipping Address | B Address B Prefix B Fname B Mname B Lname B Suffix B Organization B Street B Street 2 B CITY HA AL 12345 12345 |
      | Shipping Method  | Flat Rate 2                                                                                                     |
      | Payment Method   | Payment Term                                                                                                    |

  Scenario: Shipping rule should apply correctly after switching to address from address book
    Given I open page with shopping list List 1
    And I scroll to top
    And I wait line items are initialized
    And I click "Create Order"
    And I click on "Billing Address Select"
    And I click on "New Address Option"
    And I fill "New Address Popup Form" with:
      | Label        | B Address          |
      | Name Prefix  | B Prefix           |
      | First Name   | B Fname            |
      | Middle Name  | B Mname            |
      | Last Name    | B Lname            |
      | Name Suffix  | B Suffix           |
      | Organization | B Organization     |
      | Phone        | 12345              |
      | Street       | B Street           |
      | Street 2     | B Street 2         |
      | City         | B City             |
      | Country      | Ukraine            |
      | State        | Cherkas'ka Oblast' |
      | Postal Code  | 12345              |
    And I click "Continue"
    When I check "Use billing address" on the checkout page
    Then There is no shipping method available for this order
    When I select "Fifth avenue, 10115 Berlin, Germany" from "Select Billing Address"
    Then I should see "Flat Rate: $3.00"
    When I click "Delete this shopping list after submitting order"
    And I wait "Submit Order" button
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And I should not see flash messages
