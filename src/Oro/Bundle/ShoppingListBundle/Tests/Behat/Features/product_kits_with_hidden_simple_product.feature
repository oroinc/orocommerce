@ticket-BB-23093
@fixture-OroShoppingListBundle:product_kits_add_from_in_shopping_list_dialog.yml

Feature: Product kits with hidden simple product

  Scenario: Feature Background
    Given There is USD currency in the system configuration
    And sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Hide a product
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products / Products
    And I filter SKU as is equal to "simple-product-03"
    When I click "View" on row "simple-product-03" in grid
    And click "More actions"
    And click "Manage Visibility"
    And I select "Hidden" from "Visibility to All"
    And I save and close form
    Then I should see "Product visibility has been saved" flash message

  Scenario: Check the visibility in "In Shopping List" dialog
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I type "product-kit-1" in "search"
    And I click "Search Button"
    And I click "View Details" for "Product Kit 1" product
    And I click "In Shopping List"
    Then I should see "Configure and Add to Shopping List" in the "Product Kit In Shopping List Dialog Action Panel" element
    And I should see an "Product 2 Link" element
    And I should not see an "Product 3 Link" element
    When I click "Configure and Add to Shopping List" in "Product Kit In Shopping List Dialog Action Panel" element
    Then I should see an "Configure Popup Product 2 Link" element
    And I should not see an "Configure Popup Product 3 Link" element
    And I close ui dialog

  Scenario: Check the visibility on Shopping List Page
    When Buyer is on "Product Kit Shopping List" shopping list
    Then I should see an "Product 2 Link" element
    And I should not see an "Product 3 Link" element
    When I click "Shopping List Actions"
    And click "Edit"
    Then I should see an "Product 2 Link" element
    And I should not see an "Product 3 Link" element
    When I click "Row 1 Edit Line Item"
    Then I should see "Product Kit Dialog" with elements:
      | Title | Editing "Product Kit 1" in "Product Kit Shopping List" |
    And I should see an "Product 2 Link" element
    And I should not see an "Product 3 Link" element
