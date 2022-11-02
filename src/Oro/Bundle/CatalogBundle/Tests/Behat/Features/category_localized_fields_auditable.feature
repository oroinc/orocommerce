@regression
@ticket-BAP-20919

Feature: Category Localized Fields Auditable
  In order to have localized fields and their extensions like category title and description etc. auditable
  As an Administrator
  I need to be able to see the audit data records once I updated them and see changes in change history

  Scenario: Login as administrator and go to entity management
    Given I login as administrator

  Scenario Outline: Check default data audit settings of category localized fallback entities and enable all of them
    Given I go to System/Entities/Entity Management
    When filter Name as is equal to "<Name>"
    Then I should see following grid:
      | Auditable | <Auditable> |
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
      | Name                     | Auditable | AuditField |
      | CategoryTitle            | Yes       | string     |
      | CategoryLongDescription  | No        | wysiwyg    |
      | CategoryShortDescription | No        | text       |

  Scenario: Check default data audit settings of entity category localized fields and enable auditable for fields
    Given I go to System/Entities/Entity Management
    When filter Name as is equal to "Category"
    And I click View Category in grid
    And I press "Fields"
    Then I should see following grid containing rows:
      | Name              | Auditable |
      | titles            | Yes       |
      | longDescriptions  | No        |
      | shortDescriptions | No        |
    When I click Edit longDescriptions in grid
    And I press "Other"
    And fill form with:
      | Auditable | Yes |
    And save and close form
    Then I should see "Field saved" flash message
    When I click Edit shortDescriptions in grid
    And I press "Other"
    And fill form with:
      | Auditable | Yes |
    And save and close form
    Then I should see "Field saved" flash message
    And I should see following grid containing rows:
      | Name              | Auditable |
      | longDescriptions  | Yes       |
      | shortDescriptions | Yes       |

  # Test entity category name and category long/ short description
  Scenario: I edit category title/ description first localization values and I should see those changes in history
    When I go to Products/ Master Catalog
    And I click "Create Category"
    And fill "Category Form" with:
      | Title             | New Auditable Category    |
      | Short Description | Some short description    |
      | Long Description  | Some category description |
    And I click "Save"
    And I should see "Category has been saved" flash message
    And I click "Change History"
    Then number of records in "Audit History Grid" should be 1
    And I should see "Title: Category Title \"English (United States)\" added Category Title \"Default Value\" added: String value: New Auditable Category"
    And I should see "Short Description: Category Short Description \"English (United States)\" added Category Short Description \"Default Value\" added: Text value: <p>Some short description</p>"
    And I should see "Long Description: Category Long Description \"English (United States)\" added Category Long Description \"Default Value\" added: WYSIWYG value:"
  # Here we only assert some keywords because the ordering of updated fields are inconsistent and
  # wysiwyg field new value will be with some auto-generated info, it is unpredictable.
    And close ui dialog

  Scenario: I edit category title/ description first localization values and I should see those changes in history
    When I click on "Title Fallback Status"
    And press "English (United States)" in "Short Description" section
    And press "English (United States)" in "Long Description" section
    And fill "Category Form" with:
      | Title English (United States) fallback selector             | false                                   |
      | Title English (United States)                               | New Auditable Category US               |
      | Short Description English (United States) fallback selector | Custom                                  |
      | Short Description English (United States)                   | Some short description United States    |
      | Long Description English (United States) fallback selector  | Custom                                  |
      | Long Description English (United States)                    | Some category description United States |
    And I click "Save"
    And I should see "Category has been saved" flash message
    And I click "Change History"
    Then number of records in "Audit History Grid" should be 2
    And I should see "Title: Category Title \"English (United States)\" changed: String value: New Auditable Category US"
    And I should see "Short Description: Category Short Description \"English (United States)\" changed: Text value: <p>Some short description United States</p>"
    And I should see "Long Description: Category Long Description \"English (United States)\" changed: WYSIWYG value:"
    And I should not see "Long Description: Category Long Description \"Default Value\" changed: WYSIWYG value:"
    And close ui dialog

  Scenario: Check records in Data Audit page that there should not see localized fallback value be recorded
    Given go to System/ Data Audit
    Then I should see following grid containing rows:
      | Action | Entity Type | Entity Name            |
      | Create | Category    | New Auditable Category |
      | Update | Category    | New Auditable Category |
  # Again update contents has random uuid and cant be examined.
