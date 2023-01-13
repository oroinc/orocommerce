@ticket-BB-21406
@ticket-BB-21457
@fixture-OroCMSBundle:ContentTemplateFixture.yml

Feature: Content Templates Grid
  In order to see and create content templates
  As an Administrator
  I need to be able to manage content templates from grid in backoffice

  Scenario: Check Name filter
    Given I login as administrator
    And I go to Marketing/Content Templates
    And records in grid should be 10
    When I filter Name as Contains "TestContentTemplate1"
    Then records in grid should be 2
    And image "TestContentTemplate1 Default Preview Small Image" is loaded
    When I click on "TestContentTemplate1 Default Preview Small Image"
    Then I should see an "Content Template Default Preview Original Image" element
    And I close large image preview

  Scenario: Check Enabled Yes filter
    When I reset Name filter
    Then records in grid should be 10
    When I check "Yes" in "Enabled: All" filter strictly
    Then records in grid should be 6

  Scenario: Check Enabled No filter
    When I reset "Enabled" filter
    Then records in grid should be 10
    When I check "No" in "Enabled: All" filter strictly
    Then records in grid should be 4

  Scenario: Check Created At filter
    When I reset "Enabled" filter
    Then records in grid should be 10
    When I filter Created At as between "now" and "now + 1"
    Then there are no records in grid
    When I filter Created At as between "now - 3" and "now + 1"
    Then records in grid should be 10

  Scenario: Check Updated At filter
    When I reset "Created At" filter
    Then records in grid should be 10
    When I filter Updated At as between "now + 1" and "now + 2"
    Then there are no records in grid
    When I filter Updated At as between "now - 3" and "now + 1 "
    Then records in grid should be 10

  Scenario: Sorting grid by name
    When I reset "Updated At" filter
    And sort grid by Name
    Then TestContentTemplate1 must be first record
    But when I sort grid by Name again
    Then TestContentTemplate9 must be first record

  Scenario: Sorting grid by created at
    When sort grid by Created at
    Then Created At in first row must be lower then in second row
    But when I sort grid by Created At again
    Then Created At in first row must be greater then in second row

  Scenario: Sorting grid by updated at
    When sort grid by Created at
    Then Created At in first row must be lower then in second row
    But when I sort grid by Created At again
    Then Created At in first row must be greater then in second row

  Scenario: Sorting grid by Owner
    When sort grid by Owner
    Then TestContentTemplate2 must be first record
    But when I sort grid by Owner again
    Then TestContentTemplate10 must be first record

  @skip
  Scenario: Inline edit content template tags in grid adding tag 1
    When I edit "TestContentTemplate1" Tags as "Tag1" by double click
    And I click "Save changes"
    Then I should see "Tag1" in grid

  @skip
  Scenario: Inline edit content template tags in grid adding tag 2
    When I edit "TestContentTemplate2" Tags as "Tag2" by double click
    And I click "Save changes"
    Then I should see "Tag2" in grid

  @skip
  Scenario: Check Tags filter
    When I choose filter for Tags as Is Any Of "Tag2"
    Then there is 1 record in grid
    And I reset "Tags" filter

  Scenario: Clone content template in grid
    When I click clone "TestContentTemplate1" in grid
    Then I should see "Entity cloned successfully." flash message
    And I should see "TestContentTemplate1 (Copy)" in grid

  Scenario: Edit cloned content template from grid
    When I go to Marketing/Content Templates
    And I click edit "TestContentTemplate1 (Copy)" in grid
    And fill "Content Template Form" with:
      | Owner   | John Doe              |
      | Name    | TestContentTemplate12 |
      | Enabled | true                  |
      | Tags    | Tag1                  |
    And I fill in WYSIWYG "Content Template Form Content" with "Updated TestContentTemplateGridName content"
    And I save and close form
    Then I should see "Content template has been updated" flash message
    And I should see Content Template with:
      | Name    | TestContentTemplate12 |
      | Enabled | Yes                   |
      | Tags    | Tag1                  |
    And I should see "Updated TestContentTemplateGridName content"

  Scenario: Delete cloned content template from grid
    When I go to Marketing/Content Templates
    And I click delete "TestContentTemplate12" in grid
    And I click "Yes, Delete" in confirmation dialogue
    Then I should see "Content Template deleted" flash message
    And I should not see "TestContentTemplate12"
    And number of records in "Content Template Grid" should be 10
