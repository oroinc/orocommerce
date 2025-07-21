@fixture-OroOrderBundle:order.yml

Feature: Order documents
  In order to send order documents to customer
  As an Administrator
  I should be able to add documents to the order

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Add files to documents field and check them on view page
    Given I proceed as the Admin
    And I login as administrator
    And I go to Sales/Orders
    When I click edit SimpleOrder in grid
    And I click "Add File"
    And I click "Add File"
    And I fill "Order Customer Documents Form" with:
      | Documents File 1            | file1.txt |
      | Documents File 2            | file2.txt |
      | Documents File 3            | file3.txt |
      | Documents File Sort Order 1 | 3         |
      | Documents File Sort Order 2 | 1         |
      | Documents File Sort Order 3 | 2         |
    And I save and close form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    And I should see following "Order Customer Documents Grid" grid:
      | Sort order | File name | Uploaded by |
      | 1          | file2.txt | John Doe    |
      | 2          | file3.txt | John Doe    |
      | 3          | file1.txt | John Doe    |

  Scenario: View documents on store front
    Given I operate as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    And I click "Order History"
    When I click view "SimpleOrder" in grid
    Then I should see "Documents"
    And I should see "file2.txt"
    And I should see "file3.txt"
    And I should see "file1.txt"

  Scenario: Order without documents should not have documents field
    Given I click "Account Dropdown"
    And I click "Order History"
    When I click view "SecondOrder" in grid
    Then I should see "Order #SecondOrder"
    And I should not see "Documents"
