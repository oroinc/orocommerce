@regression
@ticket-BB-15015
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml

Feature: Single Page Checkout With Popup for Sales
  In order to complete the checkout process
  As a Sales Representative
  I want to fill billing address and shipping address in dialog window, complete checkout and save the address to my address book

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
    And I click "Activate" in modal window
    Then I should see "Workflow activated" flash message

  Scenario: Change customer user role
    Given I go to Customers/Customer Users
    And I click Edit "AmandaRCole@example.org" in grid
    When I fill form with:
      | Roles | Administrator |
    And I save and close form
    Then I should see "Customer User has been saved" flash message

  Scenario: Create order with new shipping address and new billing address without saving addresses
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
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
      | Save Address | false          |
    And I click "Continue"
    Then I should see "New address (B Prefix B Fname B Mname B Lname B Suffix, B Organization, B Street B Street 2, B CITY HA AL 12345, 12345)" for "Select Single Page Checkout Billing Address" select
    And I scroll to top
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
      | Save Address | false          |
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
      | Shipping Method  | Flat Rate                                                                                                         |
      | Payment Method   | Payment Term                                                                                                      |
    When I click "Address Book"
    And I should not see "B Street"
    And I should not see "S Street"

  Scenario: Create order with new shipping address and new billing address with saving addresses
    Given I proceed as the User
    And I open page with shopping list List 1
    When I scroll to top
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
      | Street       | BStreet        |
      | Street 2     | B Street 2     |
      | City         | BCity          |
      | Country      | Albania        |
      | State        | Has            |
      | Postal Code  | 12345          |
      | Save Address | true           |
    And I click "Continue"
    Then I should see "New address (B Prefix B Fname B Mname B Lname B Suffix, B Organization, BStreet B Street 2, BCITY HA AL 12345, 12345)" for "Select Single Page Checkout Billing Address" select
    And I scroll to top
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
      | Street       | SStreet        |
      | Street 2     | S Street 2     |
      | City         | SCity          |
      | Country      | Georgia        |
      | State        | Guria          |
      | Postal Code  | 67890          |
      | Save Address | true           |
    And I click "Continue"
    Then I should see "New address (S Prefix S Fname S Mname S Lname S Suffix, S Organization, SStreet S Street 2, 67890 SCity, Georgia, 67890)" for "Select Single Page Checkout Shipping Address" select
    When I click "Delete this shopping list after submitting order"
    And I wait "Submit Order" button
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    When I click "click here to review"
    Then I should be on Order Frontend View page
    And I should see Order with data:
      | Billing Address  | B Address B Prefix B Fname B Mname B Lname B Suffix B Organization BStreet B Street 2 BCITY HA AL 12345 12345   |
      | Shipping Address | S Address S Prefix S Fname S Mname S Lname S Suffix S Organization SStreet S Street 2 67890 SCity Georgia 67890 |
      | Shipping Method  | Flat Rate                                                                                                         |
      | Payment Method   | Payment Term                                                                                                      |
    When I click "Address Book"
    Then I should see following "Customer Users Address Book Grid" grid:
      | Customer Address | City   | State  | Zip/Postal Code | Country |
      | Fifth avenue     | Berlin | Berlin | 10115           | Germany |
      | Fourth avenue    | Berlin | Berlin | 10111           | Germany |
      | BStreet          | BCity  | Has    | 12345           | Albania |
      | SStreet          | SCity  | Guria  | 67890           | Georgia |

  Scenario: Check "Use billing address" disappears when billing address with no shipping option is chosen
    Given I proceed as the User
    When I open page with shopping list List 1
    And I scroll to top
    And I wait line items are initialized
    And I click "Create Order"
    Then I should see "ORO, Fifth avenue, 10115 Berlin, Germany" for "Select Single Page Checkout Billing Address" select
    And I select "S Prefix S Fname S Mname S Lname S Suffix, S Organization, SStreet S Street 2, 67890 SCity, Georgia, 67890" from "Select Shipping Address"
    And I check "Use billing address" on the checkout page
    Then I should see "ORO, Fifth avenue, 10115 Berlin, Germany" for "Select Single Page Checkout Shipping Address" select
    And I select "B Prefix B Fname B Mname B Lname B Suffix, B Organization, BStreet B Street 2, BCITY HA AL 12345, 12345" from "Select Billing Address"
    And I should not see "Use billing address"
    Then I should see "ORO, Fifth avenue, 10115 Berlin, Germany" for "Select Single Page Checkout Shipping Address" select

  Scenario: Check "Use billing address" appears for new address and can be checked
    Given I should not see flash messages
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
      | Street       | BStreet        |
      | Street 2     | B Street 2     |
      | City         | BCity          |
      | Country      | Albania        |
      | State        | Has            |
      | Postal Code  | 12345          |
      | Save Address | true           |
    And I click "Continue"
    And I should see "Use billing address"
    And I check "Use billing address" on the checkout page
    And I wait "Submit Order" button
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And I should not see flash messages
