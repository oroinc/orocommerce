@regression
@ticket-BB-19643
@fixture-OroShoppingListBundle:MyShoppingListsFixture.yml

Feature: My Shopping List Configuration
  In order to allow customers to select goods they want to purchase
  As an Admin
  I want to enable "Shopping Lists" page and give the ability to use new layout for shopping list view and edit pages

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Check configuration options
    When I proceed as the Admin
    And I login as administrator
    And I go to System/ Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    Then the "Enable \"Shopping Lists\" page in \"My Account\"" checkbox should be unchecked
    And the "Use new layout for shopping list view and edit pages" checkbox should be unchecked

  Scenario: Enable My Shopping Lists page
    When I fill "Shopping List Configuration Form" with:
      | Enable "Shopping Lists" page in "My Account" Use default | false |
      | Enable "Shopping Lists" page in "My Account"             | true  |
    And click "Save settings"
    Then I should see "Configuration saved" flash message
    And the "Enable \"Shopping Lists\" page in \"My Account\"" checkbox should be checked
    And the "Use new layout for shopping list view and edit pages" checkbox should be unchecked

  Scenario: Check Shopping Lists widget
    Given I operate as the Buyer
    When I login as AmandaRCole@example.org buyer
    And I open shopping list widget
    Then I should see "Shopping List 1" on shopping list widget
    And I should see "Shopping List 2" on shopping list widget
    And I should see "Shopping List 3" on shopping list widget

  Scenario: Check Shopping List page
    When I open page with shopping list "Shopping List 3"
    Then I should see "Shopping List 1"
    And I should see "Shopping List 2"
    And I should see "Shopping List 3"
    And I should see "Create Order"

  Scenario: Check My Shopping Lists page
    When I follow "Account"
    Then I should see "My Shopping Lists"
    When I click "My Shopping Lists"
    Then Page title equals to "My Shopping Lists - My Account"
    And I should see following grid:
      | Name            | Subtotal  | Items | Default |
      | Shopping List 3 | $8,818.00 | 32    | Yes     |
      | Shopping List 1 | $1,581.00 | 3     | No      |
    And records in grid should be 2
    And I should not see "Shopping List 2"
    And I should see following actions for Shopping List 3 in grid:
      | View |
      | Edit |

  Scenario: Enable New Layout for shopping list view and edit pages
    When I proceed as the Admin
    And uncheck "Use default" for "Use new layout for shopping list view and edit pages" field
    And I check "Use new layout for shopping list view and edit pages"
    And click "Save settings"
    Then I should see "Configuration saved" flash message
    And the "Use new layout for shopping list view and edit pages" checkbox should be checked

  Scenario: Check Shopping List edit page from shopping list widget
    When I proceed as the Buyer
    And I open page with shopping list "Shopping List 1"
    Then Page title equals to "Shopping List 1 - My Shopping Lists - My Account"
    And I should see "Shopping List 1"
    And I should see "Assigned To: Amanda Cole"
    And I should see "3 total records"
    And I should see following grid:
      | SKU  | Item                    |          | QtyUpdate All | Price  | Subtotal |
      | CC36 | Product 36 Note 36 text | In Stock | 17 pc         | $31.00 | $527.00  |
      | CC37 | Product 37 Note 37 text | In Stock | 17 pc         | $31.00 | $527.00  |
      | CC38 | Product 38 Note 38 text | In Stock | 17 pc         | $31.00 | $527.00  |
    And I should see "Summary 3 Items"
    And I should see "Subtotal $1,581.00"
    And I should see "Total $1,581.00"
    And I should see "Create Order"

  Scenario: Check Shopping List edit from My Shopping lists page
    When I follow "Account"
    And I click "My Shopping Lists"
    And I filter Name as contains "List 1"
    And I click Edit "Shopping List 1" in grid
    Then Page title equals to "Shopping List 1 - My Shopping Lists - My Account"
    And I should see "Shopping List 1"
    And I should see "Assigned To: Amanda Cole"
    And I should see "3 total records"
    And I should see following grid:
      | SKU  | Item                    |          | QtyUpdate All | Price  | Subtotal |
      | CC36 | Product 36 Note 36 text | In Stock | 17 pc         | $31.00 | $527.00  |
      | CC37 | Product 37 Note 37 text | In Stock | 17 pc         | $31.00 | $527.00  |
      | CC38 | Product 38 Note 38 text | In Stock | 17 pc         | $31.00 | $527.00  |
    And I should see "Summary 3 Items"
    And I should see "Subtotal $1,581.00"
    And I should see "Total $1,581.00"
    And I should see "Create Order"
    And I should see following actions for CC36 in grid:
      | Delete |
    When I click "Shopping List Actions"
    Then I should not see "Edit"

  Scenario: Change EDIT permission
    When I follow "Account"
    And click "Users"
    And click "Roles"
    And click edit "Administrator" in grid
    And select following permissions:
      | Shopping List | Edit:None |
    And I scroll to top
    And click "Save"
    Then should see "Customer User Role has been saved" flash message
    And should see "Edit - None"
    And click "Sign Out"

  Scenario: Check shopping list view page from shopping list widget
    When I login as AmandaRCole@example.org buyer
    And I open page with shopping list "Shopping List 1"
    Then Page title equals to "Shopping List 1 - My Shopping Lists - My Account"
    And I should see "Shopping List 1"
    And I should see "Assigned To: Amanda Cole"
    And I should see "3 total records"
    And I should see following grid:
      | SKU  | Item                    |          | Qty | Price  | Subtotal |
      | CC36 | Product 36 Note 36 text | In Stock | 17  | $31.00 | $527.00  |
      | CC37 | Product 37 Note 37 text | In Stock | 17  | $31.00 | $527.00  |
      | CC38 | Product 38 Note 38 text | In Stock | 17  | $31.00 | $527.00  |
    And I should see "Summary 3 Items"
    And I should see "Subtotal $1,581.00"
    And I should see "Total $1,581.00"
    And I should see "Create Order"
    And I should not see following actions for CC36 in grid:
      | Delete |
    When I click "Shopping List Actions"
    Then I should not see "Edit"

  Scenario: Disable My Shopping Lists page
    When I proceed as the Admin
    And I fill "Shopping List Configuration Form" with:
      | Enable "Shopping Lists" page in "My Account"             | false |
      | Enable "Shopping Lists" page in "My Account" Use default | true  |
    And I uncheck "Use new layout for shopping list view and edit pages"
    And check "Use default" for "Use new layout for shopping list view and edit pages" field
    And click "Save settings"
    Then I should see "Configuration saved" flash message
    And the "Enable \"Shopping Lists\" page in \"My Account\"" checkbox should be unchecked
    And the "Use new layout for shopping list view and edit pages" checkbox should be unchecked

  Scenario: Check My Shopping Lists pages when configuration is disabled
    When I proceed as the Buyer
    And I reload the page
    Then I should see "404 Not Found"
    When I follow "Account"
    Then I should not see "My Shopping Lists"
