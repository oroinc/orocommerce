@fixture-OroProductBundle:Products_view_page_templates.yml
Feature: Mass Product Actions
  In order to add several products in shopping list
  As a Buyer or a Guest
  I should be able to select and add several products to shopping list

  Scenario: Shopping lists are enabled for guest user
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator
    When I go to System/ Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    Then "Shopping List Config" must contains values:
      | Enable Guest Shopping List | false |
    When uncheck "Use default" for "Enable guest shopping list" field
    And I fill form with:
      | Enable Guest Shopping List | true  |
    And I save setting
    When I click "Save settings"
    Then I should see "Configuration saved" flash message
    And "Shopping List Config" must contains values:
      | Enable Guest Shopping List | true |

  Scenario: Mass actions should contain only available for user shopping lists
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on homepage
    When I type "rtsh_m" in "search"
    And I click "Search Button"
    And I check rtsh_m record in "Product Frontend Grid" grid
    And I type "3" in "LineItemQuantity"
    And fill "FrontendLineItemForm" with:
        | Unit | item |
    And I scroll to top
    And I click "Create New Shopping List" link from mass action dropdown in "Product Frontend Grid"
    Then should see an "Create New Shopping List popup" element
    And type "Shopping List of Amanda" in "Shopping List Name"
    And click "Create and Add"
    Then should see 'Shopping list "Shopping List of Amanda" was created successfully' flash message
    # Customer User of another Customer, shouldn't see other Customer lists
    When I signed in as MarleneSBradley@example.com on the store frontend
    And I type "rtsh_m" in "search"
    And I click "Search Button"
    And I check rtsh_m record in "Product Frontend Grid" grid
    And click "Product Frontend Mass Action Button"
    Then I should not see "Shopping List of Amanda"
    And I uncheck rtsh_m record in "Product Frontend Grid" grid
    # Guest, shouldn't see others lists
    When click "Sign Out"
    And I type "rtsh_m" in "search"
    And I click "Search Button"
    And I check rtsh_m record in "Product Frontend Grid" grid
    And click "Product Frontend Mass Action Button"
    Then I should not see "Shopping List of Amanda"
    And I should see "Add to current Shopping List"
