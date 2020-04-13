@fixture-OroProductBundle:limit_product_filters_sorters.yml
Feature: Limit product filters and sorters in product catalog
  In order to have the possibility to limit filters and sorters on the product catalog page
  As an Administrator
  I need to be able to hide filters and sorters that are not linked to any visible product through the product family

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create product attribute
    Given I proceed as the Admin
    Given I login as administrator
    And I go to Products/ Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | AttrWithFamily |
      | Type       | Integer        |
    And I click "Continue"

    When I fill form with:
      | Filterable | Yes |
      | Sortable   | Yes |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I click "Create Attribute"
    And I fill form with:
      | Field Name | AttrWithoutFamily |
      | Type       | Integer           |
    And I click "Continue"

    When I fill form with:
      | Filterable | Yes |
      | Sortable   | Yes |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I check "Integer" in "Data Type" filter
    Then I should see following grid:
      | Name              | Storage type     |
      | AttrWithFamily    | Serialized field |
      | AttrWithoutFamily | Serialized field |

  Scenario: Update product family with new attribute
    Given I go to Products/ Product Families
    When I click "Edit" on row "new_product_attribute_family_code" in grid
    And set Attribute Groups with:
      | Label           | Visible | Attributes |
      | system  group   | true    | [SKU, Name, Is Featured, New Arrival, Brand, Description, Short Description, Images, Inventory Status, Meta title, Meta description, Meta keywords, Product prices] |
      | New attributes  | true    | [AttrWithFamily]     |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Check that feature hides sorters and filters on the search page
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "Search Button"
    When I filter "Any Text" as contains "SKU1"
    Then I check that filter block visible in frontend product grid
    Then I should see "AttrWithFamily" in the "ProductFrontendGridFiltersBlock" element
    Then I should see "AttrWithFamily" in the "Frontend Product Grid Sorter" element
    Then I should not see "AttrWithoutFamily" in the "ProductFrontendGridFiltersBlock" element
    Then I should not see "AttrWithoutFamily" in the "Frontend Product Grid Sorter" element

  Scenario: Check that feature hides sorters and filters on the category page
    Given I proceed as the Buyer
    Then I click "New Category"
    Then I check that filter block visible in frontend product grid
    Then I should not see "AttrWithFamily" in the "ProductFrontendGridFiltersBlock" element
    Then I should not see "AttrWithFamily" in the "Frontend Product Grid Sorter" element
    Then I should not see "AttrWithoutFamily" in the "ProductFrontendGridFiltersBlock" element
    Then I should not see "AttrWithoutFamily" in the "Frontend Product Grid Sorter" element

  Scenario: Check that all sorters and filters displayed on the category page when feature is disabled
    Given I proceed as the Admin
    Given I go to System / Configuration
    And I follow "Commerce/Catalog/Filters and Sorters" on configuration sidebar
    And uncheck "Use default" for "Hide Unrelated Product Filters And Sorting Options" field
    And I uncheck "Hide Unrelated Product Filters And Sorting Options"
    And I click "Save settings"
    Given I proceed as the Buyer
    Then I reload the page
    Then I check that filter block visible in frontend product grid
    Then I should see "AttrWithFamily" in the "ProductFrontendGridFiltersBlock" element
    Then I should see "AttrWithFamily" in the "Frontend Product Grid Sorter" element
    Then I should see "AttrWithoutFamily" in the "ProductFrontendGridFiltersBlock" element
    Then I should see "AttrWithoutFamily" in the "Frontend Product Grid Sorter" element

  Scenario: Check that sorter "Relevance" is present and applied
    Then I should see "Relevance" in the "Frontend Product Grid Sorter" element
    Then I should see "Sorted By: Relevance"
