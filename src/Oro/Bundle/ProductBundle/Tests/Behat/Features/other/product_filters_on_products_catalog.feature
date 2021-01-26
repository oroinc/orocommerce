@ticket-BB-7225
@fixture-OroProductBundle:product_frontend.yml
Feature: Product Filters On Products Catalog
  In order to ...
  As an ...
  I should be able to ...

  Scenario: Check Price filter
    Given I login as AmandaRCole@example.org buyer
    When I click "NewCategory"
    And I set range filter "Price" as min value "12.45" and max value "16.34" use "each" unit
    Then should see filter hints in frontend grid:
      | Price: between 12.45 and 16.34 / ea |
    And I set range filter "Price" as min value "12345.65" and max value "16845.78" use "each" unit
    Then should see filter hints in frontend grid:
      | Price: between 12,345.65 and 16,845.78 / ea |
    And I set range filter "Price" as min value "800" and max value "500" use "each" unit
    Then should see filter hints in frontend grid:
      | Price: between 500.00 and 800.00 / ea |
