@ticket-BB-20880
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroProductBundle:products_grid_frontend.yml

Feature: Check tooltips on the catalog switcher
  In order to check for the appearance of the tooltip when hovering over the catalog switcher buttons
  As a customer
  I go to product listing page and try to hover over the catalog switcher buttons

  Scenario: Check for the appearance of the tooltip when hovering over the catalog switcher buttons
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I click "Category 1"
    And I should see an "Catalog Switcher" element
    When I hover on "Gallery View"
    Then I should see "Gallery View" in the "Tooltip" element
    When I hover on "List View"
    Then I should see "List View" in the "Tooltip" element
    When I hover on "No Image View"
    Then I should see "Compact View" in the "Tooltip" element

  Scenario: Check for the appearance of the tooltip when hovering over the catalog switcher buttons in the sticky panel
    Given I click "Copyright"
    And I should not see an "Catalog Switcher" element
    And I should see an "Catalog Switcher Into Sticky Panel" element
    When I hover on "Gallery View"
    Then I should see "Gallery View" in the "Tooltip" element
    When I hover on "List View"
    Then I should see "List View" in the "Tooltip" element
    When I hover on "No Image View"
    Then I should see "Compact View" in the "Tooltip" element
