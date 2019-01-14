@regression
@ticket-BB-9989
@fixture-OroProductBundle:ProductAttributesFixture.yml
@fixture-OroLocaleBundle:GermanLocalization.yml
Feature: Product attribute float
  In order to have custom attributes for Product entity
  As an Administrator
  I need to be able to add product attribute and have attribute data in search, filter and sorter
  I want to have it properly formatted on product view page

  Scenario: Create product attribute
    Given I login as administrator
    And I go to Products/ Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | FloatField |
      | Type       | Float      |
    And I click "Continue"
    Then I should see that "Product Attribute Frontend Options" does not contain "Searchable"
    And I should see that "Product Attribute Frontend Options" contains "Filterable"
    And I should see that "Product Attribute Frontend Options" contains "Sortable"

    When I fill form with:
      | Filterable | Yes |
      | Sortable   | Yes |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message
    And I should not see "Update schema"
    And I enable the existing localizations

    When I check "Float" in "Data Type" filter
    Then I should see following grid:
      | Name       | Storage type     |
      | FloatField | Serialized field |

  Scenario: Update product family with new attribute
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [FloatField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update product
    Given I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    And I fill "Product Form" with:
      | FloatField | 321.67 |
    And I save and close form
    Then I should see "Product has been saved" flash message
    And I should see "321.67"

  Scenario: Check product attribute is formatted according to localization
    Given I go to System/Configuration
    And follow "System Configuration/General Setup/Localization" on configuration sidebar
    When fill "Configuration Localization Form" with:
      | Default Localization | German_Loc |
    And click "Save settings"
    Then I should see "Configuration saved" flash message
    When I go to Products/ Products
    And I click "View" on row "SKU123" in grid
    Then I should see "321,67"

  Scenario: Check product grid search
    Given I login as AmandaRCole@example.org buyer
    When I type "321.67" in "search"
    And I click "Search Button"
    Then I should not see "SKU123" product
    And I should not see "SKU456" product

  Scenario: Check product grid filter and sorter
    Given I click "NewCategory"
    And I should see "SKU123" product
    And I should see "SKU456" product
    When I filter FloatField as equals "321,67"
    Then I should see "SKU123" product
    And I should not see "SKU456" product
    And grid sorter should have "FloatField" options

  Scenario: Check product attribute is formatted according to localization on front store
    When I click "View Details" for "SKU123" product
    Then I should see "321,67"
    When I click "Localization Switcher"
    And I select "English" localization
    Then I should see "321.67"

  Scenario: Delete product attribute
    Given I login as administrator
    Given I go to Products/ Product Attributes
    When I click Remove "FloatField" in grid
    Then I should see "Are you sure you want to delete this attribute?"
    And I click "Yes"
    Then I should see "Attribute successfully deleted" flash message
    And I should not see "Update schema"
