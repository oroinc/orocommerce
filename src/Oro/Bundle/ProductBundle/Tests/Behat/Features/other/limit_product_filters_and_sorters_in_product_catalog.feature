@fixture-OroProductBundle:limit_product_filters_sorters.yml
Feature: Limit product filters and sorters in product catalog
  In order to have the possibility to limit filters and sorters on the product catalog page
  As an Administrator
  I need to be able to hide filters and sorters that are not linked to any visible product through the product family

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator

  Scenario Outline: Create product attributes
    Given I go to Products/ Product Attributes
    When I click "Create Attribute"
    And fill form with:
      | Field Name | <FieldName> |
      | Type       | Integer     |
    And click "Continue"
    And fill form with:
      | Filterable | Yes |
      | Sortable   | Yes |
    And save and close form
    Then I should see "Attribute was successfully saved" flash message

    Examples:
      | FieldName              |
      | DefaultFamilyAttribute |
      | SecondFamilyAttribute  |

  Scenario: Update default family
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And set Attribute Groups with:
      | Label          | Visible | Attributes               |
      | New attributes | true    | [DefaultFamilyAttribute] |
    And save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update second family
    Given I go to Products/ Product Families
    When I click "Edit" on row "second_family_code" in grid
    And set Attribute Groups with:
      | Label          | Visible | Attributes                                                                                                                                                          |
      | New attributes | true    | [SecondFamilyAttribute]                                                                                                                                             |
    And save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Check that feature hides sorters and filters on the search page
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And type "SKU1" in "search"
    And I click "Search Button"
    Then I check that filter block visible in frontend product grid
    And should see "DefaultFamilyAttribute" in the "ProductFrontendGridFiltersBlock" element
    And should see "DefaultFamilyAttribute" in the "Frontend Product Grid Sorter" element
    And should not see "SecondFamilyAttribute" in the "ProductFrontendGridFiltersBlock" element
    And should not see "SecondFamilyAttribute" in the "Frontend Product Grid Sorter" element

  Scenario: Disable unrelated product filters and sorting
    Given I proceed as the Admin
    And go to System / Configuration
    When I follow "Commerce/Catalog/Filters and Sorters" on configuration sidebar
    And uncheck "Use default" for "Hide Unrelated Product Filters And Sorting Options" field
    And uncheck "Hide Unrelated Product Filters And Sorting Options"
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check that feature hides sorters and filters on the search page
    Given I proceed as the Buyer
    And reload the page
    Then I check that filter block visible in frontend product grid
    And should see "DefaultFamilyAttribute" in the "ProductFrontendGridFiltersBlock" element
    And should see "DefaultFamilyAttribute" in the "Frontend Product Grid Sorter" element
    And should see "SecondFamilyAttribute" in the "ProductFrontendGridFiltersBlock" element
    And should see "SecondFamilyAttribute" in the "Frontend Product Grid Sorter" element

  Scenario: Check that sorter "Relevance" is present and applied
    Given I should see "Relevance" in the "Frontend Product Grid Sorter" element
    And should see "Sorted By: Relevance"
