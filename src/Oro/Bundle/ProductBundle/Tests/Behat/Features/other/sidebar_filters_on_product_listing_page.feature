@ticket-BB-17519
@ticket-BB-20219
@ticket-BB-22129
@fixture-OroProductBundle:product_listing_images.yml

Feature: Sidebar filters on product listing page
  To improve UX of the product category displaying on the storefront we need to implement top filters in the left sidebar

  Scenario: Feature background
    Given sessions active:
      | admin    |first_session |
      | customer |second_session|
    And I set configuration property "oro_product.filters_position" to "sidebar"
    And I set configuration property "oro_catalog.all_products_page_enabled" to "1"

  Scenario: Ensure that filters are collapsed at catalog page
    Given I proceed as the customer
    And I login as AmandaRCole@example.org buyer
    When I click "NewCategory"
    Then I should see an "Toggle Sidebar Button" element
    And I should not see an "Filters In Sidebar" element
    And I should not see an "Toggle Sidebar Button Expanded" element
    And I should see an "FrontendProductGridFilters" element

  Scenario: Ensure that filters are expanded at catalog page
    Given I proceed as the admin
    And I login as administrator
    And I go to System / Configuration
    And I follow "Commerce/Catalog/Filters and Sorters" on configuration sidebar
    And uncheck "Use default" for "Default Filter Panel State" field
    And I fill form with:
      | Default Filter Panel State | Expanded |
    And I submit form
    When I proceed as the customer
    Then I reload the page
    And I should not see an "Toggle Sidebar Button" element
    And I should see an "Filters In Sidebar" element
    And I should see an "Toggle Sidebar Button Expanded" element

  Scenario: Ensure that filters position will be saved after reloading the page
    Given I click "Toggle Sidebar Button Expanded"
    Then I should see an "Toggle Sidebar Button" element
    And I should not see an "Filters In Sidebar" element
    And I should not see an "Toggle Sidebar Button Expanded" element
    When I reload the page
    Then I should see an "Toggle Sidebar Button" element
    And I should not see an "Filters In Sidebar" element
    And I should not see an "Toggle Sidebar Button Expanded" element
    When I click "Toggle Sidebar Button"
    Then I should not see an "Toggle Sidebar Button" element
    And I should see an "Filters In Sidebar" element
    And I should see an "Toggle Sidebar Button Expanded" element
    When I reload the page
    Then I should not see an "Toggle Sidebar Button" element
    And I should see an "Filters In Sidebar" element
    And I should see an "Toggle Sidebar Button Expanded" element

  Scenario: Apply one filter at one time
    And I set filter Any Text as contains "product"
    Then I click "Apply Filters Button"
    Then I should see filter hints in frontend grid:
      | Any Text: contains "product" |
    And I should not see "Apply Filters Button"
    When I click "Clear All Filters"
    And I should not see "Clear All Filters"
    And I should not see "Apply Filters Button"

  Scenario: Change only select value for "Any Text" filter
    When I set filter Any Text as contains "product"
    Then I click "Apply Filters Button"
    Then I should see filter hints in frontend grid:
      | Any Text: contains "product" |
    And I should not see "Apply Filters Button"
    And I set filter Any Text as does not contain "product"
    Then I click "Apply Filters Button"
    Then I should see filter hints in frontend grid:
      | Any Text: does not contain "product" |
    And I should not see "Apply Filters Button"
    When I click "Clear All Filters"
    And I should not see "Clear All Filters"
    And I should not see "Apply Filters Button"

  Scenario: Hide Apply button if value was not changed
    When I set filter Any Text as contains "product"
    Then I click "Apply Filters Button"
    Then I should see filter hints in frontend grid:
      | Any Text: contains "product" |
    And I should not see a "Apply Filters Button" element
    And I set filter Any Text as contains "product"
    And I should not see a "Apply Filters Button" element
    And I set filter Any Text as contains "product 1"
    And I should see a "Apply Filters Button" element
    And I set filter Any Text as contains "product"
    And I should not see a "Apply Filters Button" element
    And I set filter Any Text as contains "product 1"
    Then I click "Apply Filters Button"
    Then I should see filter hints in frontend grid:
      | Any Text: contains "product 1" |
    And I set filter Any Text as contains "product 1"
    And I should not see "Apply Filters Button"
    And I set filter Any Text as does not contain "product 1"
    Then I click "Apply Filters Button"
    Then I should see filter hints in frontend grid:
      | Any Text: does not contain "product 1" |
    And I should not see "Apply Filters Button"
    When I click "Clear All Filters"
    And I should not see "Clear All Filters"
    And I should not see a "Apply Filters Button" element

  Scenario: Apply one filter at one time by press Enter key
    When I set filter Any Text as contains "product" and press Enter key
    Then I should see filter hints in frontend grid:
      | Any Text: contains "product" |
    And I should not see "Apply Filters Button"
    And I should see "Clear All Filters"
    When I click "Clear All Filters"
    And I should not see "Clear All Filters"
    And I should not see "Apply Filters Button"

  Scenario: Apply a few filters at one time
    When I set filter Any Text as does not contain "product"
    And I set filter SKU as is equal to "1GB81"
    And I set filter Name as is equal to "product1"
    Then I click "Apply Filters Button"
    Then I should see filter hints in frontend grid:
      | Name: is equal to "product1"         |
      | SKU: is equal to "1GB81"             |
      | Any Text: does not contain "product" |
    And I should not see "Apply Filters Button"
    When I click "Clear All Filters"
    And I should not see "Clear All Filters"
    And I should not see "Apply Filters Button"

  Scenario: Apply a few filters at one time by Enter key
    When I set filter Any Text as does not contain "product"
    And I set filter Name as is equal to "product1" and press Enter key
    Then I should see filter hints in frontend grid:
      | Name: is equal to "product1"         |
      | Any Text: does not contain "product" |
    And I should not see "Apply Filters Button"
    When I click "Clear All Filters"
    And I should not see "Clear All Filters"
    And I should not see "Apply Filters Button"

  Scenario: Remove filter hint if empty value was set
    When I set filter Any Text as contains "product"
    Then I click "Apply Filters Button"
    Then I should see filter hints in frontend grid:
      | Any Text: contains "product" |
    And I should not see "Apply Filters Button"
    Then I set filter Any Text as contains ""
    And I click "Apply Filters Button"
    And I should not see "Clear All Filters"
    And I should not see a "Apply Filters Button" element
    And I set filter SKU as is equal to "1GB81"
    And I set filter Name as is equal to "product1"
    Then I click "Apply Filters Button"
    Then I should see filter hints in frontend grid:
      | Name: is equal to "product1" |
      | SKU: is equal to "1GB81"     |
    And I set filter SKU as is equal to ""
    And I set filter Name as is equal to "product"
    Then I click "Apply Filters Button"
    Then I should see filter hints in frontend grid:
      | Name: is equal to "product" |
    And I should not see "Apply Filters Button"
    When I click "Clear All Filters"
    And I should not see "Clear All Filters"
    And I should not see "Apply Filters Button"

  Scenario: Show unapplied changes
    When I set filter Any Text as contains "product"
    And I click "Apply Filters Button"
    Then I should see filter hints in frontend grid:
      | Any Text: contains "product" |
    And I should not see "Apply Filters Button"
    And I set filter Name as is equal to "product1"
    When I click "Clear All Filters"
    And I should see a "Apply Filters Button" element
    And I should not see "Clear All Filters"
    And I should see filter Name field value is equal to "product1"
    Then I click "Apply Filters Button"
    Then I should see filter hints in frontend grid:
      | Name: is equal to "product1" |
    When I click "Clear All Filters"
    And I should not see "Clear All Filters"
    And I should not see "Apply Filters Button"

  Scenario: Reset filter hit
    When I set filter Any Text as does not contain "product"
    And I set filter Name as is equal to "product1" and press Enter key
    Then I should see filter hints in frontend grid:
      | Name: is equal to "product1"         |
      | Any Text: does not contain "product" |
    And I should not see "Apply Filters Button"
    When I set filter Name as does not contain ""
    Then I reset "Name" filter in "Catalog Sidebar Hints Container" sidebar
    Then I reset "Any Text" filter in "Catalog Sidebar Hints Container" sidebar
    Then should not see hint for "Name" filter in "Catalog Sidebar Hints Container" sidebar
    Then should not see hint for "Any Text" filter in "Catalog Sidebar Hints Container" sidebar
    And I should not see "Clear All Filters"
    And I should not see "Apply Filters Button"

  Scenario: Check if sidebar filters present at all products page
    Then I click "All Products"
    Then I should see an "Filters In Sidebar" element
    And I should see an "FrontendProductGridFilters" element

  Scenario: Ensure that side bar is collapsed when a product search query returns no results
    Given I type "Search string" in "search"
    When click "Search Button"
    Then number of records in "Product Frontend Grid" should be 0
    And I should see an "Toggle Sidebar Button" element
    And I should not see an "Filters In Sidebar" element
    And I should not see an "Toggle Sidebar Button Expanded" element

  Scenario: Ensure that filters are visible when a product search query returns no results
    Given I click "Toggle Sidebar Button"
    And I click "FrontendGridFilterManagerButton"
    When I click "FrontendGridFilterManagerButtonAll"
    Then I click "FrontendGridFilterManagerButtonNone"
    And I should see an "FrontendGridFilterManagerButton" element

  Scenario: Ensure the filter manager is visible after change selected filters
    Given I proceed as the admin
    And I go to Products/Product Attributes
    And I click edit "brand" in grid
    And I fill form with:
        | Filterable | Yes |
    And I save and close form
    And I should see "Attribute was successfully saved" flash message
    And I proceed as the customer
    And I reload the page
    And I click "Toggle Sidebar Button"
    When I click "FrontendGridFilterManagerButton"
    And I click "FrontendGridFilterManagerButtonAll"
    Then I should see an "FrontendGridFilterManager" element
    And I click "Toggle Sidebar Button Expanded"

  Scenario: Ensure that a toggle sidebar button is hidden on the tablet view
    Given I set window size to 992x1024
    When I reload the page
    Then I should see an "Frontend Grid Action Filter Button" element
    And I should not see an "Toggle Sidebar Button" element

    When I set window size to 1440x900
    Then I should not see an "Frontend Grid Action Filter Button" element
    And I should see an "Toggle Sidebar Button" element
