@ticket-BB-7225
@fixture-OroProductBundle:product_frontend.yml
Feature: Product Filters On Products Catalog

  Scenario: Feature Background
    Given sessions active:
      | admin    |first_session |
      | customer  |second_session|

  Scenario: Check Price filter
    Given I proceed as the customer
    And I login as AmandaRCole@example.org buyer
    When I click "NewCategory"
    And I open "Price" filter
    Then I should see "each" in the "FrontendProductGridFilters" element
    And I should see "hour" in the "FrontendProductGridFilters" element
    And I should see "item" in the "FrontendProductGridFilters" element
    And I should see "kilogram" in the "FrontendProductGridFilters" element
    And I should see "piece" in the "FrontendProductGridFilters" element
    And I should see "set" in the "FrontendProductGridFilters" element

  Scenario: Check price filter apply values
    And I set range filter "Price" as min value "12.45" and max value "16.34" use "each" unit
    Then should see filter hints in frontend grid:
      | Price: between 12.45 and 16.34 / ea |
    And I set range filter "Price" as min value "12345.65" and max value "16845.78" use "each" unit
    Then should see filter hints in frontend grid:
      | Price: between 12,345.65 and 16,845.78 / ea |
    And I set range filter "Price" as min value "800" and max value "500" use "each" unit
    Then should see filter hints in frontend grid:
      | Price: between 500.00 and 800.00 / ea |
    And I set range filter "Price" as min value "400" use "each" unit
    Then should see filter hints in frontend grid:
      | Price: greater than 400.00 / ea |
    And I set range filter "Price" as max value "700" use "each" unit
    Then should see filter hints in frontend grid:
      | Price: less than 700.00 / ea |

  Scenario: Check price filter apply values with different units
    And I set range filter "Price" as min value "12" and max value "16" use "item" unit
    Then should see filter hints in frontend grid:
      | Price: between 12.00 and 16.00 / item |
    And I set range filter "Price" as min value "2" and max value "5" use "set" unit
    Then should see filter hints in frontend grid:
      | Price: between 2.00 and 5.00 / set |
    And I set range filter "Price" as min value "100" and max value "50" use "kg" unit
    Then should see filter hints in frontend grid:
      | Price: between 50.00 and 100.00 / kg |
    And I set range filter "Price" as min value "200" use "piece" unit
    Then should see filter hints in frontend grid:
      | Price: greater than 200.00 / pc |
    And I set range filter "Price" as max value "22" use "hour" unit
    Then should see filter hints in frontend grid:
      | Price: less than 22.00 / hr |

  Scenario: Check price filter in single unit mode
    Given I proceed as the admin
    And I login as administrator
    And go to System/ Configuration
    And I follow "Commerce/Product/Product Unit" on configuration sidebar
    And uncheck "Use default" for "Single Unit" field
    And I check "Single Unit"
    And uncheck "Use default" for "Show Unit Code" field
    And I check "Show Unit Code"
    And I save setting
    Then I proceed as the customer
    When I click "NewCategory"
    And I open "Price" filter
    Then I should not see "each" in the "FrontendProductGridFilters" element
    And I should not see "hour" in the "FrontendProductGridFilters" element
    And I should not see "item" in the "FrontendProductGridFilters" element
    And I should not see "kilogram" in the "FrontendProductGridFilters" element
    And I should not see "piece" in the "FrontendProductGridFilters" element
    And I should not see "set" in the "FrontendProductGridFilters" element
    And I set range filter "Price" as min value "12" and max value "16"
    Then should see filter hints in frontend grid:
      | Price: between 12.00 and 16.00 / ea |
    Then I proceed as the admin
    And uncheck "Use default" for "Default Primary Unit" field
    And select "item" from "Default Primary Unit"
    And I save setting
    Then I proceed as the customer
    When I click "NewCategory"
    And I set range filter "Price" as min value "12" and max value "16"
    Then should see filter hints in frontend grid:
      | Price: between 12.00 and 16.00 / item |
