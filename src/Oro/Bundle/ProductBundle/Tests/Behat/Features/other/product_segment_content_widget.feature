@ticket-BB-17550
@fixture-OroProductBundle:new_arrivals_block.yml

Feature: Product segment content widget
  In order to have product segment displayed on the storefront
  As an Administrator
  I need to be able to create and modify the product segment content widget in the back office

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
      | Type                 | Product Segment |
      | Name                 | product_segment |
      | Segment              | New Arrivals    |
      | Use Slider On Mobile | Yes             |
    And I save and close form
    Then I should see "Content widget has been saved" flash message
    And I should see "Type: Product Segment"
    And I should see Content Widget with:
      | Name                 | product_segment |
      | Segment              | New Arrivals    |
      | Maximum Items        | 4               |
      | Minimum Items        | 3               |
      | Use Slider On Mobile | Yes             |
      | Show Add Button      | Yes             |

  Scenario: Create Landing Page
    Given I go to Marketing/Landing Pages
    And click "Create Landing Page"
    And I fill in Landing Page Titles field with "Product Segment Page"
    And I fill in WYSIWYG "CMS Page Content" with "<h1>Additional test data</h1><div data-title=\"product_segment\" data-type=\"product_segment\" class=\"content-widget content-placeholder\">{{ widget(\"product_segment\") }}</div>"
    When I save form
    Then I should see "Page has been saved" flash message
    And I should see URL Slug field filled with "product-segment-page"

  Scenario: Create Menu Item
    Given I go to System/Frontend Menus
    And click "view" on row "commerce_main_menu" in grid
    And click "Create Menu Item"
    And I fill "Commerce Menu Form" with:
      | Title       | Product Segment Page |
      | Target Type | URI                  |
      | URI         | product-segment-page |
    And I save form
    Then I should see "Menu item saved successfully" flash message

  Scenario: Check content widget on storefront
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on the homepage
    When I click "Product Segment Page"
    Then Page title equals to "Product Segment Page"
    And I should see "Additional test data"
    And I should see "Product7"
    And I should see "Product6"
    And I should see "Product5"
    And I should see "Product4"
    And I should see "Add to Shopping List"

  Scenario: Check content widget on storefront rendered in slider (mobile view)
    Given I set window size to 375x640
    When I reload the page
    Then Page title equals to "Product Segment Page"
    And I should see "Additional test data"
    And I should see "Product7"
    When I click "Product Segment Content Widget Slider Next"
    Then I should see "Product6"
    When I click "Product Segment Content Widget Slider Next"
    Then I should see "Product5"
    When I click "Product Segment Content Widget Slider Next"
    Then I should see "Product4"
    And I should see "Add to Shopping List"

  Scenario: Check add button
    Given I should not see "In Shopping List"
    When I should not see "Update Shopping List"
    And I click "Add to Shopping List" for "SKU7" product
    And I click "In Shopping List" for "SKU7" product
    Then I should see "UiDialog" with elements:
      | Title | Product7 |
    And I close ui dialog
    And I should see "In Shopping List"
    And I should see "Update Shopping List"

  Scenario: Disable rendering buttons
    Given I proceed as the Admin
    And go to Marketing/Content Widgets
    And click "Edit" on row "product_segment" in grid
    When fill "Content Widget Form" with:
      | Show Add Button      | No |
      | Use Slider On Mobile | No |
    And I save and close form
    Then I should see "Content widget has been saved" flash message
    And I should see Content Widget with:
      | Name                 | product_segment |
      | Segment              | New Arrivals    |
      | Maximum Items        | 4               |
      | Minimum Items        | 3               |
      | Use Slider On Mobile | No              |
      | Show Add Button      | No              |

  Scenario: Check rendering buttons
    Given I proceed as the Buyer
    When reload the page
    Then Page title equals to "Product Segment Page"
    And I should see "Additional test data"
    And I should see "Product7"
    And I should see "Product6"
    And I should see "Product5"
    And I should see "Product4"
    And I should not see "In Shopping List"
    And I should not see "Update Shopping List"

  Scenario: Change maximum items
    Given I proceed as the Admin
    And go to Marketing/Content Widgets
    And click "Edit" on row "product_segment" in grid
    When fill "Content Widget Form" with:
      | Maximum Items | 2 |
      | Minimum Items | 2 |
    And I save and close form
    Then I should see "Content widget has been saved" flash message
    And I should see Content Widget with:
      | Name                 | product_segment |
      | Segment              | New Arrivals    |
      | Maximum Items        | 2               |
      | Minimum Items        | 2               |
      | Use Slider On Mobile | No              |
      | Show Add Button      | No              |

  Scenario: Check maximum items
    Given I proceed as the Buyer
    When reload the page
    Then Page title equals to "Product Segment Page"
    And I should see "Additional test data"
    And I should see "Product7"
    And I should see "Product6"
    And I should not see "Product5"
    And I should not see "Product4"
    And I should not see "In Shopping List"
    And I should not see "Update Shopping List"

  Scenario: Change minimum items
    Given I proceed as the Admin
    And go to Marketing/Content Widgets
    And click "Edit" on row "product_segment" in grid
    When fill "Content Widget Form" with:
      | Minimum Items | 8 |
    And I save and close form
    Then I should see "Content widget has been saved" flash message
    And I should see Content Widget with:
      | Name                 | product_segment |
      | Segment              | New Arrivals    |
      | Maximum Items        | 2               |
      | Minimum Items        | 8               |
      | Use Slider On Mobile | No              |
      | Show Add Button      | No              |

  Scenario: Check maximum items
    Given I proceed as the Buyer
    When reload the page
    Then Page title equals to "Product Segment Page"
    And I should see "Additional test data"
    And I should not see "Product7"
    And I should not see "Product6"
    And I should not see "Product5"
    And I should not see "Product4"
