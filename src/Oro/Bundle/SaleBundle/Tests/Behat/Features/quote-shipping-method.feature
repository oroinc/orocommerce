@regression
@fixture-OroDPDBundle:DPDIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroOrderBundle:OrderShippingMethod.yml
Feature: Select shipping method for Quote on backoffice
  In order to check shipping rules edit
  As a Administrator
  I want to create and edit order from the admin panel

  Scenario: Create two session
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Create quote with DPD shipping rule
    Given I proceed as the Admin
    And I login as administrator
    And go to Sales/ Quotes
    And click "Create Quote"
    And I fill "Quote Form" with:
      | Customer         | Company A                                                           |
      | Customer User    | Amanda Cole                                                         |
      | PO Number        | PO001                                                               |
      | LineItemProduct  | SKU1                                                                |
      | LineItemQuantity | 5                                                                   |
      | LineItemPrice    | 10                                                                  |
      | Shipping Address | Amanda Cole, ORO, VOTUM GmbH Ohlauer Str. 43, 10999 Berlin, Germany |
    When click "Calculate Shipping Button"
    Then I should see "Flat Rate $50.00"
    Then I should see "DPD DPD Classic $20.00"
    When click "Quote Flat Rate"
    When click "Quote DPD Classic"
    And should not see "Previously Selected Shipping Method Flat Rate: $50.00"
    And save and close form
    And should see "Quote has been saved" flash message
    And click "Send to Customer"
    And click "Send"
    And I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And click "Account"
    And click "Quotes"
    And click view "PO001" in grid
    And click "Accept and Submit to Order"
    When I press "Submit"
    And I select "Amanda Cole, ORO, VOTUM GmbH Ohlauer Str. 43, 10999 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And click "Continue"
    Then should see "Shipping $20.00"
    And should see "Flat Rate: $50.00"
    And should see "DPD Classic: $20.00"

  Scenario: Create quote with Overridden Shipping Cost Amount
    Given I proceed as the Admin
    And go to Sales/ Quotes
    And click "Create Quote"
    And I fill "Quote Form" with:
      | Customer                        | Company A                                                           |
      | Customer User                   | Amanda Cole                                                         |
      | PO Number                       | PO002                                                               |
      | LineItemProduct                 | SKU1                                                                |
      | LineItemQuantity                | 5                                                                   |
      | LineItemPrice                   | 10                                                                  |
      | Overridden shipping cost amount | 100                                                                 |
      | Shipping Address                | Amanda Cole, ORO, VOTUM GmbH Ohlauer Str. 43, 10999 Berlin, Germany |
    And click "Calculate Shipping Button"
    And click "Quote Flat Rate"
    And save and close form
    And should see "Quote has been saved" flash message
    And click "Edit"
    Then should not see "Previously Selected Shipping Method Flat Rate: $50.00"
    When click "Quote DPD Classic"
    Then should see "Previously Selected Shipping Method Flat Rate: $50.00"
    And click "Submit"
    And should see "Quote #2 successfully updated" flash message
    And click "Send to Customer"
    And click "Send"
    And I proceed as the User
    And click "Account"
    And click "Quotes"
    And click view "PO002" in grid
    And click "Accept and Submit to Order"
    When I press "Submit"
    And I select "Amanda Cole, ORO, VOTUM GmbH Ohlauer Str. 43, 10999 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And click "Continue"
    Then should see "Shipping $100.00"
    And should see "Flat Rate: $100.00"
    And should see "DPD Classic: $100.00"

  Scenario: Create quote with Shipping Method Locked
    Given I proceed as the Admin
    And go to Sales/ Quotes
    And click "Create Quote"
    And I fill "Quote Form" with:
      | Customer         | Company A                                                           |
      | Customer User    | Amanda Cole                                                         |
      | PO Number        | PO003                                                               |
      | LineItemProduct  | SKU1                                                                |
      | LineItemQuantity | 5                                                                   |
      | LineItemPrice    | 10                                                                  |
      | Shipping Address | Amanda Cole, ORO, VOTUM GmbH Ohlauer Str. 43, 10999 Berlin, Germany |
    And click "Calculate Shipping Button"
    And click "Quote Flat Rate"
    And fill form with:
      | Shipping Method Locked | true |
    And save and close form
    And should see "Quote has been saved" flash message
    And click "Send to Customer"
    And click "Send"
    And I proceed as the User
    And click "Account"
    And click "Quotes"
    And click view "PO003" in grid
    And click "Accept and Submit to Order"
    When I press "Submit"
    And I select "Amanda Cole, ORO, VOTUM GmbH Ohlauer Str. 43, 10999 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And click "Continue"
    Then should see "Shipping $50.00"
    And should see "Flat Rate: $50.00"
    And should not see "DPD Classic"

  Scenario: Create quote with Shipping Method Locked
    Given I proceed as the Admin
    And go to Sales/ Quotes
    And click "Create Quote"
    And I fill "Quote Form" with:
      | Customer         | Company A                                                           |
      | Customer User    | Amanda Cole                                                         |
      | PO Number        | PO004                                                               |
      | LineItemProduct  | SKU1                                                                |
      | LineItemQuantity | 5                                                                   |
      | LineItemPrice    | 10                                                                  |
      | Shipping Address | Amanda Cole, ORO, VOTUM GmbH Ohlauer Str. 43, 10999 Berlin, Germany |
    And click "Calculate Shipping Button"
    And click "Quote Flat Rate"
    And fill form with:
      | Allow Unlisted Shipping Method | true |
    And save and close form
    And should see "Quote has been saved" flash message
    And click "Send to Customer"
    And click "Send"
    And I go to System/ Shipping Rules
    And click disable "Flat Rate" in grid
    And I proceed as the User
    And click "Account"
    And click "Quotes"
    And click view "PO004" in grid
    And click "Accept and Submit to Order"
    When I press "Submit"
    And I select "Amanda Cole, ORO, VOTUM GmbH Ohlauer Str. 43, 10999 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And click "Continue"
    Then should see "Shipping $50.00"
    And should see "Flat Rate: $50.00"
    And should see "DPD Classic: $20.00"
