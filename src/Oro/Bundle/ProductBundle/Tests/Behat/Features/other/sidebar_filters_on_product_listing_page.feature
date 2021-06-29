@ticket-BB-17519
@fixture-OroProductBundle:product_listing_images.yml

Feature: Sidebar filters on product listing page
  To improve UX of the product category displaying on the storefront we need to implement top filters in the left sidebar

  Scenario: Feature background
    Given I set configuration property "oro_product.filters_position" to "sidebar"
    And I set configuration property "oro_catalog.all_products_page_enabled" to "1"

  Scenario: Check if sidebar filters present at catalog page
    Then I signed in as AmandaRCole@example.org on the store frontend
    When I click "NewCategory"
    Then I should see an "Filters In Sidebar" element
    And I should see an "FrontendProductGridFilters" element

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
