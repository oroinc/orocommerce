@ticket-BB-17552
@fixture-OroCMSBundle:copyright_content_widget_fixture.yml

Feature: Content Widget in Content Block
  In order to display content widgets on store front
  As an administrator
  I want to be able to add content widget to content block content
  I do not want to be able to delete content widget which is used in some content block
  I want to be able to view usages of content widget in content blocks
  As a buyer
  I want to be able to see rendered content widget on store front

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Add content widget to content block
    Given I proceed as the Admin
    And I login as administrator
    And I go to Marketing/ Content Blocks
    And I click "edit" on row "home-page-slider" in grid
    And I fill in WYSIWYG "Content Variant Content" with "{{ widget('copyright') }}"
    When I save and close form
    Then I should see "Content block has been saved" flash message

  Scenario: Ensure content widget cannot be deleted when used
    When I go to Marketing/ Content Widgets
    Then I should not see following actions for copyright in grid:
      | Delete |
    When I click View "copyright" in grid
    Then I should not see "Delete"

  Scenario: Check content widget usages grid
    When I go to Marketing/ Content Widgets
    And I click "view" on row "copyright" in grid
    Then number of records in "Content Blocks Content Widget Usages Grid" should be 1
    And I should see following "Content Blocks Content Widget Usages Grid" grid:
      | Alias            | Title            |
      | home-page-slider | Home Page Slider |
    And It should be 2 columns in "Content Blocks Content Widget Usages Grid" grid
    When I click "Grid Settings" in "Content Blocks Content Widget Usages Grid" element
    And I click "Filters" tab in "Content Blocks Content Widget Usages Grid" element
    Then I should see following filters in the grid settings in exact order:
      | Alias      |
      | Title      |
      | Enabled    |
      | Created At |
      | Updated At |

  Scenario: Check content widget is rendered on store front
    Given I proceed as the Buyer
    When I am on the homepage
    Then I should see ". All rights reserved"

  Scenario: Ensure content widget can be deleted when there are no usages
    Given I proceed as the Admin
    And I go to Marketing/ Content Blocks
    And I click "edit" on row "home-page-slider" in grid
    And I fill in WYSIWYG "Content Variant Content" with "another content"
    When I save and close form
    Then I should see "Content block has been saved" flash message
    And I go to Marketing/ Content Widgets
    And I keep in mind number of records in list
    When I click Delete "copyright" in grid
    And I confirm deletion
    Then the number of records decreased by 1
    And I should not see "copyright"
