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
    And I click "Add Address"
    And I fill "New Address Popup Form" with:
      | Label                  | Address without a State |
      | First Name             | Tester1                 |
      | Last Name              | Testerson1              |
      | Email                  | tester1@test.com        |
      | Street                 | Fifth avenue            |
      | City                   | Texas                   |
      | Country                | United States           |
      | Zip/Postal Code        | 10115                   |
    And I click "Add Address" in modal window
    Then I should see "New Address Popup Form" validation errors:
      | State | This value should not be blank. |
    And I fill "New Address Popup Form" with:
      | State | Texas |
    And I click "Add Address" in modal window
    And I click "Ship to This Address"
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
