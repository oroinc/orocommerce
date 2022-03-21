@fixture-OroProductBundle:products_grid_frontend.yml
@ticket-BB-20374

Feature: Show default Shopping List on top of Choose Action drop-down

  There is a case when a user has a lot of shopping lists and
  it is not convenient to quickly find a default one for adding selected products in category pages on the Storefront.

  Scenario: Product mass actions search is hidden (desktop)
    Given I signed in as AmandaRCole@example.org on the store frontend
    When open page number test of frontend product grid
    And I sort frontend grid "Product Frontend Grid" by "Price (Low to High)"
    And I should see "PSKU1"
    And I check PSKU1 record in "Product Frontend Grid" grid
    And I click "ProductFrontendMassActionButton"
    Then I should not see "ProductFrontendGridMassActionSearch" element inside "ProductFrontendGridMassActionMenu" element
    And I click "ProductFrontendMassActionButton"
    And I click "Create New Shopping List" link from mass action dropdown in "Product Frontend Grid"
    Then should see an "Create New Shopping List popup" element
    And type "Shopping List 1" in "Shopping List Name"
    And click "Create and Add"
    Then should see 'Shopping list "Shopping List 1" was created successfully' flash message
    And click on "Flash Message Close Button"
    And click on "Flash Message Close Button"
    When I check PSKU1 record in "Product Frontend Grid" grid
    And I click "ProductFrontendMassActionButton"
    Then I should not see "ProductFrontendGridMassActionDefaultItem" element with text "Add to Shopping List 1" inside "ProductFrontendGridMassActionMenu" element
    Then I should see "ProductFrontendGridMassActionItem" element with text "Add to Shopping List" inside "ProductFrontendGridMassActionMenu" element
    And I should not see "ProductFrontendGridMassActionSearch" element inside "ProductFrontendGridMassActionMenu" element
    And I click "ProductFrontendMassActionButton"

  Scenario: Verify that a "Default Shopping List" action is always on the top of the actions list
    Given I check PSKU1 record in "Product Frontend Grid" grid
    And I click "Create New Shopping List" link from mass action dropdown in "Product Frontend Grid"
    Then should see an "Create New Shopping List popup" element
    And type "Shopping List 2" in "Shopping List Name"
    And click "Create and Add"
    Then should see 'Shopping list "Shopping List 2" was created successfully' flash message
    And click on "Flash Message Close Button"
    And click on "Flash Message Close Button"
    When I check PSKU1 record in "Product Frontend Grid" grid
    And I click "ProductFrontendMassActionButton"
    Then I should see "ProductFrontendGridMassActionDefaultItem" element with text "Add to Shopping List 2" inside "ProductFrontendGridMassActionMenu" element
    And I click "ProductFrontendMassActionButton"
    Given I check PSKU1 record in "Product Frontend Grid" grid
    And I click "ProductFrontendMassActionButton"
    Then I should see "ProductFrontendGridMassActionDefaultItem" element with text "Add to Shopping List 2" inside "ProductFrontendGridMassActionMenu" element
    And I click "ProductFrontendMassActionButton"

    When I check PSKU1 record in "Product Frontend Grid" grid
    And I click "Create New Shopping List" link from mass action dropdown in "Product Frontend Grid"
    Then should see an "Create New Shopping List popup" element
    And type "Shopping List 3" in "Shopping List Name"
    And click "Create and Add"
    Then should see 'Shopping list "Shopping List 3" was created successfully' flash message
    And click on "Flash Message Close Button"
    And click on "Flash Message Close Button"
    When I check PSKU1 record in "Product Frontend Grid" grid
    And I click "ProductFrontendMassActionButton"
    Then I should see "ProductFrontendGridMassActionDefaultItem" element with text "Add to Shopping List 3" inside "ProductFrontendGridMassActionMenu" element
    And I click "ProductFrontendMassActionButton"

    When I check PSKU1 record in "Product Frontend Grid" grid
    And I click "Create New Shopping List" link from mass action dropdown in "Product Frontend Grid"
    Then should see an "Create New Shopping List popup" element
    And type "Shopping List 4" in "Shopping List Name"
    And click "Create and Add"
    Then should see 'Shopping list "Shopping List 4" was created successfully' flash message
    And click on "Flash Message Close Button"
    And click on "Flash Message Close Button"

    When I check PSKU1 record in "Product Frontend Grid" grid
    And I click "Create New Shopping List" link from mass action dropdown in "Product Frontend Grid"
    Then should see an "Create New Shopping List popup" element
    And type "Shopping List 5" in "Shopping List Name"
    And click "Create and Add"
    Then should see 'Shopping list "Shopping List 5" was created successfully' flash message
    And click on "Flash Message Close Button"
    And click on "Flash Message Close Button"

  Scenario: Product mass actions search is visible if number of actions more then 5
    Given I check PSKU1 record in "Product Frontend Grid" grid
    When I click "ProductFrontendMassActionButton"
    Then I should see "ProductFrontendGridMassActionDefaultItem" element with text "Add to Shopping List 5" inside "ProductFrontendGridMassActionMenu" element
    And I should see "ProductFrontendGridMassActionSearch" element inside "ProductFrontendGridMassActionMenu" element
    When I type "Shopping List 5" in "ProductFrontendGridMassActionSearch"
    Then I should see "Highlight Container" element inside "ProductFrontendGridMassActionMenu" element
    And I should see "Highlighted Text" element with text "Shopping List 5" inside "ProductFrontendGridMassActionMenu" element
    When I click "Shopping List Quick Search Clear"
    Then I should not see "Highlight Container" element inside "ShoppingListButtonGroup" element
