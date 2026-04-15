@regression
Feature: WYSIWYG map type component

  Scenario: Create landing page
    Given I login as administrator
    And go to Marketing / Landing Pages
    And click "Create Landing Page"
    And I fill in Landing Page Titles field with "WYSIWYG UI page"
    And I save form

  Scenario: Add/Update/Delete map type component
    When I add new component "Map" from panel to editor area
    Then I check wysiwyg content in "CMS Page Content":
      | 1 | <iframe frameborder="0" src="https://maps.google.com/maps?&amp;z=1&amp;t=q&amp;output=embed"></iframe> |
    And I select component in canvas by tree:
      | map | 1 |
    And I update selected component settings:
      | Address  | London    |
      | Map type | Satellite |
      | Zoom     | 10        |
    Then I check wysiwyg content in "CMS Page Content":
      | 1 | <iframe frameborder="0" src="https://maps.google.com/maps?&amp;q=London&amp;z=10&amp;t=w&amp;output=embed"></iframe> |
    And I add new component "2 Columns" from panel to editor area
    And I select component in canvas by tree:
      | map | 1 |
    And I click on "Clone" action for selected component
    Then WYSIWYG "CMS Page Content" contains "https://maps.google.com/maps" 2 times
    And I select component in canvas by tree:
      | map | 2 |
    And I move "SelectedComponent" to "FirstColumnInGrid" in editor canvas
    Then I check wysiwyg content in "CMS Page Content":
      | 3 | <div class="grid-cell">                                                                              |
      | 4 | <iframe frameborder="0" src="https://maps.google.com/maps?&amp;q=London&amp;z=10&amp;t=w&amp;output=embed"></iframe> |
      | 5 | </div>                                                                                               |
    And I select component in canvas by tree:
      | grid-row    | 1 |
      | grid-column | 1 |
      | map         | 1 |
    And I click on "Delete" action for selected component
    Then WYSIWYG "CMS Page Content" contains "https://maps.google.com/maps" 1 time
