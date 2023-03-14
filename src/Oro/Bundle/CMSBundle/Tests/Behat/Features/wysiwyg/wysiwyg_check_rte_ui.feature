@regression
Feature: WYSIWYG check RTE UI

  Scenario: Create landing page
    Given I login as administrator
    And go to Marketing/ Content Widgets
    And click "Create Content Widget"
    And fill "Content Widget Form" with:
      | Type | Copyright   |
      | Name | test-inline |
    Then I save and close form
    And go to Marketing / Landing Pages
    And click "Create Landing Page"
    And I fill in Landing Page Titles field with "WYSIWYG UI page"
    And I save form

  Scenario: Check RTE for textblock
    When I add new component "Text" from panel to editor area
    And I select component in canvas by tree:
      | text | 1 |
    And I enter "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin laoreet elit sit amet diam tincidunt, ultrices finibus libero euismod. Curabitur mollis posuere tellus, eget ullamcorper quam" text to "SelectedComponent" component
    And I select text "consectetur adipiscing elit" range in selected component
    And I apply "bold" action in RTE
    And I apply "italic" action in RTE
    And I apply "formatBlock" action with "Heading 1" in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <h1>Lorem ipsum dolor sit amet,                                                                                                           |
      | 2 | <b>                                                                                                                                       |
      | 3 | <i>consectetur adipiscing elit                                                                                                            |
      | 4 | </i>                                                                                                                                      |
      | 5 | </b>. Proin laoreet elit sit amet diam tincidunt, ultrices finibus libero euismod. Curabitur mollis posuere tellus, eget ullamcorper quam |
      | 6 | </h1>                                                                                                                                     |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I select text "amet diam tincidunt" range in selected component
    And I click "RteItalicAction"
    And I check wysiwyg content in "CMS Page Content":
      | 6 | <i>amet diam tincidunt                                                                        |
      | 7 | </i>, ultrices finibus libero euismod. Curabitur mollis posuere tellus, eget ullamcorper quam |
    Then I select component in canvas by tree:
      | text | 1 |
    And I click "WysiwygToolbarActionDelete"
    And I clear canvas in WYSIWYG
    And I save form

  Scenario: Check Text component
    When I add new component "Text" from panel to editor area
    And I select component in canvas by tree:
      | text | 1 |
    And I enter "Lorem ipsum dolor sit amet, consectetur adipiscing elit." text to "SelectedComponent" component
    And I select text "consectetur adipiscing elit" range in selected component
    And I apply "bold" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 2 | <b>consectetur adipiscing elit |
      | 3 | </b>.                          |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I select text "dolor sit amet" range in selected component
    And I apply "italic" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 2 | <i>dolor sit amet |
      | 3 | </i>,             |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I select text "Lorem" range in selected component
    And I apply "insertOrderedList" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <ol>                           |
      | 2 | <li>Lorem ipsum                |
      | 3 | <i>dolor sit amet              |
      | 4 | </i>,                          |
      | 5 | <b>consectetur adipiscing elit |
      | 6 | </b>.                          |
      | 7 | </li>                          |
      | 8 | </ol>                          |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I select all text in selected component
    And I apply "insertOrderedList" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 2 | <i>dolor sit amet |
      | 3 | </i>,             |
    And I clear canvas in WYSIWYG
    And I save form

  Scenario: Check list in text
    When I add new component "Text" from panel to editor area
    And I select component in canvas by tree:
      | text | 1 |
    And I enter "Lorem ipsum dolor sit amet, consectetur adipiscing elit." text to "SelectedComponent" component
    And I put caret after "sit amet," in selected component
    And I press "Enter" key on "SelectedComponent" element in canvas
    And I put caret after "consectetur " in selected component
    And I press "Enter" key on "SelectedComponent" element in canvas
    And I select all text in selected component
    And I apply "insertOrderedList" action in RTE
    And I select text "consectetur" range in selected component
    Then I press "Tab" key on "SelectedComponent" element in canvas
    And I check wysiwyg content in "CMS Page Content":
      | 1  | <ol>                            |
      | 2  | <li>Lorem ipsum dolor sit amet, |
      | 3  | <ol>                            |
      | 4  | <li> consectetur                |
      | 5  | </li>                           |
      | 6  | </ol>                           |
      | 7  | </li>                           |
      | 8  | <li>adipiscing elit.            |
      | 9  | </li>                           |
      | 10 | </ol>                           |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I select text "consectetur" range in selected component
    And I apply "insertUnorderedList" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1  | <ul>  |
      | 3  | <ul>  |
      | 6  | </ul> |
      | 10 | </ul> |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I select text "consectetur" range in selected component
    And I press "Shift+Tab" key on "SelectedComponent" element in canvas
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <ul>                            |
      | 2 | <li>Lorem ipsum dolor sit amet, |
      | 3 | </li>                           |
      | 4 | <li> consectetur                |
      | 5 | </li>                           |
      | 6 | <li>adipiscing elit.            |
      | 7 | </li>                           |
      | 8 | </ul>                           |
    And I clear canvas in WYSIWYG
    And I save form

  Scenario: Check text formats
    When I add new component "Text" from panel to editor area
    And I select component in canvas by tree:
      | text | 1 |
    And I enter "Lorem ipsum dolor sit amet" text to "SelectedComponent" component
    And I put caret after "sit" in selected component
    And I apply "formatBlock" action with "Heading 1" in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <h1>Lorem ipsum dolor sit amet |
      | 2 | </h1>                          |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I put caret after "sit" in selected component
    And I apply "formatBlock" action with "Heading 2" in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <h2>Lorem ipsum dolor sit amet |
      | 2 | </h2>                          |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I put caret after "sit" in selected component
    And I apply "formatBlock" action with "Heading 3" in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <h3>Lorem ipsum dolor sit amet |
      | 2 | </h3>                          |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I put caret after "sit" in selected component
    And I apply "formatBlock" action with "Heading 4" in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <h4>Lorem ipsum dolor sit amet |
      | 2 | </h4>                          |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I put caret after "sit" in selected component
    And I apply "formatBlock" action with "Heading 5" in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <h5>Lorem ipsum dolor sit amet |
      | 2 | </h5>                          |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I put caret after "sit" in selected component
    And I apply "formatBlock" action with "Heading 6" in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <h6>Lorem ipsum dolor sit amet |
      | 2 | </h6>                          |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I put caret after "sit" in selected component
    And I apply "formatBlock" action with "Paragraph" in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <p>Lorem ipsum dolor sit amet |
      | 2 | </p>                          |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I put caret after "sit" in selected component
    And I apply "formatBlock" action with "Normal Text" in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <div>Lorem ipsum dolor sit amet |
      | 2 | </div>                          |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I put caret after "sit" in selected component
    And I apply "formatBlock" action with "Heading 1" in RTE
    And I select text "ipsum dolor" range in selected component
    And I apply "bold" action in RTE
    And I apply "italic" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <h1>Lorem            |
      | 2 | <span><i>ipsum dolor |
      | 3 | </i></span> sit amet |
      | 4 | </h1>                |
    And I clear canvas in WYSIWYG
    And I save form

  Scenario: Check create link in the text
    When I add new component "Text" from panel to editor area
    And I select component in canvas by tree:
      | text | 1 |
    And I enter "Lorem ipsum dolor sit amet" text to "SelectedComponent" component
    And I select text "ipsum dolor" range in selected component
    And I apply "link" action in RTE
    And I type "https://test.link" in "HrefField"
    And I type "Link" in "TitleField"
    Then I click "Insert"
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <div>Lorem                                                                                    |
      | 2 | <a href="https://test.link" title="Link" target="_self" class="link">ipsum dolor</a> sit amet |
      | 3 | </div>                                                                                        |
    And I click "GrapesJs Wysiwyg"
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I select text "ipsum dolor" range in selected component
    And I apply "link" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <div>Lorem ipsum dolor sit amet |
      | 2 | </div>                          |
    And I clear canvas in WYSIWYG
    And I save form

  Scenario: Check h1-h6, apply the list, change type of the list, turn off the list
    When I add new component "Text" from panel to editor area
    And I select component in canvas by tree:
      | text | 1 |
    And I enter "Heading 1 Heading 2 Heading 3 Heading 4 Heading 5 Heading 6" text to "SelectedComponent" component
    And I make line break after "Heading 1 " in current editing
    And I make line break after "Heading 2 " in current editing
    And I make line break after "Heading 3 " in current editing
    And I make line break after "Heading 4 " in current editing
    And I make line break after "Heading 5 " in current editing
    Then I select text "Heading 1" range in selected component
    And I apply "formatBlock" action with "Heading 1" in RTE
    Then I select text "Heading 2" range in selected component
    And I apply "formatBlock" action with "Heading 2" in RTE
    Then I select text "Heading 3" range in selected component
    And I apply "formatBlock" action with "Heading 3" in RTE
    Then I select text "Heading 4" range in selected component
    And I apply "formatBlock" action with "Heading 4" in RTE
    Then I select text "Heading 5" range in selected component
    And I apply "formatBlock" action with "Heading 5" in RTE
    Then I select text "Heading 6" range in selected component
    And I apply "formatBlock" action with "Heading 6" in RTE
    Then I select all text in selected component
    And I apply "insertUnorderedList" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1  | <ul>          |
      | 2  | <li>          |
      | 3  | <h1>Heading 1 |
      | 4  | </h1>         |
      | 5  | </li>         |
      | 6  | <li>          |
      | 7  | <h2>Heading 2 |
      | 8  | </h2>         |
      | 9  | </li>         |
      | 10 | <li>          |
      | 11 | <h3>Heading 3 |
      | 12 | </h3>         |
      | 13 | </li>         |
      | 14 | <li>          |
      | 15 | <h4>Heading 4 |
      | 16 | </h4>         |
      | 17 | </li>         |
      | 18 | <li>          |
      | 19 | <h5>Heading 5 |
      | 20 | </h5>         |
      | 21 | </li>         |
      | 22 | <li>          |
      | 23 | <h6>Heading 6 |
      | 24 | </h6>         |
      | 25 | </li>         |
      | 26 | </ul>         |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    Then I select text "Heading 5" range in selected component
    And I apply "insertOrderedList" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1  | <ol>          |
      | 3  | <h1>Heading 1 |
      | 7  | <h2>Heading 2 |
      | 11 | <h3>Heading 3 |
      | 15 | <h4>Heading 4 |
      | 19 | <h5>Heading 5 |
      | 23 | <h6>Heading 6 |
      | 26 | </ol>         |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    Then I select text "Heading 5" range in selected component
    And I apply "insertOrderedList" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1  | <div>         |
      | 2  | <h1>Heading 1 |
      | 3  | </h1>         |
      | 4  | <h2>Heading 2 |
      | 5  | </h2>         |
      | 6  | <h3>Heading 3 |
      | 7  | </h3>         |
      | 8  | <h4>Heading 4 |
      | 9  | </h4>         |
      | 10 | <h5>Heading 5 |
      | 11 | </h5>         |
      | 12 | <h6>Heading 6 |
      | 13 | </h6>         |
      | 14 | </div>        |
    And I clear canvas in WYSIWYG
    And I save form

  Scenario: Check set subscript and superscript
    When I add new component "Text" from panel to editor area
    And I select component in canvas by tree:
      | text | 1 |
    And I enter "Lorem ipsum dolor sit amet" text to "SelectedComponent" component
    Then I select text "ipsum" range in selected component
    And I apply "subscript" action in RTE
    Then I select text "sit" range in selected component
    And I apply "superscript" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <div>Lorem             |
      | 2 | <sub>ipsum</sub> dolor |
      | 3 | <sup>sit</sup> amet    |
      | 4 | </div>                 |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    Then I select text "ipsum" range in selected component
    And I apply "subscript" action in RTE
    Then I select text "sit" range in selected component
    And I apply "superscript" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <div>Lorem ipsum dolor sit amet |
      | 2 | </div>                          |
    And I clear canvas in WYSIWYG
    And I save form

  Scenario: Check 'Text Style' for 1 word
    When I add new component "Text" from panel to editor area
    And I select component in canvas by tree:
      | text | 1 |
    And I enter "Lorem ipsum dolor sit amet" text to "SelectedComponent" component
    Then I select text "ipsum" range in selected component
    And I apply "wrap" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <div>Lorem                                               |
      | 2 | <span data-type="text-style">ipsum</span> dolor sit amet |
      | 3 | </div>                                                   |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    Then I select text "ipsum" range in selected component
    And I apply "wrap" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <div>Lorem ipsum dolor sit amet |
      | 2 | </div>                          |
    And I clear canvas in WYSIWYG
    And I save form

  Scenario: Check 'Text Style' for 1 line, add new line, apply the list for this lines
    When I add new component "Text" from panel to editor area
    And I select component in canvas by tree:
      | text | 1 |
    And I enter "Lorem ipsum, dolor sit amet consectetur adipisicing elit." text to "SelectedComponent" component
    Then I select text "dolor sit amet" range in selected component
    And I apply "wrap" action in RTE
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I put caret after "dolor sit" in selected component
    And I press "Enter" key on "SelectedComponent" element in canvas
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <div>Lorem ipsum,                                                                     |
      | 2 | <span data-type="text-style">dolor sit<br/> amet</span> consectetur adipisicing elit. |
      | 3 | </div>                                                                                |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    Then I select all text in selected component
    And I apply "insertOrderedList" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <ol>                                                                                  |
      | 2 | <li>Lorem ipsum,                                                                      |
      | 3 | <span data-type="text-style">dolor sit<br/> amet</span> consectetur adipisicing elit. |
      | 4 | </li>                                                                                 |
      | 5 | </ol>                                                                                 |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I put caret after "dolor sit" in selected component
    And I apply "wrap" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <ol>                                     |
      | 2 | <li>Lorem ipsum, dolor sit               |
      | 3 | <br/> amet consectetur adipisicing elit. |
      | 4 | </li>                                    |
      | 5 | </ol>                                    |
    And I clear canvas in WYSIWYG
    And I save form

  Scenario: Check indent
    When I add new component "Text" from panel to editor area
    And I select component in canvas by tree:
      | text | 1 |
    And I enter "Lorem ipsum, dolor sit amet consectetur adipisicing elit." text to "SelectedComponent" component
    Then I select text "dolor sit amet" range in selected component
    And I apply "formatBlock" action with "Heading 2" in RTE
    And I apply "indent" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <h2 style="padding-left: 40px;">Lorem ipsum, dolor sit amet consectetur adipisicing elit. |
      | 2 | </h2>                                                                                     |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I select text "dolor sit amet" range in selected component
    And I apply "formatBlock" action with "Heading 4" in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <h4 style="padding-left: 40px;">Lorem ipsum, dolor sit amet consectetur adipisicing elit. |
      | 2 | </h4>                                                                                     |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I select text "dolor sit amet" range in selected component
    And I apply "formatBlock" action with "Paragraph" in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <p style="padding-left: 40px;">Lorem ipsum, dolor sit amet consectetur adipisicing elit. |
      | 2 | </p>                                                                                     |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I select text "dolor sit amet" range in selected component
    And I apply "outdent" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. |
      | 2 | </p>                                                         |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I enter "Lorem ipsum, dolor sit amet consectetur adipisicing elit." text to "SelectedComponent" component
    And I clear canvas in WYSIWYG
    When I add new component "Text" from panel to editor area
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    And I apply "bold" action in RTE
    And I apply "italic" action in RTE
    And I apply "indent" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <p style="padding-left: 40px;"> |
      | 2 | <b>                             |
      | 3 | <i>Insert your text here        |
      | 4 | </i>                            |
      | 5 | </b>                            |
      | 6 | </p>                            |
    And I clear canvas in WYSIWYG
    And I save form

  Scenario: Check indent in list
    When I add new component "Text" from panel to editor area
    And I select component in canvas by tree:
      | text | 1 |
    And I enter "Lorem ipsum, dolor sit amet consectetur adipisicing elit." text to "SelectedComponent" component
    Then I put caret after "ipsum," in selected component
    And I press "Enter" key on "SelectedComponent" element in canvas
    Then I put caret after "amet" in selected component
    And I press "Enter" key on "SelectedComponent" element in canvas
    Then I put caret after "consectetur" in selected component
    And I press "Enter" key on "SelectedComponent" element in canvas
    And I select all text in selected component
    And I apply "insertOrderedList" action in RTE
    Then I select text "amet" range in selected component
    And I apply "indent" action in RTE
    Then I select text "consectetur" range in selected component
    And I apply "indent" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1  | <ol>                   |
      | 2  | <li>Lorem ipsum,       |
      | 3  | <ol>                   |
      | 4  | <li> dolor sit amet    |
      | 5  | </li>                  |
      | 6  | <li> consectetur       |
      | 7  | </li>                  |
      | 8  | </ol>                  |
      | 9  | </li>                  |
      | 10 | <li> adipisicing elit. |
      | 11 | </li>                  |
      | 12 | </ol>                  |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    Then I put caret before "ctetur" in selected component
    And I apply "outdent" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1  | <ol>                   |
      | 2  | <li>Lorem ipsum,       |
      | 3  | <ol>                   |
      | 4  | <li> dolor sit amet    |
      | 5  | </li>                  |
      | 6  | </ol>                  |
      | 7  | </li>                  |
      | 8  | <li> consectetur       |
      | 9  | </li>                  |
      | 10 | <li> adipisicing elit. |
      | 11 | </li>                  |
      | 12 | </ol>                  |
    And I select component in canvas by tree:
      | text | 1 |
    Then I enter to edit mode "SelectedComponent" component in canvas
    Then I put caret before "sit amet" in selected component
    And I apply "outdent" action in RTE
    And I check wysiwyg content in "CMS Page Content":
      | 1  | <ol>                   |
      | 2  | <li>Lorem ipsum,       |
      | 3  | </li>                  |
      | 4  | <li> dolor sit amet    |
      | 5  | </li>                  |
      | 6  | <li> consectetur       |
      | 7  | </li>                  |
      | 8  | <li> adipisicing elit. |
      | 9  | </li>                  |
      | 10 | </ol>                  |
    And I clear canvas in WYSIWYG
    And I save form

  Scenario: Check inline content widget
    When I add new component "Text" from panel to editor area
    And I select component in canvas by tree:
      | text | 1 |
    And I enter "Lorem ipsum, dolor sit amet consectetur adipisicing elit." text to "SelectedComponent" component
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <div>Lorem ipsum, dolor sit amet consectetur adipisicing elit. |
      | 2 | </div>                                                         |
    And I select component in canvas by tree:
      | text | 1 |
    And I enter to edit mode "SelectedComponent" component in canvas
    Then I select text "ipsum," range in selected component
    And I apply "inlineWidget" action in RTE
    And I click on test-inline in grid
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <div>Lorem                                                                                                                                                         |
      | 2 | <span data-title="test-inline" data-type="copyright" class="content-widget-inline">{{ widget("test-inline") }}</span> dolor sit amet consectetur adipisicing elit. |
      | 3 | </div>                                                                                                                                                             |
    And I save form
    And I select component in canvas by tree:
      | text | 1 |
    Then I select "InlineContentWidget" component in canvas
    And I click "WysiwygToolbarActionDelete"
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <div>Lorem  dolor sit amet consectetur adipisicing elit. |
      | 2 | </div>                                                   |
    And I clear canvas in WYSIWYG
    And I save form
