@ticket-BB-15168
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml

Feature: Checkout by customer user with new role
  In order to to create order from Shopping List on front store
  As a buyer
  I want to be able to create order with any customer user role which has sufficient permissions

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create new Customer User Role
    Given I proceed as the Admin
    And I login as administrator
    And I go to Customers/Customer User Roles
    And I click "Create Customer User Role"
    And I fill form with:
      | Role | Test customer user role |
    And select following permissions:
      | Checkout                | View:User (Own) | Create:User (Own) | Edit:User (Own) | Delete:User (Own) | Assign:User (Own) |
      | Customer User Address   | View:User (Own) | Create:User (Own) | Edit:User (Own) | Delete:User (Own) | Assign:User (Own) |
      | Order                   | View:User (Own) | Create:User (Own) | Edit:User (Own) | Delete:User (Own) | Assign:User (Own) |
      | Shopping List           | View:User (Own) | Create:User (Own) | Edit:User (Own) | Delete:User (Own) | Assign:User (Own) |
      | Shopping List Line Item | View:User (Own) | Create:User (Own) | Edit:User (Own) | Delete:User (Own) | Assign:User (Own) |
    And select following permissions:
      | Checkout | View Workflow:User (Own) | Perform transitions:User (Own) |
    And I click "Checkout" tab
    And I check "Use Any Billing Address From The Customer User's Address Book" entity permission
    And I check "Use Any Shipping Address From The Customer User's Address Book" entity permission
    And I save and close form
    Then I should see "Customer User Role has been saved" flash message
    And I should see "Test customer user role"

  Scenario: Assign new Customer User Role to Customer User
    Given I go to Customers/Customer Users
    And click Edit AmandaRCole@example.org in grid
    And I fill form with:
      | Buyer                   | false |
      | Test customer user role | true  |
    And I save and close form
    And should see "Customer User has been saved" flash message

  Scenario: Create Checkout as Customer User with new Customer User Role
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I do the order through completion, and should be on order view page
