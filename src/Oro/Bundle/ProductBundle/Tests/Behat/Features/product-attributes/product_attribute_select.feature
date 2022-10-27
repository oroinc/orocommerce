@regression
@ticket-BB-9989
@ticket-BB-14755
@ticket-BB-7152
@ticket-BB-21056
@ticket-BB-20955
@fixture-OroProductBundle:ProductAttributesFixture.yml
Feature: Product attribute select
  In order to have custom attributes for Product entity
  As an Administrator
  I need to be able to add product attribute and have attribute data in search, filter and sorter

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create product attribute
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products/ Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | SelectField |
      | Type       | Select      |
    And I click "Continue"
    Then I should see that "Product Attribute Frontend Options" contains "Searchable"
    And I should see that "Product Attribute Frontend Options" contains "Filterable"
    And I should see that "Product Attribute Frontend Options" contains "Sortable"

    When I set Options with:
      | Label |
      |       |
    And I save and close form
    Then I should see validation errors:
      | Option First | This value should not be blank. |

    When I fill form with:
      | Searchable | Yes |
      | Filterable | Yes |
      | Sortable   | Yes |
    And I set Options with:
      | Label          |
      | TestValueOne   |
      | TestValueTwo   |
      | 0.5            |
      | 5              |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Update product family with new attribute
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [SelectField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update products
    Given I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    And I fill "Product Form" with:
      | SelectField | TestValueTwo |
    And I save and close form
    Then I should see "Product has been saved" flash message
    When I go to Products/ Products
    And I click "Edit" on row "SKU456" in grid
    And I fill "Product Form" with:
      | SelectField | 0.5 |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check product grid search
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I type "TestValueTwo" in "search"
    And I click "Search Button"
    Then I should see "SKU123" product
    And I should not see "SKU456" product

  Scenario: Check product grid filter and sorter
    Given I click "NewCategory"
    And I should see "SKU123" product
    And I should see "SKU456" product
    When I click "Grid Filters Button"
    And I check "TestValueTwo" in SelectField filter in frontend product grid
    Then I should see "SKU123" product
    And I should not see "SKU456" product
    And grid sorter should have "SelectField" options
    When I check "0.5" in SelectField filter in frontend product grid
    And I should see "SKU123" product
    Then I should see "SKU456" product
    And grid sorter should have "SelectField" options

  Scenario: Set all product variants to use numeric option as new attribute to observe their behaviour
    Given I proceed as the Admin
    When I go to Products/ Products
    And I click "Edit" on row "SKU123" in grid
    And I fill "Product Form" with:
      | SelectField | 5 |
    And I save and close form
    Then I should see "Product has been saved" flash message
    When I go to Products/ Products
    And I click "Create Product"
    And I fill "ProductForm Step One" with:
      | Type           | Configurable |
      | Product Family | Default      |
    And I click "Continue"
    And I fill "ProductForm" with:
      | Sku                     | SKUCONF       |
      | Name                    | Product Conf  |
      | URL Slug                | product-conf  |
      | Status                  | Enabled       |
      | Configurable Attributes | [SelectField] |
    And I press "Product Variants"
    And I select following records in grid:
      | SKU123 |
    And I save and close form
    Then I should see "Product has been saved" flash message
    When I press "Product Variants"
    And I click "Grid Settings"
    Then I should see following columns in the grid settings:
      | SelectField |
    When I check "SelectField"
    And I click "Grid Settings"
    Then number of records should be 1
    And I should see following grid:
      | SKU       | NAME     | SelectField    |
      | SKU123    | Product1 | 5              |

  Scenario: Set configurable products view as no matrix form
    When I go to System/Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And uncheck "Use default" for "Product Views" field
    And I fill form with:
      | Product Views | No Matrix Form |
    And I save form
    Then I should see "Configuration saved" flash message
#
  Scenario: Check configurable product in frontstore to check available options of new attribute are correct
    Given I proceed as the Buyer
    When I type "Product Conf" in "search"
    And I click "Search Button"
    And I click "Product Conf"
    Then I should see an "Configurable Product Form" element
    And "Configurable Product Form" must contains values:
      | SelectField    | 5     |
    When I fill "Configurable Product Form" with:
      | SelectField    | 5     |
    And I click "Add to Shopping List"
    Then I should see 'Product has been added to "Shopping List"' flash message

  Scenario: Change configurable product variants and check data are correct
    Given I proceed as the Admin
    When I go to Products/ Products
    And I click "Edit" on row "SKUCONF" in grid
    And I press "Product Variants"
    And I select following records in grid:
      | SKU456 |
    And I save and close form
    Then I should see "Product has been saved" flash message
    When I press "Product Variants"
    And I click "Grid Settings"
    Then I should see following columns in the grid settings:
      | SelectField |
    When I check "SelectField"
    And I click "Grid Settings"
    Then number of records should be 2
    And I should see following grid:
      | SKU       | NAME     | SelectField    |
      | SKU123    | Product1 | 5              |
      | SKU456    | Product2 | 0.5            |

  Scenario: Check configurable product in frontstore after changing product variants
    Given I proceed as the Buyer
    When I reload the page
    Then I should see an "Configurable Product Form" element
    And "Configurable Product Form" must contains values:
      | SelectField    | 0.5   | 5     |
    When I fill "Configurable Product Form" with:
      | SelectField    | 0.5   |
    And I click "Add to Shopping List"
    Then I should see 'Product has been added to "Shopping List"' flash message

  Scenario: Set all product variants to use string option as new attribute
    Given I proceed as the Admin
    When I go to Products/ Products
    And I click "Edit" on row "SKU123" in grid
    And I fill "Product Form" with:
      | SelectField | TestValueTwo |
    And I save and close form
    Then I should see "Product has been saved" flash message
    When I go to Products/ Products
    And I click "Edit" on row "SKU456" in grid
    And I fill "Product Form" with:
      | SelectField | TestValueOne |
    And I save and close form
    Then I should see "Product has been saved" flash message
    When I go to Products/ Products
    And I click "View" on row "SKUCONF" in grid
    When I press "Product Variants"
    And I click "Grid Settings"
    Then I should see following columns in the grid settings:
      | SelectField |
    When I check "SelectField"
    And I click "Grid Settings"
    Then number of records should be 2
    And I should see following grid:
      | SKU       | NAME     | SelectField    |
      | SKU123    | Product1 | TestValueTwo   |
      | SKU456    | Product2 | TestValueOne   |

  Scenario: Confirm configurable product show it's variants and attribute correctly
    Given I proceed as the Buyer
    When I reload the page
    Then I should see an "Configurable Product Form" element
    And "Configurable Product Form" must contains values:
      | SelectField    | TestValueTwo   | TestValueOne     |

  Scenario: Update product family and remove new attribute from it
    Given I proceed as the Admin
    When I go to Products/ Products
    And I click "Delete" on row "SKUCONF" in grid
    And I click "Yes, Delete"
    Then I should see "Product Deleted"
    When I go to Products/ Product Families
    And I click "Edit" on row "default_family" in grid
    And I clear "Attributes" field
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Check product grid search for not searchable attribute
    Given I proceed as the Buyer
    When I type "TestValueTwo" in "search"
    And I click "Search Button"
    Then I should not see "SKU123" product
    And I should not see "SKU456" product

  Scenario: Remove new attribute from product family
    Given I proceed as the Admin
    And I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I clear "Attributes" field
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Check that attribute is not present
    Given I go to Products/ Products
    When I click "View" on row "SKU123" in grid
    Then I should not see "SelectField"
    And I should not see "TestValueTwo"

  Scenario: Update product family with new attribute again to check if attribute data is deleted
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [SelectField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Check that attribute is present but its data is gone
    Given I go to Products/ Products
    When I click "View" on row "SKU123" in grid
    Then I should see product with:
      | SelectField | N/A |

  Scenario: Delete product attribute
    Given I go to Products/ Product Attributes
    When I click Remove "SelectField" in grid
    Then I should see "Are you sure you want to delete this attribute?"
    And I click "Yes"
    Then I should see "Attribute successfully deleted" flash message
    And I should see "Update schema"
