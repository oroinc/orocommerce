@regression
@ticket-BB-16146
@ticket-BB-16431
@behat-test-env
Feature: Paypal express validation
  In order to be sure that express checkout names are always unique
  As a administrator
  I want to be able to create new integration with name same as express
  checkout name and system will not allows to do this

  Scenario: Create new PayPal Integration and express checkout name and see validation error
    Given I login as administrator
    And I go to System/Integrations/Manage Integrations
    And I click "Create Integration"
    And I select "PayPal Payments Pro" from "Type"
    And I fill PayPal integration fields with next data:
      | Name                         | PayPalPro      |
      | Label                        | PayPalPro      |
      | Short Label                  | PPlPro         |
      | Allowed Credit Card Types    | Mastercard     |
      | Partner                      | PayPal         |
      | Vendor                       | qwerty123456   |
      | User                         | qwer12345      |
      | Password                     | qwer123423r23r |
      | Payment Action               | Authorize      |
      | Express Checkout Name        | PayPalPro      |
      | Express Checkout Label       | ExpressPayPal  |
      | Express Checkout Short Label | ExprPPl        |
    When I save form
    Then I should see "Express Checkout cannot have the same name as the integration itself."
    And I fill "PayPalForm" with:
      | Express Checkout Name | NewPayPalPro |
    When I save form
    Then I should see validation errors:
      | Password | This value should not be blank. |
    And I fill "PayPalForm" with:
      | Password | qwer123423r23r |
    When I save form
    Then I should see "Integration saved" flash message

  Scenario: Create new PayPal Integration with name used in express checkout and see validation error
    When I go to System/Integrations/Manage Integrations
    And I click "Create Integration"
    And I select "PayPal Payments Pro" from "Type"
    And I fill PayPal integration fields with next data:
      | Name                         | NewPayPalPro   |
      | Label                        | PayPalPro      |
      | Short Label                  | PPlPro         |
      | Allowed Credit Card Types    | Mastercard     |
      | Partner                      | PayPal         |
      | Vendor                       | qwerty123456   |
      | User                         | qwer12345      |
      | Password                     | qwer123423r23r |
      | Payment Action               | Authorize      |
      | Express Checkout Name        | ExpPayPalPro   |
      | Express Checkout Label       | ExpressPayPal  |
      | Express Checkout Short Label | ExprPPl        |
    And I save form
    Then I should see "The payment method names should be unique. \"NewPayPalPro\" is used by a different payment integration."
