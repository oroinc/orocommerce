@fixture-OroShoppingListBundle:ShoppingListFixture.yml
@ticket-BB-20374

Feature: Show default Shopping List on top of Choose Action drop-down

  There is a case when a user has a lot of shopping lists and
  it is not convenient to quickly find a default one for adding selected products in category pages on the Storefront.

  Scenario: Product mass actions search is hidden (desktop)
    Given I signed in as AmandaRCole@example.org on the store frontend
    When type "AA1" in "search"
    And I click "Search Button"
    And I check AA1 record in "Product Frontend Grid" grid
    And I click "ProductFrontendMassActionButton"
    Then I should see "ProductFrontendGridMassActionDefaultItem" element with text "Add to Shopping List 5" inside "ProductFrontendGridMassActionMenu" element
    And I should not see "ProductFrontendGridMassActionSearch" element inside "ProductFrontendGridMassActionMenu" element
    And I click "ProductFrontendMassActionButton"

  Scenario: Product mass actions in bottom sticky panel (tablet)
    Given I set window size to 992x1024
    Then I should not see "ProductFrontendMassActionButton"
    When I click on "ProductFrontendMassActionHeadButtonTablet"
    And I check AA1 record in "Product Frontend Grid" grid
    Then I should see "ProductFrontendMassPanelInBottomSticky" element inside "Bottom Active Sticky Panel" element
    When I uncheck AA1 record in "Product Frontend Grid" grid
    Then I should not see "ProductFrontendMassPanelInBottomSticky" element inside "Bottom Active Sticky Panel" element
    When I check AA1 record in "Product Frontend Grid" grid
    Then I should see "ProductFrontendMassClosePanel" element inside "Bottom Active Sticky Panel" element
    And I should see "ProductFrontendMassAddPPanelInBottomSticky" element inside "Bottom Active Sticky Panel" element
    When I click "ProductFrontendMassClosePanel"
    Then I should not see "ProductFrontendMassPanelInBottomSticky" element inside "Bottom Active Sticky Panel" element

  Scenario: Product mass actions search is hidden (tablet)
    Given I set window size to 992x1024
    When I click on "ProductFrontendMassActionHeadButtonTablet"
    And I check AA1 record in "Product Frontend Grid" grid
    Then I should see "ProductFrontendMassAddPPanelInBottomSticky" element inside "Bottom Active Sticky Panel" element
    And I click "ProductFrontendMassAddPPanelInBottomSticky"
    Then I should see "1 product was added" flash message
    Then I should not see "ProductFrontendMassClosePanel"
    Then I should not see "ProductFrontendMassActionButton"
    When I check AA1 record in "Product Frontend Grid" grid
    And I click "ProductFrontendMassOpenInDialog"
    Then I should see an "Fullscreen Popup" element
    And I should see "Fullscreen Popup Header" element with text "Choose Action" inside "Fullscreen Popup" element
    And I should not see "ProductFrontendGridMassActionSearch" element inside "Fullscreen Popup" element
    When I click "Create New Shopping List" in modal window
    Then should see an "Create New Shopping List popup" element
    When type "Shopping List 6" in "Shopping List Name"
    And click "Create and Add"
    Then should see 'Shopping list "Shopping List 6" was created successfully' flash message
    And click on "Flash Message Close Button"
    And click on "Flash Message Close Button"

  Scenario: Verify that a "Default Shopping List" action is always on the top of the actions list
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

  Scenario: Product mass actions search is visible if number of actions more then 5 (tablet)
    Given I set window size to 992x1024
    When I click on "ProductFrontendMassActionHeadButtonTablet"
    And I check AA1 record in "Product Frontend Grid" grid
    And I click "ProductFrontendMassOpenInDialog"
    Then I should see an "Fullscreen Popup" element
    And I should see "ProductFrontendGridMassActionSearch" element inside "Fullscreen Popup" element
    Then I should see "ProductFrontendGridMassActionDefaultItem" element with text "Add to Shopping List 8" inside "Fullscreen Popup" element
    When I type "Shopping List 8" in "ProductFrontendGridMassActionSearchInDialog"
    Then I should see "Highlight Container" element inside "Fullscreen Popup" element
    And I should see "Highlighted Text" element with text "Shopping List 8" inside "Fullscreen Popup" element
    When I click "Shopping List Quick Search Clear in Dialog"
    Then I should not see "Highlight Container" element inside "Fullscreen Popup" element
