@fixture-OroCatalogBundle:categories.yml
Feature: Move category in tree
  In order to change order of categories or nesting
  As site administrator
  I need to be able to move category

  Scenario: Move category into another parent
    Given I login as administrator
    When I go to Products/Master Catalog
    When I expand "Retail Supplies" in tree
    And I click "Printers"
    And I expand "Retail Supplies" in tree
    And I drag and drop "Printers" before "Lighting Products"
    Then I should see "Lighting Products" after "Printers" in tree
    When I click "Save"
    Then I should see "Lighting Products" after "Printers" in tree
