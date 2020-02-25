@ticket-BB-16234
@fixture-OroProductBundle:product_frontend.yml
Feature: Product catalog Filter Panel state
  As Administrator
  I have Filter Panel collapsed by default for all grids in back office
  I have a preserved Filter Panel state from last usage
  I need to have ability to configure default Filter Panel state for product catalog on front store
  As Customer User
  On the product catalog, by default I see the Filter Panel is collapsed OR expand, as it is configured from back office
  I have Filter Panel collapsed by default for rest of grids
  I have a preserved Filter Panel state from last usage separately for product catalog and rest of grids

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    Then I proceed as the Admin
    And I login as administrator
    Then I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer

  Scenario: Check that Filter Panel is collapsed by default on front store
    Given I proceed as the Buyer
    When I click "NewCategory"
    Then I should not see an "GridFilters" element
    When follow "Account"
    And click "Users"
    Then I should not see an "GridFilters" element

  Scenario: Check that Filter Panel is collapsed by default on back office
    Given I proceed as the Admin
    When go to System/ User Management/ Users
    Then I should not see an "GridFilters" element

  Scenario: Check that Filter Panel default state option is present in organization configuration
    Given I proceed as the Admin
    When I go to System/ User Management/ Organizations
    And I click Configuration ORO in grid
    And I follow "Commerce/Catalog/Filters and Sorters" on configuration sidebar
    Then I should see "Default Filter Panel State"

  Scenario: Check that Filter Panel default state is configurable for product catalog
    Given I proceed as the Admin
    When I go to System/ Configuration
    And I follow "Commerce/Catalog/Filters and Sorters" on configuration sidebar
    And uncheck "Use default" for "Default Filter Panel State" field
    And I fill form with:
      | Default Filter Panel State             | Expanded |
    And I submit form
    Then I should see "Configuration saved" flash message
    When go to System/ User Management/ Users
    Then I should not see an "GridFilters" element

  Scenario: Check that Filter Panel is expanded by default only for product catalog on front store
    Given I proceed as the Buyer
    When I click "NewCategory"
    Then I should see an "GridFilters" element
    When follow "Account"
    And click "Users"
    Then I should not see an "GridFilters" element

  Scenario: Check that Filter Panel state for product catalog is preserved from last usage
    Given I proceed as the Buyer
    When I click "NewCategory"
    Then I should see an "GridFilters" element
    When I filter SKU as is equal to "SKU2"
    And I click "GridFiltersButton"
    Then I should not see an "GridFilters" element
    But I should see "SKU is equal to \"SKU2\"" in the "GridFiltersState" element
    When I reload the page
    Then I should not see an "GridFilters" element
    When I click "GridFiltersState"
    Then I should see an "GridFilters" element
    When I reload the page
    Then I should see an "GridFilters" element

  Scenario: Check that Filter Panel state for rest of grids is preserved separately from last usage
    Given I proceed as the Buyer
    When follow "Account"
    And click "Users"
    Then I should not see an "GridFilters" element
    When I click "GridFiltersButton"
    Then I should see an "GridFilters" element
    When I reload the page
    Then I should see an "GridFilters" element
    When I filter First Name as contains "Amanda"
    And I click "GridFiltersButton"
    Then I should not see an "GridFilters" element
    But I should see "First Name contains \"Amanda\"" in the "GridFiltersState" element
    When I reload the page
    Then I should not see an "GridFilters" element
    When I click "GridFiltersState"
    Then I should see an "GridFilters" element
    When I reload the page
    Then I should see an "GridFilters" element

  Scenario: Check that Filter Panel state for back office grids is preserved separately from last usage
    Given I proceed as the Admin
    When go to System/ User Management/ Users
    Then I should not see an "GridFilters" element
    But I should see "Enabled Enabled" in the "GridFiltersState" element
    When I click "GridFiltersState"
    Then I should see an "GridFilters" element
    When I reload the page
    Then I should see an "GridFilters" element
    And I click "GridFiltersButton"
    Then I should not see an "GridFilters" element
    But I should see an "GridFiltersState" element
    When I reload the page
    Then I should not see an "GridFilters" element
