@fixture-OroProductBundle:Product_tax_codes_Inline_edit.yml
@regression
Feature: Inline Editing in Products Grids
  In order to quickly edit product information
  As an Administrator
  I want to use edit products inline in the products grid
#    Description
#    Enable inline editing in the grid on the Products -> Products page for the following columns:
#    Name (Default Value)
#    Inventory Status
#    Status
#    Tax Code
#
#    Scenario: Preconditions
#    I should have:
#    Tax codes:
#    TaxCode1
#    TaxCode2
#
#    product:
#    type - Simple
#    name - Product1
#    sku - SKU1
#    product status - enabled
#    inventory status - in stock
#    unit - each
#    price list - default
#    tax code - TaxCode1

  Scenario: Inline editing of Name field in grid
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |
    And I proceed as the Admin
    And I login as administrator
    And I go to Products / Products
    When I edit "Product1" Name as "Product2" without saving
    And I click "Save changes"
    Then I should see "Changing Page URLs" in the "UiWindow Title" element
    Then I click "Apply" in modal window
    Then I should see "Record has been successfully updated" flash message
    Then I should see following records in grid:
      | Product2 |
    And I reload the page
    And I show column Tax Code in grid

  Scenario Outline: Inline editing of Product fields in grid
    When I edit "Product2" <field> as "<input>"
    Then I should see following records in grid:
      | <value> |
    Examples:
      | field            | input        | value        |
      | Inventory Status | Out of Stock | Out of Stock |
      | Status           | Disabled     | Disabled     |
      | Tax Code         | XCODE2       | TaxCode2     |

  Scenario: Canceling inline editing on Changing Page URLs warning pop-up
    When I edit "Product2" Name as "Product1" without saving
    And I click "Save changes"
    Then I should see "Changing Page URLs" in the "UiWindow Title" element
    Then I click "Cancel" in modal window
    Then I should see following records in grid:
      | Product2 |
    And I click "Cancel"
    And I reload the page

  Scenario Outline: Canceling inline editing of Product fields in grid
    When I edit "Product2" <field> as "<newValue>" and cancel
    Then I should see following records in grid:
      | <oldValue> |
    Examples:
      | field            | newValue | oldValue     |
      | Name             | Product1 | Product2     |
      | Inventory Status | In stock | Out of Stock |
      | Status           | Enabled  | Disabled     |
      | Tax Code         | TaxCode1 | TaxCode2     |

  Scenario: Checking inline editing results on Product view page
    And I click View Product2 in grid
    Then I should see Product with:
      | Name             | Product2     |
      | Inventory Status | Out of Stock |
      | Tax Code         | TaxCode2     |
    And I should see "Disabled" in the "Entity Status" element

  Scenario: Inline editing Name field save by clicking on empty space
    And I go to Products / Products
    When I edit "Product2" Name as "Product1" without saving
    And I click on empty space
    Then I should see "Changing Page URLs" in the "UiWindow Title" element
    And I click "Apply" in modal window
    And I should see "Record has been successfully updated" flash message
    And I should see following records in grid:
      | Product1 |
    And I show column Tax Code in grid

  Scenario Outline: Inline editing of Product fields save by clicking on empty space
    When I edit "Product1" <field> as "<value>" with click on empty space
    Then I should see following records in grid:
      | <value> |
    Examples:
      | field            | value    |
      | Inventory Status | In Stock |
      | Status           | Enabled  |
      | Tax Code         | TaxCode1 |

  Scenario: Inline editing Name field using double click
    When I edit "Product1" Name as "Product2" by double click
    And I click "Save changes"
    Then I should see "Changing Page URLs" in the "UiWindow Title" element
    And I click "Apply" in modal window
    And I should see "Record has been successfully updated" flash message
    And I should see following records in grid:
      | Product2 |
    And I show column Tax Code in grid

  Scenario Outline: Inline editing of Product fields using double click
    When I edit "Product2" <field> as "<value>" by double click
    And I click "Save changes"
    Then I should see "Record has been successfully updated" flash message
    Then I should see following records in grid:
      | <value> |
    Examples:
      | field            | value        |
      | Inventory Status | Out of Stock |
      | Status           | Disabled     |
      | Status           | Enabled      |
      | Tax Code         | TaxCode2     |

  Scenario: Attempting to save empty value
    And I edit "Product2" Name as "" without saving
    And I click "Save changes"
    Then I should see "This value should not be blank."
    Then I click "Cancel"

  Scenario: Check that slug redirect is created with dialog
    Given I proceed as the User
    When I am on "/product2"
    Then I should see "Product2"
    When I am on "/product3"
    Then I should see "404 Not Found"

    Then I proceed as the Admin
    And I go to Products / Products
    When I edit "Product2" Name as "Product3" without saving
    And I click "Save changes"
    Then I should see "Changing Page URLs" in the "UiWindow Title" element
    Then I click "Apply" in modal window
    Then I should see "Record has been successfully updated" flash message
    Then I should see following records in grid:
      | Product3 |

    Then I proceed as the User
    When I am on "/product2"
    Then I should see "Product3"
    When I am on "/product3"
    Then I should see "Product3"

  Scenario: Check that slug redirect isn't created with dialog
    Given I proceed as the Admin
    And I go to Products / Products
    When I edit "Product3" Name as "Product4" without saving
    And I click "Save changes"
    Then I should see "Changing Page URLs" in the "UiWindow Title" element
    Then I uncheck "Create 301 Redirect from old to new URLs"
    Then I click "Apply" in modal window
    Then I should see "Record has been successfully updated" flash message
    Then I should see following records in grid:
      | Product4 |

    Then I proceed as the User
    When I am on "/product3"
    Then I should see "404 Not Found"
    When I am on "/product4"
    Then I should see "Product4"

  Scenario: Check that slug redirect is created with system option Always
    Given I proceed as the Admin
    When I go to System / Configuration
    And I follow "System Configuration/Websites/Routing" on configuration sidebar
    And uncheck "Use default" for "Create Redirects" field
    Then I fill in "Create Redirects" with "Always"
    And I save form
    Then I should see "Configuration saved" flash message

    And I go to Products / Products
    When I edit "Product4" Name as "Product5" without saving
    And I click "Save changes"
    Then I should not see "Changing Page URLs"
    Then I should see "Record has been successfully updated" flash message
    Then I should see following records in grid:
      | Product5 |

    Then I proceed as the User
    When I am on "/product4"
    Then I should see "Product5"
    When I am on "/product5"
    Then I should see "Product5"

  Scenario: Check that slug redirect isn't created with system option Never
    Given I proceed as the Admin
    When I go to System / Configuration
    And I follow "System Configuration/Websites/Routing" on configuration sidebar
    Then I fill in "Create Redirects" with "Never"
    And I save form
    Then I should see "Configuration saved" flash message

    And I go to Products / Products
    When I edit "Product5" Name as "Product6" without saving
    And I click "Save changes"
    Then I should not see "Changing Page URLs"
    Then I should see "Record has been successfully updated" flash message
    Then I should see following records in grid:
      | Product6 |

    Then I proceed as the User
    When I am on "/product5"
    Then I should see "404 Not Found"
    When I am on "/product6"
    Then I should see "Product6"

    Then I proceed as the Admin
    When I go to System / Configuration
    And I follow "System Configuration/Websites/Routing" on configuration sidebar
    Then I fill in "Create Redirects" with "Ask"
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Check that two products with the same name create different redirect slugs
    Given I go to Products / Products
    When I edit "Control Product" Name as "Product10" without saving
    And I click "Save changes"
    Then I should see "Changing Page URLs" in the "UiWindow Title" element
    Then I click "Apply" in modal window
    Then I should see "Record has been successfully updated" flash message
    Then I should see following records in grid:
      | Product10 |
    When I edit "Product6" Name as "Product10" without saving
    And I click "Save changes"
    Then I should see "Changing Page URLs" in the "UiWindow Title" element
    Then I click "Apply" in modal window
    Then I should see "Record has been successfully updated" flash message
    Then I should see following records in grid:
      | Product10 |

    Then I proceed as the User
    When I am on "/product10"
    Then I should see "CONTROL1"
    When I am on "/product10-1"
    Then I should see "SKU1"
