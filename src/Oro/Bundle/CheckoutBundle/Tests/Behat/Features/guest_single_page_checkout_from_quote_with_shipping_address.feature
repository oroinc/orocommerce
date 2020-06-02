@regression
@ticket-BB-16502
@fixture-OroUserBundle:user.yml
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroSaleBundle:GuestQuoteWithAddressFixture.yml

Feature: Guest Single Page checkout from quote with shipping address
  In order to provide possibility create quotes for non authorized users
  As an Guest User
  I want to be able to accept Quote without registration on website

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Guest | second_session |
    And set configuration property "oro_sale.enable_guest_quote" to "1"
    And set configuration property "oro_checkout.guest_checkout" to "1"
    And I proceed as the Admin
    And login as administrator
    And I go to System/Workflows
    When I click "Activate" on row "Single Page Checkout" in grid
    And I click "Activate" in modal window
    Then I should see "Workflow activated" flash message

  Scenario: Set payment term for Non-Authenticated Visitors group
    Given I go to Customers/ Customer Groups
    And I click Edit Non-Authenticated Visitors in grid
    And I fill form with:
      | Payment Term | net 10 |
    When I save form
    Then I should see "Customer group has been saved" flash message

  Scenario: Send Quote to customer
    Given I go to Sales/Quotes
    And I click view Quote_1 in grid
    And I click "Send to Customer"
    When I fill "Send to Customer Form" with:
      | To | Charlie Sheen |
    And click "Send"
    Then I should see "Quote_1 successfully sent to customer" flash message

  Scenario: Accept quote
    Given I proceed as the Guest
    And I visit guest quote link for quote Quote_1
    When I click "Accept and Submit to Order"
    And click "Submit"
    Then I should see "Checkout"

  Scenario: Fill and save billing address via popup
    Given I scroll to top
    And I click on "Billing Address Select"
    And I click on "New Address Option"
    And I should see "Address 1 My Organization 801 Scenic Hwy HAINES CITY FL US 33844"
    When I fill "New Address Popup Form" with:
      | Email           | tester@test.com |
      | First name      | Charlie         |
      | Last name       | Sheen           |
      | Street          | 800 Scenic Hwy  |
      | City            | Haines City     |
      | Country         | United States   |
      | State           | Florida         |
      | Zip/Postal Code | 33844           |
    And I click "Continue"
    And I scroll to top
    And I wait until all blocks on one step checkout page are reloaded
    Then I should see "New address (Charlie Sheen, 800 Scenic Hwy, HAINES CITY FL US 33844)" for "Select Single Page Checkout Billing Address" select
    And I should see "Address 1 My Organization 801 Scenic Hwy HAINES CITY FL US 33844"

  Scenario: Complete checkout
    Given I uncheck "Save my data and create an account" on the checkout page
    And I wait "Submit Order" button
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
