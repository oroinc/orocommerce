@regression
Feature: WYSIWYG custom code
  code example types

  Scenario: Create landing page
    Given I login as administrator
    And go to Marketing / Landing Pages
    And click "Create Landing Page"
    And I fill in Landing Page Titles field with "WYSIWYG UI page"
    And I save form

  Scenario: Add/update/delete custom code type
    When I add new component "Custom Code" from panel to editor area
    And I add "Text content" to dialog code editor with Apply Changes
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <div data-type="custom-source-code">Text content |
    Then I click on "Edit custom code" action for selected component
    And I add "<div class=\"test\"><blockquote>Test content</blockquote></div>" to dialog code editor with Apply Changes
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <div data-type="custom-source-code"> |
      | 2 | <div class="test">                   |
      | 3 | <blockquote>Test content             |
      | 4 | </blockquote>                        |
      | 5 | </div>                               |
      | 6 | </div>                               |
    Then I click on "Merge custom code into content" action for selected component
    And I click "OK" in confirmation dialogue
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <div class="test">                     |
      | 2 | <blockquote class="quote">Test content |
      | 3 | </blockquote>                          |
      | 4 | </div>                                 |
    And I select component in canvas by tree:
      | text  | 1 |
      | quote | 1 |
    Then I click on "Clone" action for selected component
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <div class="test">                     |
      | 2 | <blockquote class="quote">Test content |
      | 3 | </blockquote>                          |
      | 4 | <blockquote class="quote">Test content |
      | 5 | </blockquote>                          |
      | 6 | </div>                                 |
    And I clear canvas in WYSIWYG
    Then I add new component "Text" from panel to editor area
    And I select component in canvas by tree:
      | text | 1 |
    And I update selected component settings:
      | id | test-id |
    When I add new component "Custom Code" from panel to editor area
    And I add "<div id=\"test-id\"></div>" to dialog code editor
    And I should see "Line #1: The \"id\" attribute values that start with \"test-id\" already exist on the page, please use a different ID."
    And I close ui dialog
    And I clear canvas in WYSIWYG

  Scenario: Add/update/delete code example type
    When I add new component "Code Example" from panel to editor area
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <pre>                               |
      | 2 | <code>Type code example here</code> |
      | 3 | </pre>                              |
    And I select component in canvas by tree:
      | code | 1 |
    And I enter to edit mode "SelectedComponent" component in canvas
    And I add "<div class=\"test\"><blockquote>Test content</blockquote></div>" to dialog code editor with Save
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <pre>                                                                                                        |
      | 2 | <code>&lt;div class=&quot;test&quot;&gt;&lt;blockquote&gt;Test content&lt;/blockquote&gt;&lt;/div&gt;</code> |
      | 3 | </pre>                                                                                                       |
    And I select component in canvas by tree:
      | code | 1 |
    And I click on "Clone" action for selected component
    And I check wysiwyg content in "CMS Page Content":
      | 4 | <pre>                                                                                                        |
      | 5 | <code>&lt;div class=&quot;test&quot;&gt;&lt;blockquote&gt;Test content&lt;/blockquote&gt;&lt;/div&gt;</code> |
      | 6 | </pre>                                                                                                       |
    And I select component in canvas by tree:
      | code | 2 |
    And I enter to edit mode "SelectedComponent" component in canvas
    And I add "Cloned content" to dialog code editor with Save
    And I save form
    And I check wysiwyg content in "CMS Page Content":
      | 4 | <pre>                       |
      | 5 | <code>Cloned content</code> |
      | 6 | </pre>                      |
    And I select component in canvas by tree:
      | code | 1 |
    And I click on "Delete" action for selected component

