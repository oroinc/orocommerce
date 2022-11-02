@ticket-BB-17548
@fixture-OroCMSBundle:CustomerUserFixture.yml
@fixture-OroCMSBundle:WysiwygRoleFixture.yml

Feature: Configurable image slider
  In order to have image sliders displayed on the storefront
  As an Administrator
  I need to be able to create and modify the image slider widget in the back office

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create content widget
    Given I proceed as the Admin
    And login as administrator
    And go to Marketing/Content Widgets
    And click "Create Content Widget"
    When fill "Content Widget Form" with:
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
      | Slide Order 1    | 1            |
      | URL 1            | /product     |
      | Target 1         | Same Window  |
      | Title 1          | Slide 1      |
      | Text Alignment 1 | Center       |
      | Text 1           | Slide text 1 |
    When I save and close form
    Then I should see "This value should not be blank."
    And I click on "Choose Main Slider Image 1"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat1.jpg |
      | Title | cat1.jpg |
    And I click "Upload"
    And click on cat1.jpg in grid
    And I click on "Choose Medium Slider Image 1"
    And click on cat1.jpg in grid
    When I save and close form
    Then I should see "Content widget has been saved" flash message
    And I should see "Type: Image Slider"
    And I should see Content Widget with:
      | Name | test_image_slider |

  Scenario: Update content widget
    Given I click "Edit"
    And click "Add"
    When fill "Image Slider Form" with:
      | Slide Order 2    | 2            |
      | URL 2            | /about       |
      | Target 2         | New Window   |
      | Title 2          | Slide 2      |
      | Text Alignment 2 | Center       |
      | Text 2           | Slide text 2 |
    And I click on "Choose Main Slider Image 2"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat2.jpg |
      | Title | cat2.jpg |
    And I click "Upload"
    And click on cat2.jpg in grid

    And I click on "Choose Small Slider Image 2"
    And click on cat2.jpg in grid

    When I save and close form
    Then I should see "Content widget has been saved" flash message
    And I should see next rows in "Slides" table
      | SLIDE ORDER | URL      | TITLE   | TEXT         | TEXT ALIGNMENT | TARGET WINDOW | MAIN IMAGE | MEDIUM IMAGE | SMALL IMAGE |
      | 1           | /product | Slide 1 | Slide text 1 | Center         | Same Window   | cat1.jpg   | cat1.jpg     |             |
      | 2           | /about   | Slide 2 | Slide text 2 | Center         | New Window    | cat2.jpg   |              | cat2.jpg    |

  Scenario: Edit user roles
    Given I go to System/User Management/Users
    When click Edit admin in grid
    And I click "Groups and Roles"
    And I fill form with:
      | Roles | [Administrator, WYSIWYG] |
    And I save and close form
    Then I should see "User saved" flash message
    # Relogin for refresh token after change user roles
    And I am logged out

  Scenario: Create Landing Page
    Given login as administrator
    And I go to Marketing/Landing Pages
    And click "Create Landing Page"
    And I fill in Landing Page Titles field with "Image slider page"
    And I fill in WYSIWYG "CMS Page Content" with "<div data-title=\"test_image_slider\" data-type=\"image_slider\" class=\"content-widget content-placeholder\">{{ widget('test_image_slider') }}</div>"
    When I save form
    Then I should see "Page has been saved" flash message
    And I should see URL Slug field filled with "image-slider-page"

  Scenario: Create Menu Item
    Given I go to System/Frontend Menus
    And click "view" on row "commerce_main_menu" in grid
    And click "Create Menu Item"
    And I fill "Commerce Menu Form" with:
      | Title       | Image slider page |
      | Target Type | URI               |
      | URI         | image-slider-page |
    And I save form
    Then I should see "Menu item saved successfully" flash message

  Scenario: Check content widget of storefront
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on the homepage
    When I click "Image slider page"
    Then Page title equals to "Image slider page"
    And I should see "Slide text 1"
    And I should not see "Slide text 2"

  Scenario: Check click on slide
    Given I should not see "All Products"
    When I click "First Image Slide"
    Then I should see "All Products"

  Scenario: Check second slide
    Given I click "Image slider page"
    And I should see "Slide text 1"
    And I should not see "Slide text 2"
    When I click "Second Dot On Image Slider"
    And I should not see "Slide text 1"
    And I should see "Slide text 2"

  Scenario: Add same image slider to the same page
    Given I proceed as the Admin
    And I go to Marketing/Landing Pages
    And click Edit "Image slider page" in grid
    And I fill in WYSIWYG "CMS Page Content" with "<div data-title=\"test_image_slider\" data-type=\"image_slider\" class=\"content-widget content-placeholder\">{{ widget('test_image_slider') }}</div><div data-title=\"test_image_slider\" data-type=\"image_slider\" class=\"content-widget content-placeholder\">{{ widget('test_image_slider') }}</div>"
    When I save form
    Then I should see "Page has been saved" flash message

  Scenario: Ensure sliders are still functional
    Given I proceed as the Buyer
    When I click "Image slider page"
    Then Page title equals to "Image slider page"
    And I should see "Slide text 1"
    And I should not see "Slide text 2"
    And I should not see "All Products"
    When I click "First Image Slide"
    Then I should see "All Products"
    When I click "Image slider page"
    Then I should see "Slide text 1"
    And I should not see "Slide text 2"
    When I click "Second Dot On Image Slider"
    Then I should see "Slide text 2"
    And I should see "Slide text 1"
