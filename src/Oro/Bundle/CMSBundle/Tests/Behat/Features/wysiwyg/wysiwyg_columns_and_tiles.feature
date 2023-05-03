@regression
Feature: WYSIWYG columns and tiles

  Scenario: Create landing page
    Given I login as administrator
    And go to Marketing / Landing Pages
    And click "Create Landing Page"
    And I fill in Landing Page Titles field with "WYSIWYG UI page"
    And I save form

  Scenario: Add new columns into canvas
    When I add new component "Columns" from panel to editor area
    And I click "3ColumnsPreset"
    Then I check wysiwyg content in "CMS Page Content":
      | 1  | <div class="grid">     |
      | 2  | <div class="grid-col"> |
      | 3  | </div>                 |
      | 4  | <div class="grid-col"> |
      | 5  | </div>                 |
      | 6  | <div class="grid-col"> |
      | 7  | </div>                 |
      | 8  | </div>                 |
      | 10 | --grid-column-count:3; |
    And I update selected component "Columns Settings" styles:
      | --grid-column-count | 6  |
      | --grid-row-gap      | 20 |
    Then I check wysiwyg content in "CMS Page Content":
      | 10 | --grid-column-count:6; |
      | 11 | --grid-gap:20px 16px;  |
    And I add new component "Div Block" by click from panel to:
      | columns | 1 |
    And I add new component "Div Block" by click from panel to:
      | columns | 1 |
    And I add new component "Div Block" by click from panel to:
      | columns | 1 |
    Then I check wysiwyg content in "CMS Page Content":
      | 1  | <div class="grid">     |
      | 2  | <div class="grid-col"> |
      | 3  | </div>                 |
      | 4  | <div class="grid-col"> |
      | 5  | </div>                 |
      | 6  | <div class="grid-col"> |
      | 7  | </div>                 |
      | 8  | <div class="grid-col"> |
      | 9  | </div>                 |
      | 10 | <div class="grid-col"> |
      | 11 | </div>                 |
      | 12 | <div class="grid-col"> |
      | 13 | </div>                 |
      | 14 | </div>                 |
    And I save form

  Scenario: Change column span
    When I select component in canvas by tree:
      | columns      | 1 |
      | columns-item | 1 |
    And I update selected component settings:
      | id | column-test-1 |
    And I update selected component "Columns Item Settings" styles:
      | --grid-column-span | 3 |
    And I select component in canvas by tree:
      | columns      | 1 |
      | columns-item | 2 |
    And I update selected component settings:
      | id | column-test-2 |
    And I update selected component "Columns Item Settings" styles:
      | --grid-column-span | 3 |
    And I select component in canvas by tree:
      | columns      | 1 |
      | columns-item | 3 |
    And I update selected component settings:
      | id | column-test-3 |
    And I update selected component "Columns Item Settings" styles:
      | --grid-column-span | 6 |
    And I select component in canvas by tree:
      | columns      | 1 |
      | columns-item | 4 |
    And I update selected component settings:
      | id | column-test-4 |
    And I update selected component "Columns Item Settings" styles:
      | --grid-column-span | 2 |
    And I select component in canvas by tree:
      | columns      | 1 |
      | columns-item | 5 |
    And I update selected component settings:
      | id | column-test-5 |
    And I update selected component "Columns Item Settings" styles:
      | --grid-column-span | 2 |
    And I select component in canvas by tree:
      | columns      | 1 |
      | columns-item | 6 |
    And I update selected component settings:
      | id | column-test-6 |
    And I update selected component "Columns Item Settings" styles:
      | --grid-column-span | 2 |
    Then I check wysiwyg content in "CMS Page Content":
      | 19 | #column-test-1{       |
      | 20 | --grid-column-span:3; |
      | 22 | #column-test-2{       |
      | 23 | --grid-column-span:3; |
      | 25 | #column-test-3{       |
      | 26 | --grid-column-span:6; |
      | 28 | #column-test-4{       |
      | 29 | --grid-column-span:2; |
      | 31 | #column-test-5{       |
      | 32 | --grid-column-span:2; |
      | 34 | #column-test-6{       |
      | 35 | --grid-column-span:2; |

  Scenario: Add sub columns
    When I select component in canvas by tree:
      | columns | 1 |
      | columns-item | 3 |
    And I click on "Delete" action for selected component
    And I add new component "Columns" by click from panel to:
      | columns | 1 |
    And I click "3ColumnsPreset"
    And I select component in canvas by tree:
      | columns | 1 |
      | columns | 1 |
    And I update selected component settings:
      | id | sub-columns-test |
    And I update selected component "Sub Columns Settings" styles:
      | --grid-column-span  | 6 |
      | --grid-column-count | 2 |
    And I select component in canvas by tree:
      | columns      | 1 |
      | columns      | 1 |
      | columns-item | 3 |
    And I update selected component settings:
      | id | sub-columns-item-test |
    And I update selected component "Columns Item Settings" styles:
      | --grid-column-span | 2 |
    Then I check wysiwyg content in "CMS Page Content":
      | 40 | #sub-columns-test{      |
      | 41 | --grid-column-count:2;  |
      | 42 | --grid-column-span:6;   |
      | 44 | #sub-columns-item-test{ |
      | 45 | --grid-column-span:2;   |
    And I click "GrapesJs Wysiwyg"
    And I select component in canvas by tree:
      | columns | 1 |
    And  I click on "Clone" action for selected component
    Then I check wysiwyg content in "CMS Page Content":
      | 32 | <div class="grid grid-col"> |
      | 33 | <div class="grid-col">      |
    And I clear canvas in WYSIWYG

  Scenario: Add sample UI component to columns
    When I add new component "Columns" from panel to editor area
    And I click "1ColumnsPreset"
    And I select component in canvas by tree:
      | columns      | 1 |
      | columns-item | 1 |
    And I click on "Delete" action for selected component
    And I click "GrapesJs Wysiwyg"
    And I add new component "Text" by click from panel to:
      | columns | 1 |
    And I click "GrapesJs Wysiwyg"
    And I add new component "Quote" by click from panel to:
      | columns | 1 |
    Then I check wysiwyg content in "CMS Page Content":
      | 2 | <div class="grid-col">                                                                                                                        |
      | 3 | <div>Insert your text here                                                                                                                    |
      | 6 | <div class="grid-col">                                                                                                                        |
      | 7 | <blockquote class="quote">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore ipsum dolor sit |
    And I clear canvas in WYSIWYG

  Scenario: Add new Tiles
    When I add new component "Tiles" from panel to editor area
    And I click "3TilesPreset"
    And I update selected component "Tiles Settings" styles:
      | --tiles-column-count | 6 |
    Then I check wysiwyg content in "CMS Page Content":
      | 1  | <div class="tiles">      |
      | 2  | <div class="tiles-item"> |
      | 3  | </div>                   |
      | 4  | <div class="tiles-item"> |
      | 5  | </div>                   |
      | 6  | <div class="tiles-item"> |
      | 7  | </div>                   |
      | 8  | </div>                   |
      | 10 | --tiles-column-count:6;  |
    And I add new component "Div Block" by click from panel to:
      | tiles | 1 |
    And I add new component "Div Block" by click from panel to:
      | tiles | 1 |
    And I add new component "Div Block" by click from panel to:
      | tiles | 1 |
    Then I check wysiwyg content in "CMS Page Content":
      | 8  | <div class="tiles-item"> |
      | 10 | <div class="tiles-item"> |
      | 12 | <div class="tiles-item"> |
    And I add new component "Text" by click from panel to:
      | tiles | 1 |
    Then I check wysiwyg content in "CMS Page Content":
      | 14 | <div class="tiles-item">   |
      | 15 | <div>Insert your text here |
    And I save form
    And I clear canvas in WYSIWYG

  Scenario: Import columns
    When I import content "<div class=\"grid\"><div class=\"grid-col-3 grid-col-mobile-landscape-12\"></div><div class=\"grid-col-9 grid-col-tablet-5 grid-col-mobile-landscape-12\"></div></div>" to "CMS Page Content" WYSIWYG editor
    And I select component in canvas by tree:
      | columns | 1 |
    And I select component in canvas by tree:
      | columns      | 1 |
      | columns-item | 1 |
    And I update selected component "Columns Item Settings" styles:
      | --grid-column-span | 2 |
    Then I check wysiwyg content in "CMS Page Content":
      | 2 | <div class="grid-col-mobile-landscape-12 grid-col"> |
    And I click "EditorDeviceMobileLandscape"
    And I select component in canvas by tree:
      | columns      | 1 |
      | columns-item | 1 |
    And I update selected component "Columns Item Settings" styles:
      | --grid-column-span | 6 |
    Then I check wysiwyg content in "CMS Page Content":
      | 2  | <div class="grid-col">     |
      | 10 | @media (max-width: 640px){ |
      | 12 | --grid-column-span:6;      |
    And I select component in canvas by tree:
      | columns      | 1 |
      | columns-item | 2 |
    And I click "EditorDeviceTablet"
    And I update selected component "Columns Item Settings" styles:
      | --grid-column-span | 6 |
    And I click "EditorDeviceMobileLandscape"
    And I select component in canvas by tree:
      | columns      | 1 |
      | columns-item | 2 |
    Then I update selected component "Columns Item Settings" styles:
      | --grid-column-span | 9 |
    And I click "EditorDeviceDesktop"
    And I select component in canvas by tree:
      | columns      | 1 |
      | columns-item | 2 |
    Then I update selected component "Columns Item Settings" styles:
      | --grid-column-span | 3 |
    Then I check wysiwyg content in "CMS Page Content":
      | 2  | <div class="grid-col">      |
      | 4  | <div class="grid-col">      |
      | 8  | --grid-column-span:2;       |
      | 11 | --grid-column-span:3;       |
      | 13 | @media (max-width: 1099px){ |
      | 15 | --grid-column-span:6;       |
      | 18 | @media (max-width: 640px){  |
      | 20 | --grid-column-span:6;       |
      | 23 | --grid-column-span:9;       |
