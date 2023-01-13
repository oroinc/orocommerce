@ticket-BB-8426
@automatically-ticket-tagged
@fixture-OroWebCatalogBundle:web_catalog.yml
Feature: Move node in tree
  In order to change order of nodes or nesting
  As site administrator
  I need to be able to move node

  Scenario: Move node within the same parent
    Given I login as administrator
    When I go to Marketing/Web Catalogs
    And I click view "Default Web Catalog" in grid
    Then I click "Edit Content Tree"
    And I click "Save"
    Then I should see "Content Node has been saved" flash message

    When I drag and drop "Products" before "Clearance"
    Then I should not see "Changing Page URLs"
    And I should see "Clearance" after "Products" in tree

    Then I reload the page
    And I should see "Clearance" after "Products" in tree

  Scenario: Move node into another parent
    When I expand "Clearance" in tree
    And I click "By Brand"
    And I drag and drop "By Brand" before "Products"
    And I click "Apply" in modal window
    Then I should see "By Brand" after "New Arrivals" in tree
    When I click "Save"
    And I should see "Content Node has been saved" flash message
    Then I should see "By Brand" after "New Arrivals" in tree

  Scenario: Move node as new root
    When I drag and drop "By Brand" before "Default Web Catalog"
    Then I should see "You can not create new root content node."
