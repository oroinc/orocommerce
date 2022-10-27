@ticket-BB-21406
@ticket-BB-21457

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
      | Enabled | true                                                                                                                                                                                                                                                            |
    And I fill in WYSIWYG "Content Template Form Content" with "Test content"
    And I save and close form
    Then I should see "Content template has been saved" flash message
    And I should see Content Template with:
      | Name    | ttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttt |
      | Enabled | Yes                                                                                                                                                                                                                                                             |
      | Tags    | N/A                                                                                                                                                                                                                                                             |
    And I should see "Test content"

  Scenario: Clone content template with long name
    When I click "Clone"
    Then I should see "Entity cloned successfully." flash message

    When I go to Marketing/Content Templates
    And I click view "Disabled" in grid
    Then  I should see Content Template with:
      | Name    | ttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttt… (Copy) |
      | Enabled | No                                                                                                                                                                                                                                                              |
      | Tags    | N/A                                                                                                                                                                                                                                                             |
    And I should see "Test content"

  Scenario: Create Content Template with tags
    When I go to Marketing/Content Templates
    And I click "Create Content Template"
    And I fill "Content Template Form" with:
      | Name    | ContentTemplateWithTags |
      | Enabled | true                    |
      | Tags    | [Tag1, Tag2]            |
    And I fill in WYSIWYG "Content Template Form Content" with "Test content with tags"
    And I save and close form
    Then I should see "Content template has been saved" flash message
    And I should see Content Template with:
      | Name    | ContentTemplateWithTags |
      | Enabled | Yes                     |
      | Tags    | Tag1 Tag2               |
    And I should see "Test content with tags"

  Scenario: Check Content Template in search results
    When I click "Tag1"
    Then I should be on Tag Search Result page
    And I should see following search entity types:
      | Type              | N | isSelected |
      | All               | 1 | yes        |
      | Content Templates | 1 |            |
    And number of records should be 1
    And I should see following search results:
      | Title                   | Type             |
      | ContentTemplateWithTags | Content Template |
    And I should see "Created At"
    And I should see "Updated At"

  Scenario: Check preview image in the Content Template grid
    When I go to Marketing/Content Templates
    Then should see following grid:
      | Name                    | Enabled | Tags      |
      | ContentTemplateWithTags | Enabled | Tag1 Tag2 |
    And image "ContentTemplateWithTags Preview Small Image" is loaded
    And I should see picture "ContentTemplateWithTags Preview Small Picture" element
    And I remember filename of the image "ContentTemplateWithTags Preview Small Image"
    When I click on "ContentTemplateWithTags Preview Small Image"
    Then I should see picture "ContentTemplateWithTags Preview Original Picture" element
    And I close large image preview

  Scenario: Edit "ContentTemplateWithTags" content template
    When I go to Marketing/Content Templates
    And I click edit "ContentTemplateWithTags" in grid
    And I fill in WYSIWYG "Content Template Form Content" with "Updated Test content with tags"
    And I save and close form
    Then I should see Content Template with:
      | Name    | ContentTemplateWithTags |
      | Enabled | Yes                     |
      | Tags    | Tag1 Tag2               |
    And I should see "Updated Test content with tags"

  Scenario: Check preview image is updated for "ContentTemplateWithTags" content template
    When I go to Marketing/Content Templates
    Then should see following grid:
      | Name                    | Enabled | Tags      |
      | ContentTemplateWithTags | Enabled | Tag1 Tag2 |
    And image "ContentTemplateWithTags Preview Small Image" is loaded
    And I should see picture "ContentTemplateWithTags Preview Small Picture" element
    And filename of the image "ContentTemplateWithTags Preview Small Image" is not as remembered
    And I remember filename of the image "ContentTemplateWithTags Preview Small Image"
    When I click on "ContentTemplateWithTags Preview Small Image"
    Then I should see picture "ContentTemplateWithTags Preview Original Picture" element
    And I close large image preview

  Scenario: Clone content template
    When I click view "ContentTemplateWithTags" in grid
    And I click "Clone"
    Then I should see "Entity cloned successfully." flash message

  Scenario: Check cloned Content Template
    When I go to Marketing/Content Templates
    Then should see following grid:
      | Name                           | Enabled  | Tags      |
      | ContentTemplateWithTags (Copy) | Disabled | Tag1 Tag2 |
      | ContentTemplateWithTags        | Enabled  | Tag1 Tag2 |
    And image "ContentTemplateWithTags Preview Small Image" is loaded
    And image "ContentTemplateWithTags (Copy) Preview Small Image" is loaded
    And filename of the image "ContentTemplateWithTags Preview Small Image" is as remembered
    And I should see picture "ContentTemplateWithTags (Copy) Preview Small Picture" element
    And images "ContentTemplateWithTags Preview Small Image" and "ContentTemplateWithTags (Copy) Preview Small Image" have different filenames

    When I click view "ContentTemplateWithTags" in grid
    Then  I should see Content Template with:
      | Name    | ContentTemplateWithTags (Copy) |
      | Enabled | No                             |
      | Tags    | Tag1 Tag2                      |
    And I should see "Updated Test content with tags"

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

  Scenario: Check that original content template is not changed
    When I go to Marketing/Content Templates
    Then should see following grid:
      | Name                    | Enabled | Tags      |
      | TestName1               | Enabled | Tag2      |
      | ContentTemplateWithTags | Enabled | Tag1 Tag2 |
    And image "ContentTemplateWithTags Preview Small Image" is loaded
    And image "TestName1 Content Template Preview Small Image" is loaded
    And filename of the image "ContentTemplateWithTags Preview Small Image" is as remembered
    And I should see picture "TestName1 Content Template Preview Small Picture" element
    And images "ContentTemplateWithTags Preview Small Image" and "TestName1 Content Template Preview Small Image" have different filenames

    When I click view "ContentTemplateWithTags" in grid
    Then I should see Content Template with:
      | Name    | ContentTemplateWithTags |
      | Enabled | Yes                     |
      | Tags    | Tag1 Tag2               |
    And I should see "Updated Test content with tags"

  Scenario: Delete content template
    When I go to Marketing/Content Templates
    And I click view "TestName1" in grid
    And I click "Delete"
    And I click "Yes, Delete" in confirmation dialogue
    Then I should see "Content Template deleted" flash message
    And I should see following grid:
      | Name                    | Enabled | Tags      |
      | ContentTemplateWithTags | Enabled | Tag1 Tag2 |
    And I should not see "TestName1"
    And image "ContentTemplateWithTags Preview Small Image" is loaded
    And I should see picture "ContentTemplateWithTags Preview Small Picture" element
    And filename of the image "ContentTemplateWithTags Preview Small Image" is as remembered
