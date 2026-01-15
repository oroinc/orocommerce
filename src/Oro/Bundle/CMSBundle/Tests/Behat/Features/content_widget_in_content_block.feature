@regression
@ticket-BB-17552
@fixture-OroCMSBundle:home_page_slider_content_widget_fixture.yml

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
    And I add Home Page Slider content block before content for "Homepage" page
    When go to Marketing/ Content Widgets
    And click "Create Content Widget"
    And fill "Content Widget Form" with:
      | Type                          | Image Slider      |
      | Name                          | test_image_slider |
      | Number of Slides to Show      | 1                 |
      | Number of Slides to Scroll    | 1                 |
      | Enable Autoplay               | false             |
      | Autoplay Speed (milliseconds) | 4000              |
      | Show Arrows                   | false             |
      | Show Dots                     | true              |
      | Enable Infinite Scroll        | false             |
    And fill "Image Slider Form" with:
      | Slide Order 1    | 1              |
      | URL 1            | /product       |
      | Target 1         | Same Window    |
      | Alt Image Text 1 | Slide 1        |
      | Text Alignment 1 | Center         |
      | Text 1           | Slide text 1   |
      | Header 1         | Image Header 1 |
      | Loading 1        | Eager          |
      | Fetch Priority 1 | High           |
    And I click on "Choose Extra Large Slider Image1x 1"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat1.jpg |
      | Title | cat1.jpg |
    And I click "Upload"
    And click on cat1.jpg in grid
    And I click on "Choose Large Slider Image1x 1"
    And click on cat1.jpg in grid
    And I click on "Choose Medium Slider Image1x 1"
    And click on cat1.jpg in grid
    And I click on "Choose Small Slider Image1x 1"
    And click on cat1.jpg in grid
    Then I save and close form
    And I should see "Content widget has been saved" flash message

    And I go to Marketing/ Content Blocks
    And I click "edit" on row "home-page-slider" in grid
    And I fill in WYSIWYG "Content Variant Content" with "{{ widget('test_image_slider') }}"
    When I save and close form
    Then I should see "Content block has been saved" flash message

  Scenario: Ensure content widget cannot be deleted when used
    When I go to Marketing/ Content Widgets
    Then I should not see following actions for test_image_slider in grid:
      | Delete |
    When I click View "test_image_slider" in grid
    Then I should not see "Delete"

  Scenario: Check content widget usages grid
    When I go to Marketing/ Content Widgets
    And I click "view" on row "test_image_slider" in grid
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
    Then I should see "Slide text 1"

  Scenario: Ensure content widget can be deleted when there are no usages
    Given I proceed as the Admin
    And I go to Marketing/ Content Blocks
    And I click "edit" on row "home-page-slider" in grid
    And I fill in WYSIWYG "Content Variant Content" with "another content"
    When I save and close form
    Then I should see "Content block has been saved" flash message
    And I go to Marketing/ Content Widgets
    And I keep in mind number of records in list
    When I click Delete "test_image_slider" in grid
    And I confirm deletion
    Then the number of records decreased by 1
    And I should not see "test_image_slider"
