@ticket-BB-18293
@fixture-OroShoppingListBundle:ShoppingListFixture.yml
Feature: Shopping List Line Items
  In order to manager shopping lists on front store
  As a Buyer
  I need to be able to update shopping list

  Scenario: Create different window sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Merge Line items
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And Buyer is on "Shopping List 5" shopping list
    And I should see following line items in "Shopping List Line Items Table":
      | SKU | Quantity | Unit |
      | AA1 | 1        | set  |
      | AA1 | 2        | item |
    When I fill "Shopping List Line Item 1 Form" with:
      | Unit | item |
    Then I should see following line items in "Shopping List Line Items Table":
      | SKU | Quantity | Unit |
      | AA1 | 3        | item |

  Scenario: Check edit permission for line item
    When I proceed as the Admin
    And login as administrator
    And I go to Customers / Customer User Roles
    And I click edit "Buyer" in grid
    And select following permissions:
      | Shopping List Line Item | Edit:None |
    And I save form
    Then I should see "Customer User Role has been saved" flash message

    When I proceed as the Buyer
    And I reload the page
    And I fill "Shopping List Line Item 1 Form" with:
      | Quantity | 5 |
    Then I should see "You do not have permission to perform this action." flash message

  Scenario: Check delete permission for line item
    When I proceed as the Admin
    And select following permissions:
      | Shopping List Line Item | Delete:None |
    And I save form
    Then I should see "Customer User Role has been saved" flash message

    When I proceed as the Buyer
    And I reload the page
    Then I should not see a "Remove Line Item" element
