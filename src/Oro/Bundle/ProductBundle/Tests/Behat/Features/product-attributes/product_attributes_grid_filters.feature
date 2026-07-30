@ticket-BAP-23396

Feature: Product Attributes Grid Filters
  In order to efficiently navigate the product attributes list
  As an Administrator
  I want to be able to filter product attributes by name and data type.

  Scenario: Filter product attributes by field name
    Given I login as administrator
    And I go to Products/ Product Attributes
    When I filter Name as contains "sku"
    Then I should see following records in grid:
      | sku |
    And records in grid should be 1
    And I reset Name filter

  Scenario: Filter product attributes by data type
    When I check "Boolean" in "Data Type" filter
    Then I should see following records in grid:
      | featured   |
      | newArrival |
    And records in grid should be 2
    And I reset Data Type filter
