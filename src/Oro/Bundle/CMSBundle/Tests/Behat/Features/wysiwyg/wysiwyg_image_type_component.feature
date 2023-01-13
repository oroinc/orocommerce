@regression
Feature: WYSIWYG image type component

  Scenario: Create landing page
    Given I login as administrator
    And go to Marketing / Landing Pages
    And click "Create Landing Page"
    And I fill in Landing Page Titles field with "WYSIWYG UI page"
    And I save form

  Scenario: Add new image into canvas
    When I add new component "Image" from panel to editor area
    And I click on promo-slider-small-6.jpg in grid
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <picture>                         |
      | 2 | <source type="image/webp"/>       |
      | 3 | <img alt="promo-slider-small-6"/> |
      | 4 | </picture>                        |
    And I click "WysiwygToolbarActionImageSettings"
    Then I click "Add Source"
    And I click on promo-slider-medium-5.jpg in grid
    Then I click "Add Source"
    And I click on promo-slider-small-4.jpg in grid
    And I click "BreakpointDropdownToggle"
    And I click "breakpointMobileLandscape"
    And I wait for 1 seconds
    Then I click "Save" in modal window
    And I save form
    And I check wysiwyg content in "CMS Page Content":
      | 2 | <source type="image/webp" media="(max-width: 640px) and (orientation: landscape)"/> |
      | 3 | <source type="image/jpeg"/>                                                         |
      | 4 | <source type="image/jpeg"/>                                                         |
      | 5 | <img alt="promo-slider-small-6"/>                                                   |

  Scenario: Update main image
    When I select component in canvas by tree:
      | picture | 1 |
      | image   | 1 |
    And I enter to edit mode "SelectedComponent" component in canvas
    And I click on about_1384.jpg in grid
    And I check wysiwyg content in "CMS Page Content":
      | 5 | <img alt="about_1384.jpg"/> |

  Scenario: Copy/Move/Delete image
    When I add new component "2 Columns" from panel to editor area
    And I select component in canvas by tree:
      | picture | 1 |
      | image   | 1 |
    And I click on "Clone" action for selected component
    And I select component in canvas by tree:
      | picture | 2 |
      | image   | 1 |
    And I move "SelectedComponent" to "FirstColumnInGrid" in editor canvas
    Then I check wysiwyg content in "CMS Page Content":
      | 8  | <div class="grid-cell">     |
      | 9  | <picture>                   |
      | 10 | <source type="image/webp"/> |
      | 11 | <source type="image/jpeg"/> |
      | 12 | <source type="image/jpeg"/> |
      | 13 | <img alt="about_1384.jpg"/> |
      | 14 | </picture>                  |
      | 15 | </div>                      |
    And I select component in canvas by tree:
      | grid-row    | 1 |
      | grid-column | 1 |
      | picture     | 1 |
      | image       | 1 |
    And I click on "Delete" action for selected component
    Then I check wysiwyg content in "CMS Page Content":
      | 7 | <div class="grid-row">  |
      | 8 | <div class="grid-cell"> |
      | 9 | </div>                  |
    And I clear canvas in WYSIWYG

  Scenario: Cancel add image
    When I add new component "Image" from panel to editor area
    And I close ui dialog
    Then I check wysiwyg content in "CMS Page Content":
      | 1 | <style> |

  Scenario: Import picture without image
    And I import content "<picture><source srcset=\"#\" type=\"image/webp\"></picture>" to "CMS Page Content" WYSIWYG editor
    Then I check wysiwyg content in "CMS Page Content":
      | 1 | <picture>                              |
      | 2 | <source srcset="#" type="image/webp"/> |
      | 3 | <img src="#" alt="no-alt"/>            |
      | 4 | </picture>                             |
    And I clear canvas in WYSIWYG

  Scenario: Add style to image
    When I add new component "Image" from panel to editor area
    And I click on promo-slider-small-6.jpg in grid
    And I select component in canvas by tree:
      | picture | 1 |
      | image   | 1 |
    And I update selected component "Dimension" styles:
      | width  | 500px |
      | height | 500px |
    Then I check wysiwyg content in "CMS Page Content":
      | 6 | width:500px;  |
      | 7 | height:500px; |
