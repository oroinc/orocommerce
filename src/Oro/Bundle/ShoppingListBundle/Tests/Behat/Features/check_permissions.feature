@regression
@ticket-BB-15117
@fixture-OroShoppingListBundle:ShoppingListFixtureWithCustomers.yml

Feature: Check permissions
  In order to check frontstore shopping list acl
  As a customer user
  I want to check user and guest permissions

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Set view permission to User
    Given I proceed as the Admin
    And I login as administrator
    And I go to Customers/ Customer User Roles
    And I click edit "Buyer" in grid
    And select following permissions:
      | Shopping List | View:User | Create:None | Edit:None | Delete:None | Assign:None | Duplicate:None |
    When I save form
    Then I should see "Customer User Role has been saved" flash message

  Scenario: Check that buyer can view shopping list
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I am on homepage
    When I open shopping list widget
    Then I should see "Shopping List 1" on shopping list widget

  Scenario Outline: Check that buyer can view shopping list
    Given I reload the page
    And I should not see "<name>" on shopping list widget

    Examples:
      | name            |
      | Shopping List 2 |
      | Shopping List 3 |
      | Shopping List 4 |
      | Shopping List 5 |

  Scenario: Set view permission to Department
    Given I proceed as the Admin
    And select following permissions:
      | Shopping List | View:Department |
    When I save form
    Then I should see "Customer User Role has been saved" flash message

  Scenario Outline: Check that buyer can view shopping list
    Given I proceed as the Buyer
    And I reload the page
    When I open shopping list widget
    Then I should see "<name>" on shopping list widget

    Examples:
      | name            |
      | Shopping List 1 |
      | Shopping List 5 |

  Scenario Outline: Check that buyer can view shopping list
    Given I should not see "<name>" on shopping list widget

    Examples:
      | name            |
      | Shopping List 2 |
      | Shopping List 3 |
      | Shopping List 4 |

  Scenario: Set view permission to Сorporate
    Given I proceed as the Admin
    And select following permissions:
      | Shopping List | View:Сorporate |
    When I save form
    Then I should see "Customer User Role has been saved" flash message

  Scenario Outline: Check that buyer can view shopping list
    Given I proceed as the Buyer
    And I reload the page
    When I open shopping list widget
    Then I should see "<name>" on shopping list widget

    Examples:
      | name            |
      | Shopping List 1 |
      | Shopping List 3 |
      | Shopping List 5 |

  Scenario Outline: Check that buyer can view shopping list
    Given I should not see "<name>" on shopping list widget

    Examples:
      | name            |
      | Shopping List 2 |
      | Shopping List 4 |

  Scenario: Check buyer assign without permission
    Given I open page with shopping list Shopping List 1
    Then should not see "Customer Field"
    And I should see "Customer: Amanda Cole"

  Scenario: Set assign permission to User
    Given I proceed as the Admin
    And select following permissions:
      | Shopping List | Assign:User |
    When I save form
    Then I should see "Customer User Role has been saved" flash message

  Scenario: Check buyer assign permission
    Given I proceed as the Buyer
    And I open page with shopping list Shopping List 1
    And should see "Customer Select Field" with options:
      | Value        |
      | Amanda Cole  |

  Scenario Outline: Check that buyer didn't see assign field
    Given I open page with shopping list <name>
    When I should not see "Customer Select Field"

    Examples:
      | name            |
      | Shopping List 3 |
      | Shopping List 5 |

  Scenario: Set assign permission to Department
    Given I proceed as the Admin
    And select following permissions:
      | Shopping List | Assign:Department |
    When I save form
    Then I should see "Customer User Role has been saved" flash message

  Scenario Outline: Check buyer assign permission
    Given I proceed as the Buyer
    And I open page with shopping list <name>
    And should see "Customer Select Field" with options:
      | Value        |
      | Amanda Cole  |
      | Nancy Sallee |

    Examples:
      | name            |
      | Shopping List 1 |
      | Shopping List 5 |

  Scenario: Check that buyer didn't see assign field
    Given I open page with shopping list Shopping List 3
    When I should not see "Customer Select Field"

  Scenario: Set assign permission to Corporate
    Given I proceed as the Admin
    And select following permissions:
      | Shopping List | Assign:Сorporate |
    When I save form
    Then I should see "Customer User Role has been saved" flash message

  Scenario Outline: Check buyer assign permission
    Given I proceed as the Buyer
    And I open page with shopping list <name>
    And should see "Customer Select Field" with options:
      | Value        |
      | Amanda Cole  |
      | Nancy Sallee |
      | Ruth Maxwell |

    Examples:
      | name            |
      | Shopping List 1 |
      | Shopping List 3 |
      | Shopping List 5 |

  Scenario: Set edit permission to User
    Given I proceed as the Admin
    And select following permissions:
      | Shopping List | Edit:User |
    When I save form
    Then I should see "Customer User Role has been saved" flash message

  Scenario: Check that buyer can edit own shopping list
    Given I proceed as the Buyer
    And I reload the page
    And I open page with shopping list Shopping List 1
    When click "Edit Shopping List Label"
    And type "Shopping List User 1" in "Shopping List Label Input"
    When click "Save"
    Then I should see "Record has been successfully updated" flash message

  Scenario Outline: Check that buyer cannot edit shopping list
    Given I open page with shopping list <name>
    Then I should not see "Edit Shopping List Label"
    Then I should not see "Add a Note to This Shopping List"
    And I should see "Notes: Simple note"

    Examples:
      | name            |
      | Shopping List 3 |
      | Shopping List 5 |

  Scenario: Set edit permission to Department
    Given I proceed as the Admin
    And select following permissions:
      | Shopping List | Edit:Department |
    When I save form
    Then I should see "Customer User Role has been saved" flash message

  Scenario Outline: Check that buyer can edit own shopping list
    Given I proceed as the Buyer
    And I open page with shopping list <name>
    When click "Edit Shopping List Label"
    And type "<new name>" in "Shopping List Label Input"
    When click "Save"
    Then I should see "Record has been successfully updated" flash message

    Examples:
      | name                 | new name                   |
      | Shopping List User 1 | Shopping List Department 1 |
      | Shopping List 5      | Shopping List Department 5 |

  Scenario: Check that buyer cannot edit shopping list
    Given I open page with shopping list Shopping List 3
    Then I should not see "Edit Shopping List Label"

  Scenario: Set edit permission to Corporate
    Given I proceed as the Admin
    And select following permissions:
      | Shopping List | Edit:Сorporate |
    When I save form
    Then I should see "Customer User Role has been saved" flash message

  Scenario Outline: Check that buyer can edit own shopping list
    Given I proceed as the Buyer
    And I reload the page
    And I open page with shopping list <name>
    When click "Edit Shopping List Label"
    And type "<new name>" in "Shopping List Label Input"
    When click "Save"
    Then I should see "Record has been successfully updated" flash message

    Examples:
      | name                       | new name                  |
      | Shopping List Department 1 | Shopping List Corporate 1 |
      | Shopping List 3            | Shopping List Corporate 3 |
      | Shopping List Department 5 | Shopping List Corporate 5 |

  Scenario: Set delete permission to User
    Given I proceed as the Admin
    And select following permissions:
      | Shopping List | Delete:User |
    When I save form
    Then I should see "Customer User Role has been saved" flash message

  Scenario: Check that buyer can delete own shopping list
    Given I proceed as the Buyer
    And I reload the page
    And I open page with shopping list Shopping List Corporate 1
    Then I should see "Delete"
    And I click "Delete"
    When I confirm deletion
    Then I should see "Shopping List deleted" flash message

  Scenario Outline: Check that buyer cannot delete own shopping list
    Given I open page with shopping list <name>
    Then I should not see "Delete"

    Examples:
      | name                      |
      | Shopping List Corporate 3 |
      | Shopping List Corporate 5 |

  Scenario: Set delete permission to Department
    Given I proceed as the Admin
    And select following permissions:
      | Shopping List | Delete:Department |
    When I save form
    Then I should see "Customer User Role has been saved" flash message

  Scenario: Check that buyer cannot delete shopping list
    Given I proceed as the Buyer
    And I reload the page
    And I open page with shopping list Shopping List Corporate 5
    Then I should see "Delete"
    And I click "Delete"
    When I confirm deletion
    Then I should see "Shopping List deleted" flash message

  Scenario: Check that buyer can delete shopping list
    Given I open page with shopping list Shopping List Corporate 3
    Then I should not see "Delete"

  Scenario: Set edit permission to Corporate
    Given I proceed as the Admin
    And select following permissions:
      | Shopping List | Delete:Сorporate |
    When I save form
    Then I should see "Customer User Role has been saved" flash message

  Scenario: Check that buyer can delete shopping list
    Given I proceed as the Buyer
    And I reload the page
    And I open page with shopping list Shopping List Corporate 3
    Then I should see "Delete"
    And I click "Delete"
    When I confirm deletion
    Then I should see "Shopping List deleted" flash message

  Scenario: Set create permission to User
    Given I proceed as the Admin
    And select following permissions:
      | Shopping List | Create:User |
    When I save form
    Then I should see "Customer User Role has been saved" flash message

  Scenario: Create shopping list with User permission
    Given I proceed as the Buyer
    And I reload the page
    And I open shopping list widget
    And I click "Create New List"
    When I click "Create"
    Then I should see "1 Shopping List"

  Scenario: Set create permission to Department
    Given I proceed as the Admin
    And select following permissions:
      | Shopping List | Create:Department |
    When I save form
    Then I should see "Customer User Role has been saved" flash message

  Scenario: Create shopping list with Department permission
    Given I proceed as the Buyer
    And I reload the page
    And I open shopping list widget
    And I click "Create New List"
    When I click "Create"
    Then I should see "2 Shopping Lists"

  Scenario: Set create permission to Corporate
    Given I proceed as the Admin
    And select following permissions:
      | Shopping List | Create:Сorporate |
    When I save form
    Then I should see "Customer User Role has been saved" flash message

  Scenario: Create shopping list with User permission
    Given I proceed as the Buyer
    And I reload the page
    And I open shopping list widget
    And I click "Create New List"
    When I click "Create"
    Then I should see "3 Shopping Lists"
