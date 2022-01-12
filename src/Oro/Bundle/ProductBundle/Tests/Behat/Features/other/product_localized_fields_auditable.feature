@regression
@ticket-BAP-20919

Feature: Product Localized Fields Auditable
  In order to have localized fields and their extensions like product name and description etc. auditable
  As an Administrator
  I need to be able to see the audit data records once I updated them and see changes in change history

  Scenario: Login as administrator and go to entity management
    Given I login as administrator

  Scenario Outline: Check product localized fields and its descendants are auditable by default
    Given I go to System/Entities/Entity Management
    When filter Name as is equal to "<Name>"
    Then I should see following grid:
      | Auditable | Yes |
    And I click View <Name> in grid
    And I press "Fields"
    Then I should see following grid containing rows:
      | Name               | Auditable |
      | <AuditField>       | Yes       |
    When I click "Edit"
    And I press "Other"
    And fill form with:
      | Auditable | Yes |
    And save and close form
    Then I should see "Entity saved" flash message
    Examples:
      | Name                    | AuditField |
      | ProductName             | string     |
      | ProductDescription      | wysiwyg    |
      | ProductShortDescription | text       |

  Scenario: Check default data audit settings of entity product localized fields and enable auditable for fields
    Given I go to System/Entities/Entity Management
    When filter Name as is equal to "Product"
    And I click View Product in grid
    And I press "Fields"
    Then I should see following grid containing rows:
      | Name              | Auditable |
      | names             | Yes       |
      | descriptions      | No        |
      | shortDescriptions | No        |
    When I click Edit descriptions in grid
    And I press "Backoffice options"
    And fill form with:
      | Auditable | Yes |
    And save and close form
    Then I should see "Field saved" flash message
    When I click Edit shortDescriptions in grid
    And I press "Backoffice options"
    And fill form with:
      | Auditable | Yes |
    And save and close form
    Then I should see "Field saved" flash message
    And I should see following grid containing rows:
      | Name              | Auditable |
      | descriptions      | Yes       |
      | shortDescriptions | Yes       |

  Scenario: Check localized fallback value entity and its descendants are auditable by default
    Given I go to System/Entities/Entity Management
    When filter Name as is equal to "LocalizedFallbackValue"
    Then I should see following grid:
      | Auditable | Yes |
    And I click View LocalizedFallbackValue in grid
    And I press "Fields"
    Then I should see following grid containing rows:
      | Name               | Auditable |
      | string             | Yes       |
      | text               | Yes       |
      | wysiwyg            | Yes       |

  # Test entity product name and category long/ short description
  Scenario: I edit product name and description default values and I should see those changes in change history
    When I go to Products/ Products
    And I click "Create Product"
    And I fill "ProductForm Step One" with:
      | Type           | Simple  |
      | Product Family | Default |
    And I click "Continue"
    And I fill "Product Form" with:
      | SKU               | PSKU1-auditable           |
      | Name              | Product1 Auditable        |
      | Short Description | Some short description    |
      | Description       | Some product description  |
    And I save and close form
    And I should see "Product has been saved" flash message
    And I click "Change History"
    Then number of records in "Audit History Grid" should be 1
    And I should see "Name: Product Name \"English (United States)\" added Product Name \"Default Value\" added: String value: Product1 Auditable"
    And I should see "Description: Product Description \"English (United States)\" added Product Description \"Default Value\" added: WYSIWYG value:"
  # Here we only assert some keywords because the ordering of updated fields are inconsistent and
  # product description new value will be with some auto-generated info, it is unpredictable.
    And I should see "Short Description: Product Short Description \"English (United States)\" added Product Short Description \"Default Value\" added: Text value: <p>Some short description</p>"
    And close ui dialog

  Scenario: I edit product name/ description first localization values and I should see those changes in history
    When I click "Edit"
    And click on "Product Names Fallbacks"
    And press "English (United States)" in "Short Description" section
    And press "English (United States)" in "Description" section
    And I fill "Product Form" with:
      | Name English (United States) use fallback                   | false                                  |
      | Name English (United States) value                          | Product1 Auditable US                  |
      | Short Description English (United States) fallback selector | Custom                                 |
      | Short Description English (United States)                   | Some short description United States   |
      | Description English (United States) fallback selector       | Custom                                 |
      | Description English (United States)                         | Some product description United States |
    And I save and close form
    And I should see "Product has been saved" flash message
    And I click "Change History"
    Then number of records in "Audit History Grid" should be 2
    And I should see "Name: Product Name \"English (United States)\" changed: String value: Product1 Auditable US"
    And I should see "Description: Product Description \"English (United States)\" changed: WYSIWYG value:"
    And I should not see "Description: Product Description \"Default Value\" changed: WYSIWYG value:"
    And I should see "Short Description: Product Short Description \"English (United States)\" changed: Text value: <p>Some short description United States</p>"
    And close ui dialog

  # test entity LocalizedFallbackValue
  Scenario: I create a product brand with given localized name/ description values and I should see a record in change history
    When I go to Products/ Product Brands
    And I click "Create Brand"
    And fill "Brand Form" with:
      | Name              | New Auditable Brand    |
    And I save and close form
    And I should see "Brand has been saved" flash message
    And I click edit "New Auditable Brand" in grid
    And I click "Change History"
    Then number of records in "Audit History Grid" should be 1
    And I should see "Name: Localized fallback value \"English (United States)\" added Localized fallback value \"Default Value\" added: String value: New Auditable Brand"
    And close ui dialog

  Scenario: I edit product brand with given localized name/ description values and I should see those changes in history
    When click on "Brand Form Name Fallbacks"
    And I press "English (United States)" in "Short description" section
    And I press "English (United States)" in "Description" section
    And fill "Brand Form" with:
      | Name English Use Default              | false                     |
      | Name English                          | New Auditable Brand US    |
    And I save and close form
    And I should see "Brand has been saved" flash message
    And I click edit "New Auditable Brand" in grid
    And I click "Change History"
    Then number of records in "Audit History Grid" should be 2
    And I should see "Localized fallback value \"English (United States)\" changed: String value: New Auditable Brand US"
    And close ui dialog

  Scenario: Check records in Data Audit page that there should not see localized fallback value be recorded
    Given go to System/ Data Audit
    Then number of records should be 4
    When I press "Action"
    Then I should see following grid:
      | Action | Entity Type | Entity Name         |
      | Create | Product     | Product1 Auditable  |
      | Create | Brand       | New Auditable Brand |
      | Update | Product     | Product1 Auditable  |
      | Update | Brand       | New Auditable Brand |
  # Again update contents has random uuid and cant be examined.
