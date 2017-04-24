@fixture-web_catalog.yml
Feature: Move node within parent
  In order to change order of nodes
  As site administrator
  I need to be able to move node within the same parent

  Scenario: Move node within the same parent
    Given I login as administrator
    When I go to Marketing/Web Catalogs
    And I click view "Default Web Catalog" in grid
    Then I click "Edit Content Tree"

    When I drag and drop "Products" before "Clearance"
    Then I should not see "Changing Page URLs"
    And I should see a "Clearance after Products" element

    Then I reload the page
    And I should see a "Clearance after Products" element
