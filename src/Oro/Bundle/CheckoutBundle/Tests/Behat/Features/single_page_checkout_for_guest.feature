@ticket-BB-19443
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml
@regression

Feature: Single Page Checkout for Guest
  In order to complete the checkout process
  As a Guest
  I want to fill late registration form and complete checkout

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Guest | second_session |

  Scenario: Enable Single Page Checkout Workflow
    Given I proceed as the Admin
    And There is USD currency in the system configuration
    And I login as administrator
    And I go to System/Workflows
    When I click "Activate" on row "Single Page Checkout" in grid
    And I click "Activate" in modal window
    Then I should see "Workflow activated" flash message

  Scenario: Enable Guest Checkout and Guest Shopping List in system configuration
    Given I go to System/Configuration
    And I follow "Commerce/Sales/Checkout" on configuration sidebar
    And uncheck "Use default" for "Guest Checkout" field
    And I check "Guest Checkout"
    When I click "Save settings"
    Then I should see "Configuration saved" flash message
    When I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Enable Guest Shopping List" field
    And I check "Enable Guest Shopping List"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Set payment term for Non-Authenticated Visitors group
    Given go to Customers/ Customer Groups
    And I click Edit Non-Authenticated Visitors in grid
    And I fill form with:
      | Payment Term | net 10 |
    When I save form
    Then I should see "Customer group has been saved" flash message

  Scenario: Prepare checkout
    Given I proceed as the Guest
    And I am on homepage
    And type "SKU123" in "search"
    And I click "Search Button"
    And I click "View Details" for "SKU123" product
    And I click "Add to Shopping List"
    When I open shopping list widget
    And I click "View List"
    And I scroll to top
    And I wait line items are initialized
    And I click "Create Order"

  Scenario: Fill checkout addresses
    When I click on "Billing Address Select"
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
    And I scroll to top
    And I wait until all blocks on one step checkout page are reloaded
    Then I should see "New address (B Prefix B Fname B Mname B Lname B Suffix, B Organization, B Street B Street 2, B CITY HA AL 12345, 12345)" for "Select Single Page Checkout Billing Address" select

    When I check "Use billing address" on the checkout page
    And I wait until all blocks on one step checkout page are reloaded
    Then I should see "New address (B Prefix B Fname B Mname B Lname B Suffix, B Organization, B Street B Street 2, B CITY HA AL 12345, 12345)" for "Select Single Page Checkout Shipping Address" select

  Scenario: Check email address validation fail
    Given I fill form with:
      | Email Address    | AmandaRCole@example.org |
      | Password         | 123123qQ                |
      | Confirm Password | 123123qQ                |

    And I wait "Submit Order" button
    And I click "Submit Order"
    Then I should see validation errors:
      | Email Address | This email is already used. |

  Scenario: Check email address validation success
    Given I fill form with:
      | Email Address    | AmandaRColeNEW@example.org |
    And I wait "Submit Order" button
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
