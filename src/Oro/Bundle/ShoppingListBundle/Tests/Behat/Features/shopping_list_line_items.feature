@regression
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

  Scenario: Exchange units of Line items
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And Buyer is on "Shopping List 5" shopping list
    When I click "Shopping List Actions"
    And I click "Edit"
    Then I should see following grid:
      | SKU                                                                     | Qty Update All |
      | AA1                                                                     | 1 item ( set ) |
      | This item can't be added to checkout because the price is not available |                |
      | AA1                                                                     | 2 ( item ) set |
      | This item can't be added to checkout because the price is not available |                |
    When I click on "Shopping List Line Item 2 Quantity"
    And I fill "Shopping List Line Item Form" with:
      | Quantity | 1   |
      | Unit     | set |
# Here is a little tricky "Shopping List Line Item Form" can only be the first form and will not work if you are
# going to set values for the 2nd form when there are two more of forms.
    And I click on "Shopping List Line Item 1 Quantity"
    And I fill "Shopping List Line Item Form" with:
      | Quantity | 3    |
      | Unit     | item |
    And I click "Update All"
    Then I should see following grid:
      | SKU                                                                     | Qty Update All |
      | AA1                                                                     | 1 item ( set ) |
      | This item can't be added to checkout because the price is not available |                |
      | AA1                                                                     | 3 ( item ) set |
      | This item can't be added to checkout because the price is not available |                |

  Scenario: Merge Line items
    When I click on "Shopping List Line Item 1 Quantity"
    And I fill "Shopping List Line Item Form" with:
      | Quantity | 2    |
      | Unit     | item |
    And I click on "Shopping List Line Item 2 Quantity"
    And I type "4" in "Shopping List Line Item 2 Quantity Input"
    When I click on "Shopping List Line Item 1 Save Changes Button"
    Then I should see following grid:
      | SKU                                                                     | Qty Update All |
      | AA1                                                                     | 5 ( item ) set |
      | This item can't be added to checkout because the price is not available |                |

  Scenario: Add an additional product unit to the product
    When I proceed as the Admin
    And login as administrator
    And I go to Products / Products
    Then click "Edit" on row "AA1" in grid
    When set Additional Unit with:
      | Unit | Precision | Rate |
      | each | 1         | 1    |
    And I check "ProductAdditionalSellFieldSecondRow" element
    And save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check inline editing for simple products with three available units per product
    When I proceed as the Buyer
    And I reload the page
    Then I should see following grid:
      | SKU                                                                     | Qty Update All |
      | AA1                                                                     | 5 item         |
      | This item can't be added to checkout because the price is not available |                |
    When I click on "Shopping List Line Item 1 Quantity"
    And I fill "Shopping List Line Item Form" with:
      | Quantity | 7    |
      | Unit     | each |
    And I save changes for "Shopping List Line Item 1" row
    Then I should see following grid:
      | SKU                                                                     | Qty Update All |
      | AA1                                                                     | 7 each         |
      | This item can't be added to checkout because the price is not available |                |

    And I click on "Decrement Product Quantity In Row 1"
    And I fill "Shopping List Line Item Form" with:
      | Quantity | 6    |
      | Unit     | each |
    And I save changes for "Shopping List Line Item 1" row

    And I click on "Increment Product Quantity In Row 1"
    And I fill "Shopping List Line Item Form" with:
      | Quantity | 7    |
      | Unit     | each |
    And I save changes for "Shopping List Line Item 1" row

  Scenario: Enable "Plain" view mode for product quantity
    When I proceed as the Admin
    And I go to System / Theme Configurations
    And I click "Edit" on row "Golden Carbon" in grid
    And I fill "Theme Configuration Form" with:
      | Quantity Inputs in Shopping List | Plain |
    And I save and close form
    Then I should see "Theme Configuration" flash message

  Scenario: Check that standard quantity input field without stepper buttons
    When I proceed as the Buyer
    And I reload the page
    Then I should not see "Decrement Product Quantity In Row 1" element inside "Frontend Shopping List Edit Grid" element
    And I should not see "Increment Product Quantity In Row 1" element inside "Frontend Shopping List Edit Grid" element

  Scenario: Check edit permission for line item
    When I proceed as the Admin
    And I go to Customers / Customer User Roles
    And I click edit "Buyer" in grid
    And select following permissions:
      | Shopping List Line Item | Edit:None |
    And I save form
    Then I should see "Customer User Role has been saved" flash message
    When I proceed as the Buyer
    And I reload the page
    And I click on "Shopping List Line Item 1 Quantity"
    Then I should not see an "Shopping List Line Item Form" element

   # Because the "delete" action checks acl resource "oro_shopping_list_frontend_update" instead of "oro_shopping_list_line_item_frontend_delete"
  Scenario: Check delete permission for line item
    When I proceed as the Admin
    And select following permissions:
      | Shopping List Line Item | Edit:User (Own) | Delete:None |
    And I save form
    Then I should see "Customer User Role has been saved" flash message
    When I proceed as the Buyer
    And I reload the page
    Then I should see only following actions for row #1 on grid:
      | Save For Later |
      | Add a note     |
      | Delete         |
