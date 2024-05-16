@regression
@ticket-BB-13239
@fixture-OroShoppingListBundle:check_the_shopping_list_using_different_customer_users.yml

Feature: Check shopping list subtotal using different customer users
  All subtotals should be cached and recalculated depending on the current user.

  Scenario: Feature Background
    Given sessions active:
      | Admin              | system_session |
      | CustomerHeadoffice | first_session  |
      | CustomerDepartment | second_session |
    Given I proceed as the Admin
    And I login as administrator

  Scenario: Sets option "Show All Lists In Shopping List Widgets"
    Given I go to System/Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Show All Lists in Shopping List Widgets" field
    And check "Show All Lists in Shopping List Widgets"
    When I save form
    Then I should see "Configuration saved" flash message

  Scenario: Change Administrator role's Shopping list view permission
    Given I go to Customers/Customer User Roles
    When I click Edit Administrator in grid
    And select following permissions:
      | Shopping List | View:Corporate (All Levels) |
    And save and close form
    Then I should see "Customer User Role has been saved" flash message

  Scenario Outline: Add product prices
    Given I go to Sales / Price Lists
    And click view "<PriceListName>" in grid
    When I click "Add Product Price"
    And fill "Add Product Price Form" with:
      | Product  | product_simple_1 |
      | Quantity | 1                |
      | Unit     | item             |
      | Price    | <Price>          |
    And click "Save"
    And click "Add Product Price"
    And fill "Add Product Price Form" with:
      | Product  | product_simple_2 |
      | Quantity | 1                |
      | Unit     | item             |
      | Price    | <Price>          |
    When I click "Save"
    Then I should see "Product Price has been added" flash message

    Examples:
      | PriceListName       | Price |
      | HeadofficePriceList | 25    |
      | DepartmentPriceList | 45    |

  Scenario: Create promotion
    Given I go to Marketing / Promotions / Promotions
    And click "Create Promotion"
    When I fill "Promotion Form" with:
      | Name               | Promotion30 |
      | Type               | Percent     |
      | Sort Order         | 1           |
      | Discount Value (%) | 10          |
      | Enabled            | true        |
    And press "Add" in "Items To Discount" section
    And check product_simple_1 record in "Add Products Popup" grid
    And check product_simple_2 record in "Add Products Popup" grid
    And click "Add" in "UiDialog ActionPanel" element
    And save form
    Then I should see "Promotion has been saved" flash message

  Scenario: Create Shopping List as department customer
    Given I proceed as the CustomerDepartment
    And I login as MarleneSBradley@example.com buyer
    And I am on "/product"
    And click "Add to Shopping List" for "Simple product 1" product
    And click "Add to Shopping List" for "Simple product 2" product
    And I scroll to top
    And I should see "Product has been added to" flash message and I close it

    When I open shopping list widget
    And I should see "2 items | $90.00"

    When I click "Shopping List" on shopping list widget
    Then should see "Subtotal $90.00"
    And should see "Discount -$9.00"
    And should see "Total $81.00"

  Scenario: View Shopping List from headoffice customer
    Given I proceed as the CustomerHeadoffice
    And I login as AmandaRCole@example.org buyer
    And I am on homepage

    When I open shopping list widget
    # $90.00 because cache for AmandaRCole user is not created.
    Then I should see "2 items | $90.00"

    When I click "Shopping List" on shopping list widget
    Then I should see "Subtotal $50.00"
    And should see "Discount -$5.00"
    And should see "Total $45.00"

    When I open shopping list widget
    # $50.00 because cache for AmandaRCole created.
    Then I should see "2 items | $50.00"

  Scenario: Invalidate customer department shopping list cache
    Given I proceed as the CustomerDepartment
    When I click Delete product_simple_1 in grid
    Then I should see "Are you sure you want to delete this product?"
    When I click "Delete" in modal window
    Then I should see 'The "Simple product 1' flash message

  Scenario: Check subtotal in shopping list widget from headoffice customer
    Given I proceed as the CustomerHeadoffice
    And I am on homepage
    When I open shopping list widget
    # $45.00 because cache for AmandaRCole user is not valid.
    Then I should see "1 item | $45.00"

    When I click "Shopping List" on shopping list widget
    Then I should see "Subtotal $25.00"
    And should see "Discount -$2.50"
    And should see "Total $22.50"
    When I open shopping list widget
    # 25.00 because cache for AmandaRCole created.
    Then I should see "1 item | $25.00"

  Scenario: Invalidate customer department shopping list cache
    Given I proceed as the Admin
    And go to Sales / Price Lists
    And click view DepartmentPriceList in grid
    And click edit "product_simple_2" in grid
    When I fill "Update Product Price Form" with:
      | Price | 40 |
    And click "Save"
    Then I should see "Product Price has been added" flash message

  Scenario: Check subtotal in shopping list widget from headoffice customer
    Given I proceed as the CustomerHeadoffice
    And I am on homepage
    When I open shopping list widget
    # $45.00 because cache for AmandaRCole and MarleneSBradley users is not valid.
    #
    # This value is an outdated value from the cache, but it is correct, although it misleads the user.
    # Since no user has an up-to-date cache and AmandaRCole cannot generate a cache for MarleneSBradley since
    # all discounts are unknown, etc., the cache will be filled with the old one.
    # To update it, go to the shopping list view or edit page.
    Then I should see "1 item | $45.00"

  Scenario: Update cache from CustomerDepartment
    Given I proceed as the CustomerDepartment
    And I click "Account Dropdown"
    When I click on "Shopping Lists"
    Then I should see following grid:
      | Name          | Subtotal | Items |
      | Shopping List | $40.00   | 1     |
    And records in grid should be 1

    When I open shopping list widget
    # $45.00 because cache for AmandaRCole user is not valid.
    Then I should see "1 item | $40.00"

    When I click "Shopping List" on shopping list widget
    Then I should see "Subtotal $40.00"
    And should see "Discount -$4.00"
    And should see "Total $36.00"

  Scenario: Check subtotal in shopping list widget from headoffice customer
    Given I proceed as the CustomerHeadoffice
    And I am on homepage

    When I open shopping list widget
    # $40.00 because cache for AmandaRCole user is not valid.
    Then I should see "1 item | $40.00"

    When I click "Shopping List" on shopping list widget
    Then I should see "Subtotal $25.00"
    And should see "Discount -$2.50"
    And should see "Total $22.50"
    When I open shopping list widget
    # $25.00 because cache for AmandaRCole created.
    Then I should see "1 item | $25.00"
