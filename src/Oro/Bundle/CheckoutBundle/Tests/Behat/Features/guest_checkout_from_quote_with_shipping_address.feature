@regression
@ticket-BB-16502
@fixture-OroUserBundle:user.yml
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroSaleBundle:GuestQuoteWithAddressFixture.yml

Feature: Guest checkout from quote with shipping address
  In order to provide possibility create quotes for non authorized users
  As an Guest User
  I want to be able to accept Quote without registration on website

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Guest | second_session |
    And set configuration property "oro_sale.enable_guest_quote" to "1"
    And set configuration property "oro_checkout.guest_checkout" to "1"

  Scenario: Set payment term for Non-Authenticated Visitors group
    Given I proceed as the Admin
    And login as administrator
    And I go to Customers/ Customer Groups
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

  Scenario: Complete checkout
    Given I click "Continue as a Guest"
    When I fill form with:
      | Email           | tester@test.com |
      | First name      | Charlie         |
      | Last name       | Sheen           |
      | Street          | 800 Scenic Hwy  |
      | City            | Haines City     |
      | Country         | United States   |
      | State           | Florida         |
      | Zip/Postal Code | 33844           |
    And click "Continue"
    And I should see "Address 1 My Organization 801 Scenic Hwy HAINES CITY FL US 33844"
    And click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I uncheck "Save my data and create an account" on the checkout page
    And I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"
