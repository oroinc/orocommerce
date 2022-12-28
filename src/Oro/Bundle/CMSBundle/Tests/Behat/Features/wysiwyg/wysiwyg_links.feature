@regression
Feature: WYSIWYG links
  link button
  link block types

  Scenario: Create landing page
    Given I login as administrator
    And go to Marketing / Landing Pages
    And click "Create Landing Page"
    And I fill in Landing Page Titles field with "WYSIWYG UI page"
    And I save form

  Scenario: Add/update/delete link type
    When I add new component "2 Columns" from panel to editor area
    And I add new component "Link" from panel to:
      | grid-row    | 1 |
      | grid-column | 2 |
    And I check wysiwyg content in "CMS Page Content":
      | 5 | <a class="link">Link</a> |
    And I select component in canvas by tree:
      | grid-row    | 1 |
      | grid-column | 2 |
      | link        | 1 |
    And I update selected component settings:
      | Href   | http://test-url.com |
      | Text   | Test text           |
      | Title  | Test title          |
      | Target | _blank              |
    And I check wysiwyg content in "CMS Page Content":
      | 5 | <a href="http://test-url.com" title="Test title" target="_blank" class="link">Test text</a> |
    And I update selected component "Typography" styles:
      | color | #000000 |
    And I check wysiwyg content in "CMS Page Content":
      | 28 | color:#000000; |
    And I select component in canvas by tree:
      | grid-row    | 1 |
      | grid-column | 2 |
      | link        | 1 |
    Then I click on "Delete" action for selected component
    And I check wysiwyg content in "CMS Page Content":
      | 5 | </div> |

  Scenario: Add/update/delete link button type
    And I add new component "Link Button" from panel to:
      | grid-row    | 1 |
      | grid-column | 2 |
    And I check wysiwyg content in "CMS Page Content":
      | 5 | <a class="btn btn--info">Link Button</a> |
    And I select component in canvas by tree:
      | grid-row    | 1 |
      | grid-column | 2 |
      | link-button | 1 |
    And I update selected component settings:
      | Href   | http://test-url.com |
      | Text   | Test text           |
      | Title  | Test title          |
      | Target | _blank              |
    And I check wysiwyg content in "CMS Page Content":
      | 5 | <a href="http://test-url.com" title="Test title" target="_blank" class="btn btn--info">Test text</a> |
    And I update selected component "Typography" styles:
      | color | #000000 |
    And I update selected component "Decorations" styles:
      | background-color | red |
    And I check wysiwyg content in "CMS Page Content":
      | 28 | color:#000000;        |
      | 29 | background-color:red; |
    And I add new component "Link Button" from panel to:
      | grid-row    | 1 |
      | grid-column | 1 |
    And I select component in canvas by tree:
      | grid-row    | 1 |
      | grid-column | 1 |
      | link-button | 1 |
    And I update selected component "Decorations" styles:
      | background-color | green |
    And I check wysiwyg content in "CMS Page Content":
      | 33 | background-color:green; |
    And I select component in canvas by tree:
      | grid-row    | 1 |
      | grid-column | 1 |
      | link-button | 1 |
    Then I click on "Clone" action for selected component
    And I check wysiwyg content in "CMS Page Content":
      | 3 | <a class="btn btn--info">Link Button</a> |
      | 4 | <a class="btn btn--info">Link Button</a> |
    And I save form
    And I check wysiwyg content in "CMS Page Content":
      | 3 | <a class="btn btn--info">Link Button</a>                                                             |
      | 4 | <a class="btn btn--info">Link Button</a>                                                             |
      | 7 | <a href="http://test-url.com" title="Test title" target="_blank" class="btn btn--info">Test text</a> |
    And I clear canvas in WYSIWYG

  Scenario: Add/update/delete link block type
    When I add new component "2 Columns" from panel to editor area
    And I add new component "Link Block" from panel to:
      | grid-row    | 1 |
      | grid-column | 2 |
    And I select component in canvas by tree:
      | grid-row    | 1 |
      | grid-column | 2 |
      | link-block  | 1 |
    And I update selected component settings:
      | Href   | http://test-url.com |
      | Title  | Test title          |
      | Target | _blank              |
    And I update selected component "General" styles:
      | display | block |
    And I check wysiwyg content in "CMS Page Content":
      | 5  | <a href="http://test-url.com" title="Test title" target="_blank" class="link-block"></a> |
      | 28 | display:block;                                                                           |
      | 29 | padding:5px;                                                                             |
      | 30 | min-height:50px;                                                                         |
      | 31 | min-width:50px;                                                                          |
