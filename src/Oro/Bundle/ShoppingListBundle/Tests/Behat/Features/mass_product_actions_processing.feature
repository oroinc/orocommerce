@fixture-OroWebCatalogBundle:empty_web_catalog.yml
@fixture-OroCatalogBundle:all_products_page.yml

Feature: Mass Product Actions processing

  In order to add multiple products to a shopping list
  As a Customer User or Guest
  I want to have ability to select multiple products and add them to a shopping list

  Scenario: Create different window session
    Given sessions active:
      | Admin  |first_session |
      | User   |second_session|

  Scenario: Administrator enables all products feature
    Given I proceed as the Admin
    And login as administrator
    And I go to System/ Configuration
    And follow "Commerce/Catalog/Special Pages" on configuration sidebar
    And uncheck "Use default" for "Enable all products page" field
    And I check "Enable all products page"
    And save form
    Then I should see "Configuration saved" flash message

  Scenario: I add link to all products page to the main menu
    Given I proceed as the Admin
    When I go to System/ Frontend Menus
    And I click view "commerce_main_menu" in grid
    And I click "Create Menu Item"
    And I fill "Commerce Menu Form" with:
      | Title       | All Products         |
      | Target Type | URI                  |
      | URI         | /catalog/allproducts |
    And save form
    Then I should see "Menu item saved successfully" flash message

  Scenario: "Create New shopping list" mass action on the category list view
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "All Products"
    And I should see mass action checkbox in row with PSKU1 content for "Product Frontend Grid"
    When I click "Catalog Switcher Toggle"
    And I click "Gallery View"
    Then I should see mass action checkbox in row with PSKU1 content for "Product Frontend Grid"
    When I click "Catalog Switcher Toggle"
    And I click "No Image View"
    And I check PSKU1 record in "Product Frontend Grid" grid
    And I fill line item with "PSKU1" in frontend product grid:
      | Quantity | 10   |
      | Unit     | set  |
    And I check PSKU2 record in "Product Frontend Grid" grid
    And I fill line item with "PSKU2" in frontend product grid:
      | Quantity | 15   |
      | Unit     | item |
    And I click "Create New Shopping List" in "ProductFrontendMassPanelInBottomSticky" element
    Then should see an "Create New Shopping List popup" element
    And type "Shopping List of Amanda" in "Shopping List Name"
    And click "Create and Add"
    Then should see '2 products were added' flash message
    And click on "Flash Message Close Button"
    When I hover on "Shopping Cart"
    And I click "Shopping List of Amanda" on shopping list widget
    Then I should see following grid:
      | SKU   | Qty Update All |
      | PSKU1 | 10 set         |
      | PSKU2 | 15 item        |

  Scenario: "Add to Shopping List of Amanda" mass action on the category list view
    Given I proceed as the User
    And I click "All Products"
    And I click "Search Button"
    And I check PSKU3 record in "Product Frontend Grid" grid
    And I fill line item with "PSKU3" in frontend product grid:
      | Quantity | 7    |
      | Unit     | set  |
    And I click "Header"
    And I click "Add to Shopping List of Amanda" in "ProductFrontendMassPanelInBottomSticky" element
    Then I should see "1 product was added" flash message
    When I hover on "Shopping Cart"
    And I click "Shopping List of Amanda" on shopping list widget
    Then I should see following grid:
      | SKU   | Qty Update All |
      | PSKU1 | 10 set         |
      | PSKU2 | 15 item        |
      | PSKU3 | 7 set          |

  Scenario: Should be possible to check mass action checkbox on All products page
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    When I click "All Products"
    And I check PSKU2 record in "Product Frontend Grid" grid

  Scenario: Show warning message when products are selected and trying to refresh the page
    When I click "Catalog Switcher Toggle"
    And I click "List View"
    And I accept alert
    Then I should see PSKU2 unchecked record in "Product Frontend Grid"

  Scenario: Enabled Shopping lists for guest user
    And I proceed as the Admin
    When I go to System/ Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    Then "Shopping List Config" must contains values:
      | Enable Guest Shopping List | false |
    When uncheck "Use default" for "Enable Guest Shopping List" field
    And I fill form with:
      | Enable Guest Shopping List | true |
    And I save setting
    When I click "Save settings"
    Then I should see "Configuration saved" flash message
    And "Shopping List Config" must contains values:
      | Enable Guest Shopping List | true |

  Scenario: Mass actions should contain only available for user shopping lists
    # Customer User of another Customer, shouldn't see other Customer lists
    When I signed in as MarleneSBradley@example.com on the store frontend
    And I type "PSKU3" in "search"
    And I click "Search Button"
    And I check PSKU3 record in "Product Frontend Grid" grid
    Then I should not see "ProductFrontendMassOpenInDropdown"
    Then I should not see "Shopping List of Amanda" in the "ProductFrontendMassPanelInBottomSticky" element
    And I uncheck PSKU3 record in "Product Frontend Grid" grid
    # Guest, shouldn't see others lists
    When click "Sign Out"
    And I type "PSKU3" in "search"
    And I click "Search Button"
    And I check PSKU3 record in "Product Frontend Grid" grid
    Then I should not see "ProductFrontendMassOpenInDropdown"
    And I should not see "Shopping List of Amanda" in the "ProductFrontendMassPanelInBottomSticky" element
    And I should see "Add to current Shopping list" in the "ProductFrontendMassPanelInBottomSticky" element
