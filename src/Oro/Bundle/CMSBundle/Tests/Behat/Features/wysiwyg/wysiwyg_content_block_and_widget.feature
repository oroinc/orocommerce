@regression
Feature: WYSIWYG content block and widget

  Scenario: Create landing page
    Given I login as administrator
    And I go to Marketing / Content Blocks
    And click "Create Content Block"
    And fill "Content Block Form" with:
      | Owner   | Main       |
      | Alias   | test_alias |
      | Titles  | Test Title |
      | Enabled | True       |
    And I click "Add Content"
    When I save and close form
    And go to Marketing / Landing Pages
    And click "Create Landing Page"
    And I fill in Landing Page Titles field with "WYSIWYG UI page"
    And I save form

  Scenario: Add/Update/Delete content block
    When I add new component "Content Block" from panel to editor area
    And I click on home-page-slider in grid
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <div data-title="Home Page Slider" class="content-block content-placeholder">{{ content_block("home-page-slider") }} |
      | 2 | </div>                                                                                                               |
    And I select component in canvas by tree:
      | content-block | 1 |
    Then I click on "Block Settings" action for selected component
    And I click on test_alias in grid
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <div data-title="Test Title" class="content-block content-placeholder">{{ content_block("test_alias") }} |
    And I select component in canvas by tree:
      | content-block | 1 |
    Then I click on "Delete" action for selected component
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <style> |

  Scenario: Add/Update/Delete content widget
    When I add new component "Content Widget" from panel to editor area
    And I click on home-page-slider in grid
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <div data-title="home-page-slider" data-type="image_slider" class="content-widget content-placeholder">{{ widget("home-page-slider") }} |
      | 2 | </div>                                                                                                                                  |
    And I select component in canvas by tree:
      | content-widget | 1 |
    Then I click on "Widget Settings" action for selected component
    And I click on contact_us_form in grid
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <div data-title="contact_us_form" data-type="contact_us_form" class="content-widget content-placeholder">{{ widget("contact_us_form") }} |
    And I select component in canvas by tree:
      | content-widget | 1 |
    Then I click on "Delete" action for selected component
    And I check wysiwyg content in "CMS Page Content":
      | 1 | <style> |
