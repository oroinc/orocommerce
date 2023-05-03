Feature: Product Tax Code CRUD
  In order to manage product tax codes
  As Administrator
  I need to be able to view, create, edit and delete product tax codes

  Scenario: Feature Background
    Given I login as administrator

  Scenario: Create new product tax code
    When I go to Taxes/ Product Tax Codes
    And press "Create Product Tax Code"
    And fill form with:
      | Code        | PRODUCT_TAX_CODE_1      |
      | Description | Test product tax code 1 |
    And I press "Save and Close"
    Then I should see "Product Tax Code has been saved" flash message

  Scenario: View product tax code on index and view pages
    When I go to Taxes/ Product Tax Codes
    Then I should see following grid:
      | Code               | Description             |
      | PRODUCT_TAX_CODE_1 | Test product tax code 1 |
    When I click view "PRODUCT_TAX_CODE_1" in grid
    Then I should see Product Tax Code with:
      | Code        | PRODUCT_TAX_CODE_1      |
      | Description | Test product tax code 1 |

  Scenario: Edit product tax code
    When I go to Taxes/ Product Tax Codes
    And I click view "PRODUCT_TAX_CODE_1" in grid
    And I press "Edit Product Tax Code"
    And fill form with:
      | Code        | PRODUCT_TAX_CODE_2      |
      | Description | Test product tax code 2 |
    And I press "Save and Close"
    Then I should see "Product Tax Code has been saved" flash message

  Scenario: Try to set invalid code for product tax code
    When I go to Taxes/ Product Tax Codes
    And I click view "PRODUCT_TAX_CODE_2" in grid
    And I press "Edit Product Tax Code"
    And fill form with:
      | Code | code 2 |
    And I press "Save and Close"
    Then I should see validation errors:
      | Code | This value should contain only latin letters, numbers and symbols "-" or "_". |

  Scenario: Try to create new product tax code with invalid code
    When I go to Taxes/ Product Tax Codes
    And I should see alert with message "You have unsaved changes, are you sure you want to leave this page?"
    And I accept alert
    And press "Create Product Tax Code"
    And fill form with:
      | Code | CODE 1 |
    And I press "Save and Close"
    Then I should see validation errors:
      | Code | This value should contain only latin letters, numbers and symbols "-" or "_". |

  Scenario: Try to create new product tax code when such code already exists
    When I go to Taxes/ Product Tax Codes
    And I should see alert with message "You have unsaved changes, are you sure you want to leave this page?"
    And I accept alert
    And press "Create Product Tax Code"
    And fill form with:
      | Code | PRODUCT_TAX_CODE_2 |
    And I press "Save and Close"
    Then I should see validation errors:
      | Code | This value is already used. |

  Scenario: Delete product tax code
    When I go to Taxes/ Product Tax Codes
    And I click delete PRODUCT_TAX_CODE_2 in grid
    And confirm deletion
    Then I should see "Product Tax Code Deleted" flash message
