@fixture-OroShoppingListBundle:ShoppingListFixture.yml
@ticket-BB-20374

Feature: Show default Shopping List on top of Choose Action drop-down

  There is a case when a user has a lot of shopping lists and
  it is not convenient to quickly find a default one for adding selected products in category pages on the Storefront.

  Scenario: Product mass actions search is hidden
    Given I signed in as AmandaRCole@example.org on the store frontend
    When type "AA1" in "search"
    And I click "Search Button"
    And I check AA1 record in "Product Frontend Grid" grid
    And I click "ProductFrontendMassActionButton"
    Then I should see "ProductFrontendGridMassActionDefaultItem" element with text "Add to Shopping List 5" inside "ProductFrontendGridMassActionMenu" element
    And I should not see "ProductFrontendGridMassActionSearch" element inside "ProductFrontendGridMassActionMenu" element
    And I click "ProductFrontendMassActionButton"

  Scenario: Verifye that a "Default Shopping List" action is always on the top of the actions list
    Given I click "Create New Shopping List" link from mass action dropdown in "Product Frontend Grid"
    Then should see an "Create New Shopping List popup" element
    When type "Shopping List 6" in "Shopping List Name"
    And click "Create and Add"
    Then should see 'Shopping list "Shopping List 6" was created successfully' flash message
    And click on "Flash Message Close Button"
    And click on "Flash Message Close Button"
    When I check AA1 record in "Product Frontend Grid" grid
    And I click "ProductFrontendMassActionButton"
    Then I should see "ProductFrontendGridMassActionDefaultItem" element with text "Add to Shopping List 6" inside "ProductFrontendGridMassActionMenu" element
    And I click "ProductFrontendMassActionButton"

    When I check AA1 record in "Product Frontend Grid" grid
    And I click "Create New Shopping List" link from mass action dropdown in "Product Frontend Grid"
    Then should see an "Create New Shopping List popup" element
    And type "Shopping List 7" in "Shopping List Name"
    And click "Create and Add"
    Then should see 'Shopping list "Shopping List 7" was created successfully' flash message
    And click on "Flash Message Close Button"
    And click on "Flash Message Close Button"
    When I check AA1 record in "Product Frontend Grid" grid
    And I click "ProductFrontendMassActionButton"
    Then I should see "ProductFrontendGridMassActionDefaultItem" element with text "Add to Shopping List 7" inside "ProductFrontendGridMassActionMenu" element
    And I click "ProductFrontendMassActionButton"

    When I check AA1 record in "Product Frontend Grid" grid
    And I click "Create New Shopping List" link from mass action dropdown in "Product Frontend Grid"
    Then should see an "Create New Shopping List popup" element
    And type "Shopping List 8" in "Shopping List Name"
    And click "Create and Add"
    Then should see 'Shopping list "Shopping List 8" was created successfully' flash message
    And click on "Flash Message Close Button"
    And click on "Flash Message Close Button"

  Scenario: Product mass actions search is visible if number of actions more then 5
    Given I check AA1 record in "Product Frontend Grid" grid
    When I click "ProductFrontendMassActionButton"
    Then I should see "ProductFrontendGridMassActionDefaultItem" element with text "Add to Shopping List 8" inside "ProductFrontendGridMassActionMenu" element
    And I should see "ProductFrontendGridMassActionSearch" element inside "ProductFrontendGridMassActionMenu" element
    When I type "Shopping List 8" in "ProductFrontendGridMassActionSearch"
    Then I should see "Highlight Container" element inside "ProductFrontendGridMassActionMenu" element
    And I should see "Highlighted Text" element with text "Shopping List 8" inside "ProductFrontendGridMassActionMenu" element
    When I click "Shopping List Quick Search Clear"
    Then I should not see "Highlight Container" element inside "ShoppingListButtonGroup" element
