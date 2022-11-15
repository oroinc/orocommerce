Feature: Customer Tax Code CRUD
  In order to manage customer tax codes
  As Administrator
  I need to be able to view, create, edit and delete customer tax codes

  Scenario: Feature Background
    Given I login as administrator

  Scenario: Create new customer tax code
    When I go to Taxes/ Customer Tax Codes
    And press "Create Customer Tax Code"
    And fill form with:
      | Code        | CUSTOMER_TAX_CODE_1      |
      | Description | Test customer tax code 1 |
    And I press "Save and Close"
    Then I should see "Customer Tax Code has been saved" flash message

  Scenario: View customer tax code on index and view pages
    When I go to Taxes/ Customer Tax Codes
    Then I should see following grid:
      | Code                | Description              |
      | CUSTOMER_TAX_CODE_1 | Test customer tax code 1 |
    When I click view "CUSTOMER_TAX_CODE_1" in grid
    Then I should see Customer Tax Code with:
      | Code        | CUSTOMER_TAX_CODE_1      |
      | Description | Test customer tax code 1 |

  Scenario: Edit customer tax code
    When I go to Taxes/ Customer Tax Codes
    And I click view "CUSTOMER_TAX_CODE_1" in grid
    And I press "Edit Customer Tax Code"
    And fill form with:
      | Code        | CUSTOMER_TAX_CODE_2      |
      | Description | Test customer tax code 2 |
    And I press "Save and Close"
    Then I should see "Customer Tax Code has been saved" flash message

  Scenario: Try to set invalid code for customer tax code
    When I go to Taxes/ Customer Tax Codes
    And I click view "CUSTOMER_TAX_CODE_2" in grid
    And I press "Edit Customer Tax Code"
    And fill form with:
      | Code | code 2 |
    And I press "Save and Close"
    Then I should see validation errors:
      | Code | This value should contain only latin letters, numbers and symbols "-" or "_". |

  Scenario: Try to create new customer tax code with invalid code
    When I go to Taxes/ Customer Tax Codes
    And I should see alert with message "You have unsaved changes, are you sure you want to leave this page?"
    And I accept alert
    And press "Create Customer Tax Code"
    And fill form with:
      | Code | CODE 1 |
    And I press "Save and Close"
    Then I should see validation errors:
      | Code | This value should contain only latin letters, numbers and symbols "-" or "_". |

  Scenario: Try to create new customer tax code when such code already exists
    When I go to Taxes/ Customer Tax Codes
    And I should see alert with message "You have unsaved changes, are you sure you want to leave this page?"
    And I accept alert
    And press "Create Customer Tax Code"
    And fill form with:
      | Code | CUSTOMER_TAX_CODE_2 |
    And I press "Save and Close"
    Then I should see validation errors:
      | Code | This value is already used. |

  Scenario: Delete customer tax code
    When I go to Taxes/ Customer Tax Codes
    And I click delete CUSTOMER_TAX_CODE_2 in grid
    And confirm deletion
    Then I should see "Customer Tax Code Deleted" flash message
