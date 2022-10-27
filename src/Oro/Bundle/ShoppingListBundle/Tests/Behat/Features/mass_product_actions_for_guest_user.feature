@fixture-OroProductBundle:Products_view_page_templates.yml
Feature: Mass Product Actions for Guest user
  In order to add several products in shopping list
  As a Guest
  I should be able to select and add several products to shopping list

  Scenario: Guest Shopping List feature is disabled by default and mass actions are disabled for Guest user
    Given sessions active:
      | Admin | first_session  |
      | Guest | second_session |
    And I proceed as the Admin
    And I login as administrator
    When I go to System/ Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    Then "Shopping List Config" must contains values:
      | Enable Guest Shopping List | false |

    When I proceed as the Guest
    And I go to homepage
    When I type "rtsh_m" in "search"
    And I click "Search Button"
    Then I should not see mass action checkbox in row with rtsh_m content for "Product Frontend Grid"
    When I click "Catalog Switcher Toggle"
    And I click "Gallery View"
    Then I should not see mass action checkbox in row with rtsh_m content for "Product Frontend Grid"
    When I click "Catalog Switcher Toggle"
    And I click "No Image View"
    Then I should not see mass action checkbox in row with rtsh_m content for "Product Frontend Grid"

  Scenario: Mass actions are enabled for Guest when Guest Shopping List feature is enabled
    Given I proceed as the Admin
    When uncheck "Use default" for "Enable Guest Shopping List" field
    And I fill form with:
      | Enable Guest Shopping List | true |
    When I click "Save settings"
    Then I should see "Configuration saved" flash message
    And "Shopping List Config" must contains values:
      | Enable Guest Shopping List | true |

    When I proceed as the Guest
    And I go to homepage
    When I type "rtsh_m" in "search"
    And I click "Search Button"
    Then I should see mass action checkbox in row with rtsh_m content for "Product Frontend Grid"
    When I click "Catalog Switcher Toggle"
    And I click "Gallery View"
    Then I should see mass action checkbox in row with rtsh_m content for "Product Frontend Grid"
    When I click "Catalog Switcher Toggle"
    And I click "List View"
    Then I should see mass action checkbox in row with rtsh_m content for "Product Frontend Grid"

  Scenario: Guest should be able to add products with help of mass actions
    Given I proceed as the Guest
    When I type "rtsh_m" in "search"
    And I click "Search Button"
    And I check rtsh_m record in "Product Frontend Grid" grid
    And I fill line item with "rtsh_m" in frontend product grid:
      | Quantity | 3    |
      | Unit     | item |
    And I scroll to top
    And I click "Add to current Shopping List" in "ProductFrontendMassPanelInBottomSticky" element
    And I open shopping list widget
    And I should see "1 Item | $21.00" in the "Shopping List Widget" element
    And I click "View List"
    Then I should see following grid:
      | SKU    | Qty Update All |
      | rtsh_m | 3 item         |
