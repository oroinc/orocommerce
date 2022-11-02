@regression
@ticket-BB-20967
@fixture-OroUserBundle:manager.yml
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml

Feature: Quote email notification with override quote prices
  Check that "Override Quote Prices" permission work correctly. Quote can be sent to customer only when price of the
  product has not changed.

  Scenario: Feature Background
    Given sessions active:
      | Admin   | first_session  |
      | Manager | second_session |

  Scenario: Check permissions for Manager
    Given I proceed as the Admin
    And login as administrator
    When I go to System/ User Management/ Roles
    And filter Label as is equal to "Sales Manager"
    And click edit Sales Manager in grid
    And select following permissions:
      | Backoffice Quote Flow with Approvals | View Workflow:Global | Perform transitions:Global |
    And check "Override Quote Prices" entity permission
    And save form
    Then I should see "Role saved" flash message

  Scenario: Create simple product
    Given I go to Products/ Products
    When I click "Create Product"
    And fill form with:
      | Type | Simple |
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU              | PSKU   |
      | Name             | Item   |
      | Status           | Enable |
      | Unit Of Quantity | item   |
    And click "AddPrice"
    And fill "Product Price Form" with:
      | Price List | Default Price List |
      | Quantity   | 1                  |
      | Value      | 100                |
      | Currency   | $                  |
    And save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Create quote with default price
    Given I proceed as the Manager
    And login as "ethan" user
    When I go to Sales/Quotes
    And click "Create Quote"
    And fill "Quote Form" with:
      | Customer        | AmandaRCole |
      | Customer User   | Amanda Cole |
      | LineItemProduct | PSKU        |
    And save and close form
    And agree that shipping cost may have changed
    Then I should see "Quote has been saved" flash message
    And "Send to Customer" button is not disabled

  Scenario: Create quote with custom price
    Given I go to Sales/Quotes
    When I click "Create Quote"
    And fill "Quote Form" with:
      | Customer        | AmandaRCole |
      | Customer User   | Amanda Cole |
      | LineItemProduct | PSKU        |
      | LineItemPrice   | 10          |
    And save and close form
    And agree that shipping cost may have changed
    Then I should see "Quote has been saved" flash message
    And "Send to Customer" button is disabled
