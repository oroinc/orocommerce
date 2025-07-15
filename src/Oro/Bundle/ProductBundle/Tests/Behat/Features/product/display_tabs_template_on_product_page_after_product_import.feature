Feature: Display Tabs Template On Product Page After Product Import

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Enable tabs page template
    Given I proceed as the Admin
    And I login as administrator
    When I go to System / Theme Configurations
    And I click "Edit" on row "Refreshing Teal" in grid
    And I fill "Theme Configuration Form" with:
      | Page Template | tabs |
    And I save and close form
    Then I should see "Theme Configuration" flash message

  Scenario: Create Integer attribute
    Given I go to Products / Product Attributes
    And click "Create Attribute"
    And fill form with:
      | Field Name | IntegerAttribute |
      | Type       | Integer          |
    And click "Continue"
    When I save and close form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Update product families
    Given I go to Products / Product Families
    And click Edit Default in grid
    And set Attribute Groups with:
      | Label      | Visible | Attributes         |
      | Test group | true    | [IntegerAttribute] |
    When I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Verify administrator is able Import Products from the file
    When I go to Products/ Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | IntegerAttribute | SKU   | Product Family.Code | Name.default.value | Description.default.value | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision |
      | 2                | PSKU2 | default_family      | Test Product 1     | Product Description 1     | enabled | simple | in_stock            | set                        | 1                          |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0" text

  Scenario: Verify that attribute SKU displayed in tab on imported product
    When I proceed as the Buyer
    And I am on the homepage
    And I type "PSKU2" in "search"
    And I click "Search Button"
    And I click "View Details" for "PSKU2" product
    Then I should see an "Attributes Group Products View Tab" element
