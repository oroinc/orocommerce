@regression
@ticket-BB-28044

Feature: Pinned product families page when product attributes menu item is placed above it
  In order to navigate between pinned pages
  As an administrator
  I want the Product Families page to keep its own title regardless of the Product Attributes menu item position

  Scenario: Move Product Attributes menu item above Product Families
    Given I login as administrator
    And I go to System/Menus
    And I click view application_menu in grid
    And I expand "Products" in tree "Sidebar Menu Tree"
    When I move "Product Attributes" before "Product Families" in tree "Sidebar Menu Tree"
    And I reload the page
    Then I should see "Product Families" after "Product Attributes" in tree "Sidebar Menu Tree"

  Scenario: Pin Product Families page
    Given I go to Products/Product Families
    Then I should see "Products/ Product Families" in breadcrumbs
    When I pin page
    Then Product Families link must be in pin holder
    And Product Attributes link must not be in pin holder

  Scenario: Pin Product Attributes page
    Given I go to Products/Product Attributes
    Then I should see "Products/ Product Attributes" in breadcrumbs
    When I pin page
    Then Product Attributes link must be in pin holder
