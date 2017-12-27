@ticket-BB-7225
@fixture-OroProductBundle:product_frontend.yml
Feature: Product Filters On Products Catalog
  ToDo: BAP-16103 Add missing descriptions to the Behat features

  Scenario: Check Price filter
    Given I login as AmandaRCole@example.org buyer
    When I click "NewCategory"
    And filter Price as equals "12,34"
    Then I should see grid with filter hints:
      | Price: equals 1,234.00 / ea |
    And filter Price as equals "12345.6"
    Then I should see grid with filter hints:
      | Price: equals 12,345.60 / ea |
