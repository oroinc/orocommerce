@community-edition-only
@regression
@ticket-BB-11426
@ticket-BB-16275
@ticket-BB-15931
@fixture-OroCheckoutBundle:Products_quick_order_form_ce.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml

Feature: Guest quick order form
  In order to promote quick purchases by non-registered customers
  As a Sales rep
  I want quick order form to be accessible to guest customers

  Scenario: Create different window session
    Given sessions active:
      | Guest | first_session  |
      | Admin | second_session |

  Scenario: Quick order form for guest is disabled by default
    Given I proceed as the Guest
    And I am on the homepage
    And I should not see text matching "Quick Order Form"

  Scenario: Enable quick order form for guest
    Given I proceed as the Admin
    And I login as administrator
    And go to System/ Configuration
    And I follow "Commerce/Sales/Quick Order Form" on configuration sidebar
    And fill "Quick Order Configuration Form" with:
      | Enable Guest Quick Order Form Default | false |
      | Enable Guest Quick Order Form         | true  |
    And click "Save settings"

  Scenario: Quick order form for guest is disabled due to disabled dependant features
    Given I proceed as the Guest
    And I am on the homepage
    And I should not see text matching "Quick Order Form"

  Scenario: Enable all dependant features for guest
    Given I proceed as the Admin
    And follow "Commerce/Sales/Shopping List" on configuration sidebar
    And fill "Shopping List Configuration Form" with:
      | Enable Guest Shopping List Default | false |
      | Enable Guest Shopping List         | true  |
    And click "Save settings"
    And follow "Commerce/Sales/Request For Quote" on configuration sidebar
    And fill "Request For Quote Configuration Form" with:
      | Enable Guest RFQ Default | false |
      | Enable Guest RFQ         | true  |
    And click "Save settings"
    And follow "Commerce/Sales/Checkout" on configuration sidebar
    And fill "Checkout Configuration Form" with:
      | Enable Guest Checkout Default | false |
      | Enable Guest Checkout         | true  |
    And click "Save settings"
    And go to Customers/ Customer Groups
    And I click Edit Non-Authenticated Visitors in grid
    And I fill form with:
      | Payment Term | net 10 |
    And I save form

  Scenario: Create checkout from Shopping list and proceed till shipping method step
    Given I proceed as the Guest
    And I am on the homepage
    When I type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "PSKU1"
    And I click "Add to Shopping List"
    When I open page with shopping list "Shopping List"
    And I click "Create Order"
    And I click "Continue as a Guest"
    And I fill form with:
      | First Name      | Tester          |
      | Last Name       | Testerson       |
      | Email           | tester@test.com |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And I click "Ship to This Address"
    And I click "Continue"
    Then Buyer is on Shipping Method Checkout step

  Scenario: Create Order from Quick order when there is a started checkout from shopping list
    Given I proceed as the Guest
    And I am on the homepage
    And I should see text matching "Quick Order Form"
    And I click "Quick Order Form"
    And I should see "Add to Shopping List"
    And I should see "Get Quote"
    And I should see "Create Order"
    And I fill "Quick Order Form" with:
      | SKU1 | PSKU1 |
      | SKU2 | PSKU2 |
      | SKU3 | PSKU3 |
      | SKU4 | PSKU3 |
    And I wait for products to load
    And I fill "Quick Order Form" with:
      | QTY1  | 2   |
      | QTY2  | 4   |
      | UNIT2 | set |
      | QTY3  | 1   |
      | QTY4  | 1   |
    When I click "Create Order"
    Then I should see "You can have only 1 shopping list(s)."
    When I click "Create Order"
    And click "Continue as a Guest"
    And I fill form with:
      | First Name      | Tester          |
      | Last Name       | Testerson       |
      | Email           | tester@test.com |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And I should not see "Save address"
    And click "Continue"
    And I fill form with:
      | Label           | Home Address    |
      | First Name      | Tester          |
      | Last Name       | Testerson       |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And "Order Review" checkout step "Order Summary Products Grid" contains products
      | Product1`"'&йёщ®&reg;> | 2 | items |
      | Product2               | 4 | sets  |
      | Product3               | 2 | items |
    And I uncheck "Save my data and create an account" on the checkout page
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Check guest orders in management console and disable checkout feature
    Given I proceed as the Admin
    When I go to Sales/ Orders
    Then I should see "Tester Testerson" in grid with following data:
      | Customer      | Tester Testerson |
      | Customer User | Tester Testerson |
    When go to System/ Configuration
    And follow "Commerce/Sales/Checkout" on configuration sidebar
    And fill "Checkout Configuration Form" with:
      |Enable Guest Checkout Default|true |
      |Enable Guest Checkout        |false|
    And click "Save settings"

  Scenario: Add to shopping list from quick order page
    Given I proceed as the Guest
    And I am on homepage
    And I click "Quick Order Form"
    And I should see "Add to Shopping List"
    And I should see "Get Quote"
    And I should not see "Create Order"
    And I fill "Quick Order Form" with:
      | SKU1 | PSKU1 |
      | SKU2 | PSKU2 |
      | SKU3 | PSKU3 |
      | SKU4 | PSKU3 |
    And I wait for products to load
    And I fill "Quick Order Form" with:
      | QTY1  | 2   |
      | QTY2  | 4   |
      | UNIT2 | set |
      | QTY3  | 1   |
      | QTY4  | 1   |
    When I click "Add to Shopping List"
    Then I should see "3 products were added (view shopping list)." flash message

  Scenario: Check guest shopping lists in management console and disable shopping list feature
    Given I proceed as the Admin
    When I go to Sales/ Shopping Lists
    Then I should see "Shopping List" in grid with following data:
      |Customer      |N/A |
      |Customer User |N/A |
    When go to System/ Configuration
    And follow "Commerce/Sales/Shopping List" on configuration sidebar
    And fill "Shopping List Configuration Form" with:
      |Enable Guest Shopping List Default|true |
      |Enable Guest Shopping List        |false|
    And click "Save settings"

  Scenario: Create A Quote from quick order page
    Given I proceed as the Guest
    And I am on homepage
    And I click "Quick Order Form"
    And I should not see "Add to Shopping List"
    And I should see "Get Quote"
    And I should not see "Create Order"
    And I fill "Quick Order Form" with:
      | SKU1 | PSKU2 |
      | SKU2 | PSKU2 |
      | SKU3 | PSKU3 |
      | SKU4 | PSKU3 |
    And I wait for products to load
    And I fill "Quick Order Form" with:
      | QTY1  | 2   |
      | QTY2  | 4   |
      | UNIT2 | set |
      | QTY3  | 1   |
      | QTY4  | 1   |
    When I fill "Quick Add Copy Paste Form" with:
      | Paste your order | PSKU1 5 |
    And I click "Verify Order"
    And click "Get Quote"
    And I fill form with:
      | First Name             | Tester               |
      | Last Name              | Testerson            |
      | Email Address          | testerson@example.com|
      | Phone Number           | 72 669 62 82         |
      | Company                | Red Fox Tavern       |
      | Role                   | CEO                  |
      | Notes                  | Test note for quote. |
      | PO Number              | PO Test 01           |
    And Request a Quote contains products
      | Product2               | 2 | item |
      | Product2               | 4 | set  |
      | Product3               | 2 | item |
      | Product1`"'&йёщ®&reg;> | 5 | item |
    And I click "Submit Request"
    And I should see "Request has been saved" flash message

  Scenario: Check rfq in management console
    Given I proceed as the Admin
    When I go to Sales/ Requests For Quote
    Then I should see "Tester Testerson" in grid with following data:
      | Submitted By    | Tester Testerson     |
      | Customer        | Tester Testerson     |
      | Internal Status | Open                 |
      | PO Number       | PO Test 01           |
