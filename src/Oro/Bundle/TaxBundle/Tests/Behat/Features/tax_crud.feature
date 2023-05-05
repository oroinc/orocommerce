Feature: Tax CRUD
  In order to manage taxes
  As Administrator
  I need to be able to view, create, edit and delete taxes

  Scenario: Feature Background
    Given I login as administrator

  Scenario: Create new tax
    When I go to Taxes/ Taxes
    And press "Create Tax"
    And fill form with:
      | Code        | TAX_1      |
      | Description | Test tax 1 |
      | Rate        | 10         |
    And I press "Save and Close"
    Then I should see "Tax has been saved" flash message

  Scenario: View tax on index and view pages
    When I go to Taxes/ Taxes
    Then I should see following grid:
      | Code  | Description | Rate |
      | TAX_1 | Test tax 1  | 10%  |
    When I click view "TAX_1" in grid
    Then I should see Tax with:
      | Code        | TAX_1      |
      | Description | Test tax 1 |
      | Rate        | 10%        |

  Scenario: Edit tax
    When I go to Taxes/ Taxes
    And I click view "TAX_1" in grid
    And I press "Edit Tax"
    And fill form with:
      | Code        | TAX_2      |
      | Description | Test tax 2 |
      | Rate        | 20         |
    And I press "Save and Close"
    Then I should see "Tax has been saved" flash message

  Scenario: Try to set invalid code and rate for tax
    When I go to Taxes/ Taxes
    And I click view "TAX_2" in grid
    And I press "Edit Tax"
    And fill form with:
      | Code | tax 2 |
      | Rate | rate2 |
    And I press "Save and Close"
    Then I should see validation errors:
      | Code | This value should contain only latin letters, numbers and symbols "-" or "_". |
      | Rate | This value should be a valid number.                                          |

  Scenario: Try to create new tax with invalid code and rate
    When I go to Taxes/ Taxes
    And I should see alert with message "You have unsaved changes, are you sure you want to leave this page?"
    And I accept alert
    And press "Create Tax"
    And fill form with:
      | Code | TAX 1 |
      | Rate | rate2 |
    And I press "Save and Close"
    Then I should see validation errors:
      | Code | This value should contain only latin letters, numbers and symbols "-" or "_". |
      | Rate | This value should be a valid number.                                          |

  Scenario: Try to create new tax when such code already exists
    When I go to Taxes/ Taxes
    And I should see alert with message "You have unsaved changes, are you sure you want to leave this page?"
    And I accept alert
    And press "Create Tax"
    And fill form with:
      | Code | TAX_2 |
      | Rate | 10    |
    And I press "Save and Close"
    Then I should see validation errors:
      | Code | This value is already used. |

  Scenario: Delete tax
    When I go to Taxes/ Taxes
    And I click delete TAX_2 in grid
    And confirm deletion
    Then I should see "Tax Deleted" flash message
