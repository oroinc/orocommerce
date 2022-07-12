@ticket-BB-21406
Feature: Content Templates CRUD
  In order to see and create content templates
  As an Administrator
  I need to be able to manage content templates in backoffice

  Scenario: Create content template from index page without name
    Given I login as administrator
    When I go to Marketing/Content Templates
    And I click "Create Content Template"
    And fill "Content Template Form" with:
      | Name |  |
    And I save and close form
    Then I should see validation errors:
      | Name | This value should not be blank. |

  Scenario: Create content template from index page with not valid name
    When fill "Content Template Form" with:
      | Name | tttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttt |
    And I save and close form
    Then I should see validation errors:
      | Name | This value is too long. It should have 255 characters or less. |

  Scenario: Create Content template without tags
    When fill "Content Template Form" with:
      | Name    | ttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttt |
      | Enabled | true     |
    And I fill in WYSIWYG "Content Template Form Content" with "Test content"
    And I save and close form
    Then I should see "Content template has been saved" flash message
    And I should see Content Template with:
      | Name    | ttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttt |
      | Enabled | Yes      |
      | Tags    | N/A      |
    And I should see "Test content"

  Scenario: Clone content template with long name
    When I click "Clone"
    Then I should see "Entity cloned successfully." flash message
    And  I should see Content Template with:
      | Name    | ttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttt… (Copy) |
      | Enabled | No                      |
      | Tags    | N/A                     |
    And I should see "Test content"

  Scenario: Create Content template with tags
    When I go to Marketing/Content Templates
    And I click "Create Content Template"
    And I fill "Content Template Form" with:
      | Name    | TestNameWithTags |
      | Enabled | true             |
      | Tags    | [Tag1, Tag2]     |
    And I fill in WYSIWYG "Content Template Form Content" with "Test content with t"
    And I save and close form
    Then I should see "Content template has been saved" flash message
    And I should see Content Template with:
      | Name    | TestNameWithTags |
      | Enabled | Yes              |
      | Tags    | Tag1 Tag2        |
    And I should see "Test content with t"

  Scenario: Clone content template
    When I click "Clone"
    Then I should see "Entity cloned successfully." flash message
    And  I should see Content Template with:
      | Name    | TestNameWithTags (Copy) |
      | Enabled | No                      |
      | Tags    | Tag1 Tag2               |
    And I should see "Test content with t"

  Scenario: Edit content template
    When I click "Edit"
    And fill "Content Template Form" with:
      | Name    | TestName1 |
      | Enabled | true      |
      | Tags    | Tag2      |
    And I fill in WYSIWYG "Content Template Form Content" with "Updated content"
    And I save and close form
    Then I should see Content Template with:
      | Name    | TestName1 |
      | Enabled | Yes       |
      | Tags    | Tag2      |
    And I should see "Updated content"

  Scenario: Delete content template
    When I click "Delete"
    And I click "Yes, Delete" in confirmation dialogue
    Then I should see "Content Template deleted" flash message
    And I should not see "TestName1"