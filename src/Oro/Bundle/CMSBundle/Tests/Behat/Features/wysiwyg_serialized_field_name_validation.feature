@regression
Feature: WYSIWYG serialized field name validation
  In order to be able to create WYSIWYG fields for the extend entity
  As an administrator
  I create WYSIWYG fields and check validation

  Scenario: Create entity
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And I click "Create Entity"
    When I fill form with:
      | Name         | EntityWithWYSIWYGField |
      | Label        | EntityWithWYSIWYGField |
      | Plural Label | EntityWithWYSIWYGField |
    And I save and close form
    Then I should see "Entity saved" flash message

  Scenario: Create table column with `WYSIWYG` type
    Given I click "Create Field"
    When I fill form with:
      | Field name   | wysiwyg          |
      | Storage Type | Serialized field |
      | Type         | WYSIWYG          |
    And I click "Continue"
    And I save and close form
    Then I should see "Field saved" flash message

  Scenario: Create table column with `String` type and additional `style` field
    Given I click "Create Field"
    When I fill form with:
      | Field name   | string_style     |
      | Storage Type | Serialized field |
      | Type         | String           |
    And I click "Continue"
    And I save and close form
    Then I should see "Field saved" flash message

  Scenario: Create table column with type `String` and additional `properties` field
    Given I click "Create Field"
    When I fill form with:
      | Field name   | string_properties |
      | Storage Type | Table column      |
      | Type         | String            |
    And I click "Continue"
    And I save and close form
    Then I should see "Field saved" flash message

  Scenario: Check validation for `WYSIWYG` field and field exist
    Given I click "Create Field"
    When I fill form with:
      | Field name   | wysiwyg          |
      | Storage Type | Serialized field |
      | Type         | WYSIWYG          |
    And I click "Continue"
    Then I should see validation errors:
      | Field name | This field name cannot be used. |

  Scenario: Check validation for `WYSIWYG` field and additional fields exists
    Given I fill form with:
      | Field name   | string           |
      | Storage Type | Serialized field |
      | Type         | WYSIWYG          |
    When I click "Continue"
    Then I should see validation errors:
      | Field name | This field name cannot be used. |

  Scenario: Check validation for `WYSIWYG` field and additional `style` field exist
    Given I fill form with:
      | Field name   | wysiwyg_style |
      | Storage Type | Table column  |
      | Type         | WYSIWYG       |
    When I click "Continue"
    Then I should see validation errors:
      | Field name | This field name cannot be used. |

  Scenario: Check validation for `WYSIWYG` field and additional `properties` field exist
    Given I fill form with:
      | Field name   | wysiwyg_properties |
      | Storage Type | Table column       |
      | Type         | WYSIWYG            |
    When I click "Continue"
    Then I should see validation errors:
      | Field name | This field name cannot be used. |

  Scenario: Check validation for `String` field and field exist
    Given I fill form with:
      | Field name   | wysiwyg          |
      | Storage Type | Serialized field |
      | Type         | String           |
    When I click "Continue"
    Then I should see validation errors:
      | Field name | A field with this name is already exist. |

  Scenario: Check validation if `String` field and additional `style` field exist
    Given I fill form with:
      | Field name   | wysiwyg_style |
      | Storage Type | Table column  |
      | Type         | String        |
    When I click "Continue"
    Then I should see validation errors:
      | Field name | This field name cannot be used. |

  Scenario: Check validation if `String` field and additional `properties` field exist
    Given I fill form with:
      | Field name   | wysiwyg_properties |
      | Storage Type | Table column       |
      | Type         | String             |
    When I click "Continue"
    Then I should see validation errors:
      | Field name | This field name cannot be used. |
    And I click "Cancel"

  Scenario: Check import validation
    Given I download Data Template file for "EntityWithWYSIWYGField" extend entity
    When I fill template with data:
      | fieldName          | is_serialized | type    | importexport.header | importexport.order | importexport.excluded | form.is_enabled | entity.label | entity.description | datagrid.order | view.is_displayable | view.priority  | attachment.acl_protected |
      | wysiwyg_properties | true          | wysiwyg | header_value        | order_value        | 0                     | yes             | label_value  | description_value  | order_value    | yes                 | priority_value | yes                      |
      | wysiwyg_style      | true          | wysiwyg | header_value        | order_value        | 0                     | yes             | label_value  | description_value  | order_value    | yes                 | priority_value | yes                      |
      | string             | true          | wysiwyg | header_value        | order_value        | 0                     | yes             | label_value  | description_value  | order_value    | yes                 | priority_value | yes                      |
    And I import file
    Then Email should contains the following "Errors: 3 processed: 0, read: 3, added: 0, updated: 0, replaced: 0" text
    And I follow "Error log" link from the email
    And I should see "Error in row #1. fieldName: This field name cannot be used."
    And I should see "Error in row #2. fieldName: This field name cannot be used."
    And I should see "Error in row #3. fieldName: This field name cannot be used."
