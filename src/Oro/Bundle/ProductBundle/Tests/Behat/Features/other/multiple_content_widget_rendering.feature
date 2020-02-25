@ticket-BB-17551
@fixture-OroProductBundle:new_arrivals_segment_fixture.yml

Feature: Multiple content widget rendering
  In order to have page on the storefront with different content widgets
  As an Administrator
  I need to be able to create such page in the back office

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create product segment content widget
    Given I proceed as the Admin
    And login as administrator
    And go to Marketing/Content Widgets
    And click "Create Content Widget"
    When fill "Content Widget Form" with:
      | Type                 | Product Segment |
      | Name                 | product_segment |
      | Segment              | New Arrivals    |
      | Maximum Items        | 4               |
      | Minimum Items        | 1               |
      | Use Slider On Mobile | Yes             |
      | Show Add Button      | Yes             |
    And I save and close form
    Then I should see "Content widget has been saved" flash message

  Scenario: Create image slider content widget
    Given go to Marketing/Content Widgets
    And click "Create Content Widget"
    When fill "Content Widget Form" with:
      | Type                          | Image Slider |
      | Name                          | image_slider |
      | Number of Slides to Show      | 1            |
      | Number of Slides to Scroll    | 1            |
      | Enable Autoplay               | false        |
      | Autoplay Speed (milliseconds) | 4000         |
      | Show Arrows                   | false        |
      | Show Dots                     | true         |
      | Enable Infinite Scroll        | false        |
    And click "Add"
    And fill "Image Slider Form" with:
      | Slide Order 1    | 1            |
      | URL 1            | /product     |
      | Target 1         | Same Window  |
      | Title 1          | Slide 1      |
      | Text Alignment 1 | Center       |
      | Text 1           | Slide text 1 |
      | Slide Order 2    | 2            |
      | URL 2            | /about       |
      | Target 2         | New Window   |
      | Title 2          | Slide 2      |
      | Text Alignment 2 | Center       |
      | Text 2           | Slide text 2 |
    And I click on "Choose Main Slider Image 1"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat1.jpg |
      | Title | cat1.jpg |
    And I click "Upload"
    And click on cat1.jpg in grid
    And I click on "Choose Main Slider Image 2"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat2.jpg |
      | Title | cat2.jpg |
    And I click "Upload"
    And click on cat2.jpg in grid
    And I save and close form
    Then I should see "Content widget has been saved" flash message

  Scenario: Create Landing Page
    Given I go to Marketing/Landing Pages
    And click "Create Landing Page"
    And I fill in Landing Page Titles field with "Multiple Content Widgets Page"
    And I fill in WYSIWYG "CMS Page Content" with "<h1>Additional test data</h1>{{ widget('image_slider') }}{{ widget(\"product_segment\") }}{{ widget(\"product_segment\") }}"
    When I save form
    Then I should see "Page has been saved" flash message
    And I should see URL Slug field filled with "multiple-content-widgets-page"

  Scenario: Create Menu Item
    Given I go to System/Frontend Menus
    And click "view" on row "commerce_main_menu" in grid
    And click "Create Menu Item"
    When I fill "Commerce Menu Form" with:
      | Title       | Multiple Content Widgets Page |
      | Target Type | URI                           |
      | URI         | multiple-content-widgets-page |
    And I save form
    Then I should see "Menu item saved successfully" flash message

  Scenario: Check content widget on storefront
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on the homepage
    When I click "Multiple Content Widgets Page"
    Then Page title equals to "Multiple Content Widgets Page"
    And I should see "Additional test data"
    And I should see "Product1"
    And I should see "Your Price: $10.00 / item" for "SKU1" product
    And I should see "Add to Shopping List"
    And I should not see "Price for requested quantity is not available"

  Scenario: Ensure sliders are functional
    Given I should see "Slide text 1"
    And I should not see "Slide text 2"
    When I click "Second Dot On Image Slider"
    Then I should see "Slide text 2"
    And I should not see "Slide text 1"
