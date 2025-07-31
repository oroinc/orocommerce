@regression

Feature: WYSIWYG icons
  Icons Feature
  And and change icons from wysiwyg UI
  Use dialog for select icon

  Scenario: Create landing page
    Given I login as administrator
    And go to Marketing / Landing Pages
    And click "Create Landing Page"
    And I fill in Landing Page Titles field with "WYSIWYG UI page"
    And I save form

  Scenario: Add new icons
    When I add new component "Columns" from panel to editor area
    And I click "3ColumnsPreset"
    And I add new component "Icon" by click from panel to:
      | columns      | 1 |
      | columns-item | 1 |
    And I type "cloc" in "SearchIconInSettings"
    And I select "clock" icon in modal window
    Then I click "Save" in modal window
    And I update selected component settings:
      | Id | icon-id-clock |
    And I add new component "Icon" by click from panel to:
      | columns      | 1 |
      | columns-item | 2 |
    And I select "globe" icon in modal window
    Then I click "Save" in modal window
    And I update selected component settings:
      | Id | icon-id-globe |
    And I add new component "Icon" by click from panel to:
      | columns      | 1 |
      | columns-item | 3 |
    And I click "Cancel" in modal window
    And I add new component "Icon" by click from panel to:
      | columns      | 1 |
      | columns-item | 3 |
    Then I click "Save" in modal window
    And I update selected component settings:
      | Id | icon-id-add-note |
    And I click "GrapesJs Wysiwyg"
    And I check wysiwyg content in "CMS Page Content":
      | 2 | <div class="grid-col">{{ widget_icon("clock", {"id":"icon-id-clock"}) }}       |
      | 4 | <div class="grid-col">{{ widget_icon("globe", {"id":"icon-id-globe"}) }}       |
      | 6 | <div class="grid-col">{{ widget_icon("add-note", {"id":"icon-id-add-note"}) }} |
    Then I save form

  Scenario: Edit icons
    When I select component in canvas by tree:
      | columns      | 1 |
      | columns-item | 1 |
      | icon         | 1 |
    And I click on "Icon Settings" action for selected component
    And I select "eye" icon in modal window
    Then I click "Save" in modal window
    And I check wysiwyg content in "CMS Page Content":
      | 2 | <div class="grid-col">{{ widget_icon("eye", {"id":"icon-id-clock"}) }} |
    Then I select component in canvas by tree:
      | columns      | 1 |
      | columns-item | 2 |
      | icon         | 1 |
    And I update selected component settings:
      | Title | icon-id-title |
    And I check wysiwyg content in "CMS Page Content":
      | 4 | <div class="grid-col">{{ widget_icon("globe", {"id":"icon-id-globe","title":"icon-id-title"}) }} |
    Then I select component in canvas by tree:
      | columns      | 1 |
      | columns-item | 3 |
      | icon         | 1 |
    And I click on "Clone" action for selected component
    Then I select component in canvas by tree:
      | columns      | 1 |
      | columns-item | 3 |
      | icon         | 2 |
    And I update selected component settings:
      | Id | icon-id-cloned-1 |
    And I check wysiwyg content in "CMS Page Content":
      | 6 | <div class="grid-col">{{ widget_icon("add-note", {"id":"icon-id-add-note"}) }}{{ widget_icon("add-note", {"id":"icon-id-cloned-1"}) }} |
    Then I select component in canvas by tree:
      | columns      | 1 |
      | columns-item | 3 |
      | icon         | 2 |
    And I move "SelectedComponent" to "FirstColumn" in editor canvas
    And I check wysiwyg content in "CMS Page Content":
      | 2 | <div class="grid-col">{{ widget_icon("eye", {"id":"icon-id-clock"}) }}{{ widget_icon("add-note", {"id":"icon-id-cloned-1"}) }} |
      | 4 | <div class="grid-col">{{ widget_icon("globe", {"id":"icon-id-globe","title":"icon-id-title"}) }}                               |
      | 6 | <div class="grid-col">{{ widget_icon("add-note", {"id":"icon-id-add-note"}) }}                                                 |
    Then I select component in canvas by tree:
      | columns      | 1 |
      | columns-item | 1 |
      | icon         | 2 |
    And I select "copy" icon in traits
    And I check wysiwyg content in "CMS Page Content":
      | 2 | <div class="grid-col">{{ widget_icon("eye", {"id":"icon-id-clock"}) }}{{ widget_icon("copy", {"id":"icon-id-cloned-1"}) }} |

  Scenario: Import icons
    When I import content "<div>{{ widget_icon(\"eye\", {\"id\":\"icon-id-clock\"}) }}{{ widget_icon(\"not-valid\", {\"id\":\"invalid\"}) }}</div>" to "CMS Page Content" WYSIWYG editor
    Then I select component in canvas by tree:
      | default | 1 |
      | icon    | 2 |
    And I select "bookmark" icon in traits
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <div>{{ widget_icon("eye", {"id":"icon-id-clock"}) }}{{ widget_icon("bookmark", {"id":"invalid"}) }} |
