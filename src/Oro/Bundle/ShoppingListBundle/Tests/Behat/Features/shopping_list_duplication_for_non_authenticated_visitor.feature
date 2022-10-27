@ticket-BB-16433
@ticket-BB-16299
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
Feature: Shopping list duplication for non authenticated visitor

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Guest | second_session |
    And I enable configuration options:
      | oro_shopping_list.availability_for_guests |
      | oro_checkout.guest_checkout               |

  Scenario: Admin sets payment term for Non-Authenticated Visitors group
    Given I proceed as the Admin
    And I login as administrator
    And go to Customers/ Customer Groups
    And I click Edit Non-Authenticated Visitors in grid
    And I fill form with:
      | Payment Term | net 10 |
    When I save form
    Then I should see "Customer group has been saved" flash message

  Scenario: Try duplicate shopping list for non authenticated visitor
    Given I proceed as the Guest
    And I am on the homepage
    When type "SKU123" in "search"
    And I click "Search Button"
    Then I should see "400-Watt Bulb Work Light"
    When I click "Add to Shopping List" for "SKU123" product
    Then I should see "Product has been added to " flash message
    And I open shopping list widget
    And I click "View List"
    And click on "Create Order"
    And I click "Continue as a Guest"
    And I fill form with:
      | Email           | Andy001@example.com |
      | First Name      | Andy                |
      | Last Name       | Derrick             |
      | Organization    | TestCompany         |
      | Street          | Fifth avenue        |
      | City            | Berlin              |
      | Country         | Germany             |
      | State           | Berlin              |
      | Zip/Postal Code | 10115               |
    And I click "Ship to This Address"
    And click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I uncheck "Save my data and create an account" on the checkout page
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

    Given I proceed as the Admin
    And I go to Sales / Shopping Lists
    And I click view "Shopping List" in grid
    When I click "Duplicate List"
    Then I should see "Unable to duplicate as only one shopping list is allowed for unregistered users" flash message

  Scenario: Authenticate visitor and success duplicate shopping list
    Given I proceed as the Guest
    When type "SKU123" in "search"
    And I click "Search Button"
    Then I should see "400-Watt Bulb Work Light"
    When I click "Add to Shopping List" for "SKU123" product
    Then I should see "Product has been added to " flash message
    And I open shopping list widget
    And I click "View List"
    And click on "Create Order"
    And I click "Continue as a Guest"
    And I fill form with:
      | Email           | Andy002@example.com |
      | First Name      | Andy2               |
      | Last Name       | Derrick             |
      | Organization    | TestCompany         |
      | Street          | Fifth avenue        |
      | City            | Berlin              |
      | Country         | Germany             |
      | State           | Berlin              |
      | Zip/Postal Code | 10115               |
    And I click "Ship to This Address"
    And click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I check "Save my data and create an account" on the checkout page
    And I type "Andy002@example.com" in "Email Address"
    And I type "Andy002@example.com" in "Password"
    And I type "Andy002@example.com" in "Confirm Password"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And Andy002@example.com customer user confirms registration
    And I login as Andy002@example.com buyer in old session

    Given I proceed as the Admin
    And I go to Sales / Shopping Lists
    And I click view "Shopping List" in grid
    When I click "Duplicate List"
    Then I should see "The shopping list has been duplicated" flash message and I close it
    And I should not see "Unable to duplicate as only one shopping list is allowed for unregistered users" flash message
    And I should see "Shopping List (copied "

  Scenario: Check created customer user address
    Given I go to Customers / Customer Users
    When I click on Andy002@example.com in grid
    And I should see "Fifth avenue"
    And I should see "10115 Berlin"
    And I should see "Germany"
