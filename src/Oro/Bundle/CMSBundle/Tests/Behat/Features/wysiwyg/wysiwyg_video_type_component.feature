@regression
Feature: WYSIWYG video type component

  Scenario: Create landing page
    Given I login as administrator
    And go to Marketing / Landing Pages
    And click "Create Landing Page"
    And I fill in Landing Page Titles field with "WYSIWYG UI page"
    And I save form

  Scenario: Add/Update/Delete video type component
    When I add new component "Video" from panel to editor area
    And I select component in canvas by tree:
      | video | 1 |
    And I update selected component settings:
      | Source   | http://test-url.com/video  |
      | Poster   | http://test-url.com/poster |
      | Autoplay | true                       |
      | Loop     | true                       |
    And I save form
    Then I check wysiwyg content in "CMS Page Content":
      | 1 | <video src="http://test-url.com/video" poster="http://test-url.com/poster" loop="loop" autoplay="autoplay" controls="controls"> |
      | 2 | </video>                                                                                                                                                          |
      | 4 | height:400px;                                                                                                                                                     |
      | 5 | width:100%;                                                                                                                                                       |
    And I select component in canvas by tree:
      | video | 1 |
    And I update selected component settings:
      | Provider       | Youtube                      |
      | Video ID       | https://youtu.be/dQw4w9WgXcQ |
      | Modestbranding | true                         |
    Then I check wysiwyg content in "CMS Page Content":
      | 1 | <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ?&autoplay=1&loop=1&playlist=dQw4w9WgXcQ&modestbranding=1&mute=1" allowfullscreen="allowfullscreen"></iframe> |
    And I select component in canvas by tree:
      | video | 1 |
    And I update selected component settings:
      | Provider | Youtube (no cookie) |
      | Video ID | dQw4w9WgXcQ         |
    Then I check wysiwyg content in "CMS Page Content":
      | 1 | <iframe src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?&autoplay=1&loop=1&playlist=dQw4w9WgXcQ&modestbranding=1&mute=1" allowfullscreen="allowfullscreen"></iframe> |
    And I select component in canvas by tree:
      | video | 1 |
    And I update selected component settings:
      | Provider | Vimeo    |
      | Video ID | 38195013 |
    Then I check wysiwyg content in "CMS Page Content":
      | 1 | <iframe src="https://player.vimeo.com/video/38195013?&autoplay=1&loop=1&muted=1" allowfullscreen="allowfullscreen"></iframe> |
    And I add new component "2 Columns" from panel to editor area
    And I select component in canvas by tree:
      | video | 1 |
    And I click on "Clone" action for selected component
    And I select component in canvas by tree:
      | video | 2 |
    And I move "SelectedComponent" to "FirstColumnInGrid" in editor canvas
    Then I check wysiwyg content in "CMS Page Content":
      | 3 | <div class="grid-cell">                                                                                                      |
      | 4 | <iframe src="https://player.vimeo.com/video/38195013?&autoplay=1&loop=1&muted=1" allowfullscreen="allowfullscreen"></iframe> |
      | 5 | </div>                                                                                                                       |
    And I select component in canvas by tree:
      | grid-row    | 1 |
      | grid-column | 1 |
      | video       | 1 |
    And I click on "Delete" action for selected component
    Then I check wysiwyg content in "CMS Page Content":
      | 2 | <div class="grid-row">  |
      | 3 | <div class="grid-cell"> |
      | 4 | </div>                  |
