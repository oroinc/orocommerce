@ticket-BB-17550
@feature-BB-23047
@fixture-OroProductBundle:new_arrivals_block.yml

Feature: Product segment content widget
  In order to have product segment displayed on the storefront
  As an Administrator
  I need to be able to create and modify the product segment content widget in the back office

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Check product segments widgets
    Given I proceed as the Admin
    And login as administrator
    When go to Marketing/Content Widgets
    Then I should see following grid:
      | Name              | Type           |
      | new-arrivals      | Product Segment|
      | featured-products | Product Segment|

    When I click view new-arrivals in grid
    And I should see Content Widget with:
      | Name                          | new-arrivals |
      | Segment                       | New Arrivals |
      | Label                         | New Arrivals |
      | Maximum Items                 | 6            |
      | Minimum Items                 | 3            |
      | Use Slider On Mobile          | No           |
      | Show Add Button               | Yes          |
      | Enable Autoplay               | No           |
      | Autoplay Speed (Milliseconds) | 4000         |
      | Show Arrows                   | Yes          |
      | Show Arrows On Touchscreens   | No           |
      | Show Dots                     | No           |
      | Enable Infinite Scroll        | No           |

    When go to Marketing/Content Widgets
    When I click view featured-products in grid
    And I should see Content Widget with:
      | Name                          | featured-products |
      | Segment                       | Featured Products |
      | Label                         | Featured Products |
      | Maximum Items                 | 10                |
      | Minimum Items                 | 3                 |
      | Use Slider On Mobile          | Yes               |
      | Show Add Button               | Yes               |
      | Enable Autoplay               | No                |
      | Autoplay Speed (Milliseconds) | 4000              |
      | Show Arrows                   | Yes               |
      | Show Arrows On Touchscreens   | No                |
      | Show Dots                     | No                |
      | Enable Infinite Scroll        | No                |

  Scenario: Check default content widget's settings
    When go to Marketing/Content Widgets
    And click "Create Content Widget"
    And fill "Content Widget Form" with:
      | Type | Product Segment |
    Then "Content Widget Form" must contains values:
      | Enable Autoplay               | false |
      | Autoplay Speed (Milliseconds) | 4000  |
      | Show Arrows                   | true  |
      | Show Arrows On Touchscreens   | false |
      | Show Dots                     | false |
      | Enable Infinite Scroll        | false |

  Scenario Outline: Check validation messages
    When fill "Content Widget Form" with:
      | <Field> | <Value>  |
    Then I should see "Content Widget Form" validation errors:
      | <Field> | <Expected Error Message> |

    Examples:
      | Field                         | Value                                                                                                                                                                                                                                                             | Expected Error Message                                         |
      | Autoplay Speed (Milliseconds) | -100                                                                                                                                                                                                                                                              | This value should be greater than 0.                           |
      | Autoplay Speed (Milliseconds) | 1.23                                                                                                                                                                                                                                                              | This value should be of type integer.                          |
      | Default Label                 | Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer finibus viverra ante, sit amet fringilla ipsum fringilla eu. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec vitae felis ac neque posuere egestas. | This value is too long. It should have 255 characters or less. |

  Scenario: Create content widget
    When fill "Content Widget Form" with:
      | Type                          | Product Segment |
      | Name                          | product_segment |
      | Segment                       | New Arrivals    |
      | Use Slider On Mobile          | Yes             |
      | Enable Autoplay               | true            |
      | Autoplay Speed (Milliseconds) | 3000            |
      | Show Arrows On Touchscreens   | true            |
      | Show Dots                     | true            |
      | Enable Infinite Scroll        | true            |
    And I click on "Content Widget Label Localization Form Fallbacks"
    And fill "Content Widget Form" with:
      | Default Label           | New Arrivals Default Label   |
      | Use Default             | false                        |
      | English (United States) | New Arrivals Label (English) |
    And I save and close form
    Then I should see "Content widget has been saved" flash message
    And I should see "Type: Product Segment"
    And I should see Content Widget with:
      | Name                          | product_segment            |
      | Segment                       | New Arrivals               |
      | Label                         | New Arrivals Default Label |
      | Maximum Items                 | 4                          |
      | Minimum Items                 | 3                          |
      | Use Slider On Mobile          | Yes                        |
      | Show Add Button               | Yes                        |
      | Enable Autoplay               | Yes                        |
      | Autoplay Speed (Milliseconds) | 3000                       |
      | Show Arrows                   | Yes                        |
      | Show Arrows On Touchscreens   | Yes                        |
      | Show Dots                     | Yes                        |
      | Enable Infinite Scroll        | Yes                        |

  Scenario: Create Landing Page
    Given I go to Marketing/Landing Pages
    When click "Create Landing Page"
    And I fill in Landing Page Titles field with "Product Segment Page"
    And I fill in WYSIWYG "CMS Page Content" with "<h1>Additional test data</h1><div data-title=\"product_segment\" data-type=\"product_segment\" class=\"content-widget content-placeholder\">{{ widget(\"product_segment\") }}</div>"
    And I save form
    Then I should see "Page has been saved" flash message
    And I should see URL Slug field filled with "product-segment-page"

  Scenario: Create Menu Item
    Given I go to System/Storefront Menus
    When click "view" on row "commerce_main_menu" in grid
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
    When I click "Product Segment Page" in hamburger menu
    Then Page title equals to "Product Segment Page"
    And I should see "New Arrivals Label (English)"
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
    And I should see "New Arrivals Label (English)"
    And I should see "Product7"
    When I click "Product Segment Content Widget Slider Control1"
    Then I should see "Product6"
    When I click "Product Segment Content Widget Slider Control2"
    Then I should see "Product5"
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
    When go to Marketing/Content Widgets
    And click "Edit" on row "product_segment" in grid
    And fill "Content Widget Form" with:
      | Show Add Button             | No              |
      | Use Slider On Mobile        | No              |
      | Enable Autoplay             | false           |
      | Show Arrows                 | false           |
      | Show Arrows On Touchscreens | false           |
      | Show Dots                   | false           |
      | Enable Infinite Scroll      | false           |
    And I save and close form
    Then I should see "Content widget has been saved" flash message
    And I should see Content Widget with:
      | Name                          | product_segment            |
      | Segment                       | New Arrivals               |
      | Label                         | New Arrivals Default Label |
      | Maximum Items                 | 4                          |
      | Minimum Items                 | 3                          |
      | Use Slider On Mobile          | No                         |
      | Show Add Button               | No                         |
      | Enable Autoplay               | No                         |
      | Autoplay Speed (Milliseconds) | 3000                       |
      | Show Arrows                   | No                         |
      | Show Arrows On Touchscreens   | No                         |
      | Show Dots                     | No                         |
      | Enable Infinite Scroll        | No                         |

  Scenario: Check rendering buttons
    Given I proceed as the Buyer
    When reload the page
    Then Page title equals to "Product Segment Page"
    And I should see "Additional test data"
    And I should see "New Arrivals Label (English)"
    And I should see "Product7"
    And I should see "Product6"
    And I should see "Product5"
    And I should see "Product4"
    And I should not see "In Shopping List"
    And I should not see "Update Shopping List"

  Scenario: Change label
    Given I proceed as the Admin
    When go to Marketing/Content Widgets
    And click "Edit" on row "product_segment" in grid
    And I click on "Content Widget Label Localization Form Fallbacks"
    And fill "Content Widget Form" with:
      | Default Label           | New Arrivals Default Label Updated   |
      | English (United States) | New Arrivals Label (English) Updated |
    And I save and close form
    Then I should see "Content widget has been saved" flash message
    And I should see Content Widget with:
      | Name  | product_segment                    |
      | Label | New Arrivals Default Label Updated |

  Scenario: Check label
    Given I proceed as the Buyer
    When reload the page
    Then Page title equals to "Product Segment Page"
    And I should see "Additional test data"
    And I should see "New Arrivals Label (English) Updated"

  Scenario: Change maximum items
    Given I proceed as the Admin
    When go to Marketing/Content Widgets
    And click "Edit" on row "product_segment" in grid
    And fill "Content Widget Form" with:
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
    And I should see "New Arrivals Label (English) Updated"
    And I should see "Product7"
    And I should see "Product6"
    And I should not see "Product5"
    And I should not see "Product4"
    And I should not see "In Shopping List"
    And I should not see "Update Shopping List"

  Scenario: Change minimum items
    Given I proceed as the Admin
    When go to Marketing/Content Widgets
    And click "Edit" on row "product_segment" in grid
    And fill "Content Widget Form" with:
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
