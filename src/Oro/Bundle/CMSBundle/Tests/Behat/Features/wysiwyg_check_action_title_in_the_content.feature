@ticket-BB-20180
@fixture-OroCMSBundle:copyright_content_widget_fixture.yml
Feature: Wysiwyg check action title in the content

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |

  Scenario: Check action title in the Wysiwyg toolbar
    Given I proceed as the Admin
    And I login as administrator
    And I go to Marketing/ Content Blocks
    And I click "edit" on row "home-page-slider" in grid
    And I click on "Scrollspy Link" with title "Content Variants"
    And I fill in WYSIWYG "Content Variant Content" with "<a href='#' title='File name' target='_self' class='digital-asset-file no-hash'>File name</a>"
    And should see text matching "File name" in WYSIWYG editor
    And I click on "WysiwygFileTypeBlock" with title "File name" in WYSIWYG editor
    When I hover on "WysiwygToolbarActionFileSettings"
    Then I should see "TooltipInner" element with text "File Settings" inside "Tooltip" element
    When I hover on "WysiwygToolbarActionSelectParent"
    Then I should see "TooltipInner" element with text "Select Parent" inside "Tooltip" element
    When I hover on "WysiwygToolbarActionMove"
    Then I should see "TooltipInner" element with text "Move" inside "Tooltip" element
    When I hover on "WysiwygToolbarActionClone"
    Then I should see "TooltipInner" element with text "Clone" inside "Tooltip" element
    When I hover on "WysiwygToolbarActionDelete"
    Then I should see "TooltipInner" element with text "Delete" inside "Tooltip" element
