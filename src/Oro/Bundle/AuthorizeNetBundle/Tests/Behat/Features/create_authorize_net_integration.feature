@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-AuthorizeNetFixture.yml
Feature: Create Authorize.Net integration
  Scenario: Create new AuthorizeNet Integration
    Given I login as administrator
    When I go to System/Integrations/Manage Integrations
    And I click "Create Integration"
    And I select "Authorize.NET" from "Type"
    And I fill integration fields with next data:
      | Name               | Authorize       |
      | DefaultLabel       | Authorize       |
      | ShortLabel         | Au              |
      | AllowedCreditCards | Mastercard      |
      | APiLoginId         | qwer1234        |
      | TransactionKey     | qwerty123456    |
      | ClientKey          | qwer12345       |
      | CVVRequiredEntry   | true            |
      | PaymentAction      | Authorize       |
    And I save and close form
    Then I should see "Integration saved" flash message
    And I should see that "Authorize" is in 2 row

  Scenario: Create new Payment Rule
    Given I go to System/Payment Rules
    When I click "Create Payment Rule"
    And I check "Enabled"
    And I fill in "Name" with "Authorize"
    And I fill in "Sort Order" with "1"
    And I select "Authorize" from "Method"
    And click add payment method button
    And I save and close form
    Then I should see "Payment rule has been saved" flash message

  Scenario: Frontend AcceptJs Card validation error when pay order with AuthorizeNet
    Given There are products in the system available for order
    When I signed in as AmandaRCole@example.org on the store frontend
    And I open page with shopping list List 1
    And I press "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I had checked "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I fill credit card fields with next data:
      | CreditCardNumber | 5555555555554444 |
      | Month            | 11               |
      | Year             | 27               |
      | CVV              | 123              |
    And I click "Continue"
    Then I should see "Authorize.Net communication error." flash message

  Scenario: Error from Backend API when pay order with AuthorizeNet
    Given There are products in the system available for order
    And I open page with shopping list List 1
    And I press "Create Order"
    And I fill credit card fields with next data:
      | CreditCardNumber | 5105105105105100 |
      | Month            | 11               |
      | Year             | 27               |
      | CVV              | 123              |
    And I click "Continue"
    And I had checked "Delete the shopping list" on the "Order Review" checkout step and press Submit Order
    Then I should see "We were unable to process your payment. Please verify your payment information and try again, or try another payment method." flash message

  Scenario: Successful order payment with AuthorizeNet
    Given There are products in the system available for order
    And I open page with shopping list List 1
    And I press "Create Order"
    And I fill credit card fields with next data:
      | CreditCardNumber | 5424000000000015 |
      | Month            | 11               |
      | Year             | 27               |
      | CVV              | 123              |
    And I click "Continue"
    And I had checked "Delete the shopping list" on the "Order Review" checkout step and press Submit Order
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title