@ticket-BB-17552

Feature: Content Widget CRUD
  In order to manage content widgets from backoffice
  As an administrator
  I want to be able to create, view, update, delete content widgets

  Scenario: Create content widget
    Given I login as administrator
    And go to Marketing/ Content Widgets
    And click "Create Content Widget"
    And fill "Content Widget Form" with:
      | Widget Type | Copyright              |
      | Name        | copyright1             |
      | Description | copyright1_description |
      | Template    | copyright1_template    |
      | Short       | true                   |
    When I save and close form
    Then I should see "Content widget has been saved" flash message
    And I should see "Widget Type: Copyright"
    And I should see Content Widget with:
      | Name        | copyright1             |
      | Description | copyright1_description |
      | Template    | copyright1_template    |
      | Short       | Yes                    |

  Scenario: Update content widget
    When I click "Edit"
    And fill "Content Widget Form" with:
      | Description | copyright1_description2 |
      | Template    | N/A                     |
      | Short       | false                   |
    And I save and close form
    And I should see "Widget Type: Copyright"
    And I should see Content Widget with:
      | Name        | copyright1              |
      | Description | copyright1_description2 |
      | Template    | N/A                     |
      | Short       | No                      |

  Scenario: Check content widgets datagrid
    When go to Marketing/ Content Widgets
    Then there is 3 records in grid
    And I should see following grid:
      | Name       | Description             | Widget Type | Template |
      | copyright1 | copyright1_description2 | Copyright   | N/A      |
    And It should be 6 columns in grid
    And I should see "Created At" column in grid
    And I should see "Updated At" column in grid
    When click "Grid Settings"
    And I click "Filters" tab
    Then I should see following filters in the grid settings in exact order:
      | Name        |
      | Description |
      | Widget Type |
      | Template    |
      | Created At  |
      | Updated At  |
