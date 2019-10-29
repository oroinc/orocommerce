@regression
@fixture-OroProductBundle:product_listing_images.yml

Feature: Gallery as slider
  In order to use the best image gallery option for my theme design
  As an Administrator
  I want to be able to choose whether to use popup for image gallery, or to use it inline

  # Description
  # Based on the setting selected in system configuration use one of the two tempaltes to display customer user menu:
  # all menu items are displayed on the page
  # menu items are displayed in a drop-down when user clicks on the user name
  # Use different welcome messages in the templates:
  # default template (all at once) - "Signed in as: John Doe"
  # when only name is shown - "Welcome, John Doe"
  #
  # Configuration
  # In the "Image Gallery Options" fieldset
  # on the System -> Configuration -> COMMERCE -> Product -> Product Images page:
  # Add new field "Popup Gallery on Product View" - checkbox,
  # default value - enabled (checked), levels - global/organization/website, hint:
  # Inline gallery view may work better for some product templates.
  #
  # Acceptance Criteria
  # Show how administrators can switch between popup and inline gallery views on different configuration levels
  # Show how popup and inline gallery work on product view pages

  Scenario: Check gallery popup is present on front store
    Given I login as administrator
    And I go to Products / Products
    And I click Edit "PSKU1" in grid
    And I set Images with:
      | Main  | Listing | Additional |
      | 1     | 1       | 1          |
    And I click on "Digital Asset Choose"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat1.jpg |
      | Title | cat1.jpg |
    And I click "Upload"
    And click on cat1.jpg in grid
    And I set Images with:
      | Main  | Listing | Additional |
      |       |         | 1          |
    And I click on "Digital Asset Choose"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat2.jpg |
      | Title | cat2.jpg |
    And I click "Upload"
    And click on cat2.jpg in grid
    And I save and close form
    And I should see "Product has been saved" flash message
    And I am on the homepage
    When I type "PSKU1" in "search"
    And click "Search Button"
    And I click "View Details" for "PSKU1" product
    And I should see an "Product View Gallery Trigger" element
    And I click "Product View Gallery Trigger"
    Then I should see an "Popup Gallery Widget" element

  Scenario: Check gallery as slider is present on front store
    Given I am on dashboard
    And go to System / Configuration
    And I follow "Commerce/Product/Product Images" on configuration sidebar
    And fill "Product Images Form" with:
      | Popup Gallery Default         | false |
      | Popup Gallery On Product View | false |
    And click "Save settings"
    And I am on the homepage
    When I type "PSKU1" in "search"
    And click "Search Button"
    And click "View Details" for "PSKU1" product
    And I should see an "Product Slider" element
    Then I should not see an "Product View Gallery Trigger" element
