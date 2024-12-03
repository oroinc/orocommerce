@ticket-BB-24659
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml

Feature: Ensure that checkout state field drop down in address form becomes enabled after returning to previous step
  In order to check state field drop down in checkout address form works correctly
  As a Buyer
  I want to have possibility to select state after returning to previous checkout step in state field drop down

  Scenario: Check possibility to select state after returning to previous checkout step
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And fill form with:
      | Select Billing Address | New address             |
      | Label                  | Address without a State |
      | First Name             | Tester1                 |
      | Last Name              | Testerson1              |
      | Email                  | tester1@test.com        |
      | Street                 | Fifth avenue            |
      | City                   | Texas                   |
      | Country                | United States           |
      | State                  | Texas                   |
      | Zip/Postal Code        | 10115                   |
    And I click "Ship to This Address"
    And I click "Continue"
    And I click on "Edit Billing Information"
    When I fill form with:
      | State | State |
    When I click "Continue"
    Then I should see validation errors:
      | State | This value should not be blank. |
    When I fill form with:
      | State | Texas |
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
