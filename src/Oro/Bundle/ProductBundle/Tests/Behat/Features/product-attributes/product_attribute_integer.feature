@regression
@ticket-BB-9989
@ticket-BB-7152
@fixture-OroProductBundle:ProductAttributesFixture.yml
Feature: Product attribute integer
  In order to have custom attributes for Product entity
  As an Administrator
  I need to be able to add product attribute and have attribute data in search, filter, sorter and product view page

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create product attribute
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products/ Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | IntegerField |
      | Type       | Integer      |
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

    When I check "Integer" in "Data Type" filter
    Then I should see following grid:
      | Name         | Storage type     |
      | IntegerField | Serialized field |

  Scenario: Update product family with new attribute
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [IntegerField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update product
    Given I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    And I fill "Product Form" with:
      | IntegerField | 32167 |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check product grid search
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I am on the homepage
    When I type "32167" in "search"
    And I click "Search Button"
    Then I should not see "SKU123" product
    And I should not see "SKU456" product

  Scenario: Check product grid filter and sorter
    Given I click "NewCategory"
    And I should see "SKU123" product
    And I should see "SKU456" product
    When I filter IntegerField as equals "32167"
    Then I should see "SKU123" product
    And I should not see "SKU456" product
    And grid sorter should have "IntegerField" options

  Scenario: Check attributes in product page with "Default Page" template
    When I click "View Details" for "SKU123" product
    Then I should see "IntegerField: 32167"

  Scenario: Change product page view to "Short Page"
    Given I proceed as the Admin
    And I go to System / Configuration
    And I follow "Commerce/Design/Theme" on configuration sidebar
    When fill "Page Templates form" with:
      | Use Default  | false      |
      | Product Page | Short page |
    And save form
    Then I should see "Configuration saved" flash message

  Scenario: Check attribute in product page with "Short Page" template
    Given I proceed as the Buyer
    When I reload the page
    Then I should see "IntegerField: 32167"

  Scenario: Change product page template to "Two columns page"
    Given I proceed as the Admin
    When fill "Page Templates form" with:
      | Product Page | Two columns page |
    And save form
    Then I should see "Configuration saved" flash message

  Scenario: Check attribute in product page with "Two columns page" template
    Given I proceed as the Buyer
    When I reload the page
    Then I should see "IntegerField: 32167"

  Scenario: Change product page view to "List Page"
    Given I proceed as the Admin
    When fill "Page Templates form" with:
      | Product Page | List page |
    And save form
    Then I should see "Configuration saved" flash message

  Scenario: Check attribute in product page with "List Page" template
    Given I proceed as the Buyer
    When I reload the page
    Then I should see "IntegerField: 32167"

  Scenario: Remove new attribute from product family
    Given I login as administrator
    And I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I clear "Attributes" field
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Check that attribute is not present
    Given I go to Products/ Products
    When I click "View" on row "SKU123" in grid
    Then I should not see "IntegerField"
    Then I should not see "32167"

  Scenario: Update product family with new attribute again to check if attribute data is deleted
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [IntegerField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Check that attribute is present but its data is gone
    Given I go to Products/ Products
    When I click "View" on row "SKU123" in grid
    Then I should see product with:
      | IntegerField | N/A |

  Scenario: Delete product attribute
    Given I go to Products/ Product Attributes
    When I click Remove "IntegerField" in grid
    Then I should see "Are you sure you want to delete this attribute?"
    And I click "Yes"
    Then I should see "Attribute successfully deleted" flash message
    And I should not see "Update schema"
