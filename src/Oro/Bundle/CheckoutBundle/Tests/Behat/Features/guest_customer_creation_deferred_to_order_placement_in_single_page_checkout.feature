@ticket-BB-27354
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroCheckoutBundle:CheckoutCustomerFixture.yml
@fixture-OroCheckoutBundle:GuestGroupCaliforniaTax.yml

Feature: Guest customer creation deferred to order placement in single page checkout
  In order to avoid breaking checkout customizations built around the original guest customer
  creation timing
  As a store administrator
  I want guest customer creation deferred to order placement only when I explicitly enable it

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I enable configuration options:
      | oro_shopping_list.availability_for_guests |
      | oro_checkout.guest_checkout               |
    And set configuration property "oro_checkout.defer_guest_customer_creation_to_order" to "1"
    And I change configuration options:
      | oro_tax.use_as_base_by_default | destination |

  Scenario: Set payment term and tax code for Non-Authenticated Visitors group
    Given I proceed as the Admin
    And I login as administrator
    And go to Customers/ Customer Groups
    And I click Edit Non-Authenticated Visitors in grid
    And I fill form with:
      | Payment Term | net 10               |
      | Tax Code     | GUEST_GROUP_TAX_CODE |
    When I save form
    Then I should see "Customer group has been saved" flash message

  Scenario: Activate Single Page Checkout workflow
    Given go to System/ Workflows
    When I click "Activate" on row "Single Page Checkout" in grid
    And I click "Activate" in modal window
    Then I should see "Workflow activated" flash message

  Scenario: Guest starts single page checkout and fills billing address without placing the order
    Given I proceed as the Buyer
    And I am on the homepage
    When I type "GUESTTAX1" in "search"
    And I click "Search Button"
    And I click "Guest Tax Test Product"
    And I click "Add to Shopping List"
    And I follow "Shopping List" link within flash message "Product has been added to \"Shopping list\""
    And I click "Create Order"
    And I click on "Add Address Single Page Checkout Btn" with title "Add" in element "Single Page Checkout Billing Section"
    And I fill "New Address Popup Form" with:
      | Email           | deferred.guest@example.com |
      | First Name      | DeferredGuest              |
      | Last Name       | Buyer                      |
      | Street          | Fifth avenue               |
      | City            | Los Angeles                |
      | Country         | United States              |
      | State           | California                 |
      | Zip/Postal Code | 90001                      |
    And I click "Add Address" in modal window
    And I scroll to top
    Then I should see "Checkout"

  Scenario: Admin sees no guest customer user yet because creation is deferred
    Given I proceed as the Admin
    When I go to Customers/ Customer Users
    And I filter "Email Address" as contains "deferred.guest@example.com"
    Then there is no records in grid

  Scenario: Guest completes and submits the order
    Given I proceed as the Buyer
    And I check "Use billing address" on the checkout page
    And I check "Flat Rate" on the checkout page
    And I check "Payment Terms" on the checkout page
    And I uncheck "Save my data and create an account" on the checkout page
    Then I should see Checkout Totals with data:
      | Subtotal | $10.00 |
      | Tax      | $1.00  |
    And I wait "Submit Order" button
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Admin now sees the guest customer user was created after order placement
    Given I proceed as the Admin
    When I go to Customers/ Customer Users
    And I filter "Email Address" as contains "deferred.guest@example.com"
    Then I should see following grid containing rows:
      | Email Address              | First Name    | Last Name |
      | deferred.guest@example.com | DeferredGuest | Buyer     |
