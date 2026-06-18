@regression
Feature: WYSIWYG links
  link type with style variants (link, button)
  and container mode

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

  Scenario: Switch link style to button and update settings
    And I add new component "Link" from panel to:
      | grid-row    | 1 |
      | grid-column | 2 |
    And I select component in canvas by tree:
      | grid-row    | 1 |
      | grid-column | 2 |
      | link        | 1 |
    And I update selected component settings:
      | Link Style | button      |
      | Text       | Link Button |
    And I check wysiwyg content in "CMS Page Content":
      | 5 | <a class="btn">Link Button</a> |
    And I update selected component settings:
      | Href   | http://test-url.com |
      | Text   | Test text           |
      | Title  | Test title          |
      | Target | _blank              |
    And I check wysiwyg content in "CMS Page Content":
      | 5 | <a href="http://test-url.com" title="Test title" target="_blank" class="btn">Test text</a> |
    And I update selected component settings:
      | Button Style | btn--outlined |
    And I check wysiwyg content in "CMS Page Content":
      | 5 | <a href="http://test-url.com" title="Test title" target="_blank" class="btn btn--outlined">Test text</a> |
    And I update selected component settings:
      | Button Style | btn--plain |
    And I check wysiwyg content in "CMS Page Content":
      | 5 | <a href="http://test-url.com" title="Test title" target="_blank" class="btn btn--plain">Test text</a> |
    And I update selected component settings:
      | Button Style |  |
    And I check wysiwyg content in "CMS Page Content":
      | 5 | <a href="http://test-url.com" title="Test title" target="_blank" class="btn">Test text</a> |
    And I update selected component settings:
      | Icon Enabled | true |
    And I check wysiwyg content in "CMS Page Content":
      | 5 | <a href="http://test-url.com" title="Test title" target="_blank" class="btn">Test text{{ widget_icon("add-note") }}</a> |
    And I update selected component settings:
      | Icon Before | true |
    And I check wysiwyg content in "CMS Page Content":
      | 5 | <a href="http://test-url.com" title="Test title" target="_blank" class="btn">{{ widget_icon("add-note") }}Test text</a> |
    And I update selected component settings:
      | Icon Enabled | false |
    And I check wysiwyg content in "CMS Page Content":
      | 5 | <a href="http://test-url.com" title="Test title" target="_blank" class="btn">Test text</a> |
    And I update selected component "Typography" styles:
      | color | #000000 |
    And I update selected component "Decorations" styles:
      | background-color | red |
    And I check wysiwyg content in "CMS Page Content":
      | 28 | color:#000000;        |
      | 29 | background-color:red; |
    And I add new component "Link" from panel to:
      | grid-row    | 1 |
      | grid-column | 1 |
    And I select component in canvas by tree:
      | grid-row    | 1 |
      | grid-column | 1 |
      | link        | 1 |
    And I update selected component settings:
      | Link Style | button      |
      | Text       | Link Button |
    And I update selected component "Decorations" styles:
      | background-color | green |
    And I select component in canvas by tree:
      | grid-row    | 1 |
      | grid-column | 1 |
      | link        | 1 |
    Then I click on "Clone" action for selected component
    And I check wysiwyg content in "CMS Page Content":
      | 3 | <a class="btn">Link Button</a> |
      | 4 | <a class="btn">Link Button</a> |
    And I save form
    And I check wysiwyg content in "CMS Page Content":
      | 3 | <a class="btn">Link Button</a>                                                            |
      | 4 | <a class="btn">Link Button</a>                                                            |
      | 7 | <a href="http://test-url.com" title="Test title" target="_blank" class="btn">Test text</a> |
    And I clear canvas in WYSIWYG

  Scenario: Enable container mode on link
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
      | Href           | http://test-url.com |
      | Title          | Test title          |
      | Target         | _blank              |
    And I update selected component settings:
      | Container Mode | true |
    And I check wysiwyg content in "CMS Page Content":
      | 5 | <a href="http://test-url.com" title="Test title" target="_blank" class="link"></a> |
    And I add new component "Text" from panel to:
      | grid-row    | 1 |
      | grid-column | 2 |
      | link        | 1 |
    And I check wysiwyg content in "CMS Page Content":
      | 5 | <a href="http://test-url.com" title="Test title" target="_blank" class="link"><div>Insert your text here |
      | 6 | </div></a> |
    And I select component in canvas by tree:
      | grid-row    | 1 |
      | grid-column | 2 |
      | link        | 1 |
    And I update selected component settings:
      | Container Mode | false |
    And I check wysiwyg content in "CMS Page Content":
      | 5 | <a href="http://test-url.com" title="Test title" target="_blank" class="link">Link</a> |
    And I update selected component settings:
      | Container Mode | true |
    And I check wysiwyg content in "CMS Page Content":
      | 5 | <a href="http://test-url.com" title="Test title" target="_blank" class="link"><div>Insert your text here |
      | 6 | </div></a> |
