@ticket-BB-21273
@ticket-BB-21666
@fixture-OroCMSBundle:content_widget_in_cms_page.yml

Feature: Nested Content Widgets in CMS Page
  In order to display nested content widgets on storefront
  As an administrator

  I want to be able to create embedded content widget
  I want to be able to add such content widget to CMS page content

  As a buyer
  I want to be able to see rendered content widget on CMS page

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create Tabbed Content Widget
    Given I proceed as the Admin
    And I login as administrator
    When I go to Marketing/ Content Widgets
    And click "Create Content Widget"
    And fill "Content Widget Form" with:
      | Type        | Tabbed Content        |
      | Name        | tabbed_content_widget |
      | Description | Tabbed description    |
    Then the "Tabs mode" option from "Tabbed Content Widget Layout Select" is selected

    When I click "Clear Tabbed Content Widget Layout Select"
    And I save and close form
    Then I should see validation errors:
      | Layout | This value should not be blank. |

    When I fill "Content Widget Form" with:
      | Layout | Tabs mode |
    And click "Add"
    And fill "Tabbed Content Widget Form" with:
      | Tab 1 Title | Tab 1 Title |
      | Tab 1 Order | 1           |
      | Tab 2 Title | Tab 2 Title |
      | Tab 2 Order | 2           |
    And I fill in WYSIWYG "Tabbed Content Widget Tab 1 Content" with "Tab 1 Content"
    And I fill in WYSIWYG "Tabbed Content Widget Tab 2 Content" with "{{ widget('copyright') }} - Tab 2 Content"
    And I save and close form
    Then I should see "Content widget has been saved" flash message
    And I should see Content Widget with:
      | Name        | tabbed_content_widget |
      | Description | Tabbed description    |
      | Layout      | Tabs mode             |
    And I should see "Type: Tabbed Content"
    And I should see next rows in "Tabbed Content Widget Tabs Table" table
      | Title       | Tab Order |
      | Tab 1 Title | 1         |
      | Tab 2 Title | 2         |

  Scenario: Add Tabbed Content Widget to CMS page
    When I go to Marketing/ Landing Pages
    And I click Edit "Test CMS Page" in grid
    And I fill in WYSIWYG "CMS Page Content" with "{{ widget('tabbed_content_widget') }}{{ widget('tabbed_content_widget') }}"
    And I save and close form
    Then I should see "Page has been saved" flash message

  Scenario: Check Tabbed Content Widget in tabs mode is rendered on storefront
    Given I proceed as the Buyer
    And I am on the homepage
    When I click "Test CMS Page"
    Then Page title equals to "Test CMS Page"
    And I should see following tabs in "Tabbed Content Widget Tabs 1" element:
      | Tab 1 Title |
      | Tab 2 Title |
    And I should see following tabs in "Tabbed Content Widget Tabs 2" element:
      | Tab 1 Title |
      | Tab 2 Title |
    And I should see "Tab 1 Content" in the "Tabbed Content Widget Tabs 1" element
    And I should see "Tab 1 Content" in the "Tabbed Content Widget Tabs 2" element
    And I should not see "All rights reserved - Tab 2 Content" in the "Tabbed Content Widget Tabs 1" element
    And I should not see "All rights reserved - Tab 2 Content" in the "Tabbed Content Widget Tabs 2" element

    When I click "Tab 2 Title" tab in "Tabbed Content Widget Tabs 2" element
    Then Page title equals to "Test CMS Page"
    And I should see following tabs in "Tabbed Content Widget Tabs 1" element:
      | Tab 1 Title |
      | Tab 2 Title |
    And I should see following tabs in "Tabbed Content Widget Tabs 2" element:
      | Tab 1 Title |
      | Tab 2 Title |
    And I should see "All rights reserved - Tab 2 Content" in the "Tabbed Content Widget Tabs 2" element
    And I should not see "Tab 1 Content" in the "Tabbed Content Widget Tabs 2" element
    And I should see "Tab 1 Content" in the "Tabbed Content Widget Tabs 1" element
    And I should not see "All rights reserved - Tab 2 Content" in the "Tabbed Content Widget Tabs 1" element

    When I click "Tab 2 Title" tab in "Tabbed Content Widget Tabs 1" element
    Then Page title equals to "Test CMS Page"
    And I should see following tabs in "Tabbed Content Widget Tabs 1" element:
      | Tab 1 Title |
      | Tab 2 Title |
    And I should see following tabs in "Tabbed Content Widget Tabs 2" element:
      | Tab 1 Title |
      | Tab 2 Title |
    And I should see "All rights reserved - Tab 2 Content" in the "Tabbed Content Widget Tabs 1" element
    And I should see "All rights reserved - Tab 2 Content" in the "Tabbed Content Widget Tabs 2" element
    And I should not see "Tab 1 Content" in the "Tabbed Content Widget Tabs 1" element
    And I should not see "Tab 1 Content" in the "Tabbed Content Widget Tabs 2" element

  Scenario: Change Tabbed Content Widget layout to accordion mode
    Given I proceed as the Admin
    When I go to Marketing/ Content Widgets
    And click edit "tabbed_content_widget" in grid
    And I fill "Content Widget Form" with:
      | Layout | Accordion mode |
    And I fill in WYSIWYG "Tabbed Content Widget Tab 1 Content" with "Tab 1 Content updated"
    And I fill in WYSIWYG "Tabbed Content Widget Tab 2 Content" with "{{ widget('copyright') }} - Tab 2 Content updated"
    And I save and close form
    Then I should see "Content widget has been saved" flash message
    And I should see Content Widget with:
      | Layout | Accordion mode |
    And I should see next rows in "Tabbed Content Widget Tabs Table" table
      | Title       | Tab Order |
      | Tab 1 Title | 1         |
      | Tab 2 Title | 2         |

  Scenario: Check Tabbed Content Widget in accordion mode is rendered on storefront
    Given I proceed as the Buyer
    When I reload the page
    Then Page title equals to "Test CMS Page"
    And I should not see following tabs:
      | Tab 1 Title |
      | Tab 2 Title |
    And I should see "Tab 1 Accordion Trigger" element inside "Tabbed Content Widget Accordion 1" element
    And I should see "Tab 2 Accordion Trigger" element inside "Tabbed Content Widget Accordion 1" element
    And I should see "Tab 1 Accordion Trigger" element inside "Tabbed Content Widget Accordion 2" element
    And I should see "Tab 2 Accordion Trigger" element inside "Tabbed Content Widget Accordion 2" element
    And I should not see "All rights reserved - Tab 2 Content updated"
    And I should not see "All rights reserved - Tab 2 Content"
    And I should see "Tab 1 Content updated" in the "Tabbed Content Widget Accordion 1" element
    And I should see "Tab 1 Content updated" in the "Tabbed Content Widget Accordion 2" element

    When I click "Tab 2 Title" in "Tabbed Content Widget Accordion 2" element
    Then Page title equals to "Test CMS Page"
    And I should see "Tab 1 Accordion Trigger" element inside "Tabbed Content Widget Accordion 1" element
    And I should see "Tab 2 Accordion Trigger" element inside "Tabbed Content Widget Accordion 1" element
    And I should see "Tab 1 Accordion Trigger" element inside "Tabbed Content Widget Accordion 2" element
    And I should see "Tab 2 Accordion Trigger" element inside "Tabbed Content Widget Accordion 2" element
    And I should see "All rights reserved - Tab 2 Content updated" in the "Tabbed Content Widget Accordion 2" element
    And I should not see "Tab 1 Content updated" in the "Tabbed Content Widget Accordion 2" element
    And I should see "Tab 1 Content updated" in the "Tabbed Content Widget Accordion 1" element
    And I should not see "All rights reserved - Tab 2 Content updated" in the "Tabbed Content Widget Accordion 1" element

    When I click "Tab 2 Title" in "Tabbed Content Widget Accordion 1" element
    Then Page title equals to "Test CMS Page"
    And I should see "Tab 1 Accordion Trigger" element inside "Tabbed Content Widget Accordion 1" element
    And I should see "Tab 2 Accordion Trigger" element inside "Tabbed Content Widget Accordion 1" element
    And I should see "Tab 1 Accordion Trigger" element inside "Tabbed Content Widget Accordion 2" element
    And I should see "Tab 2 Accordion Trigger" element inside "Tabbed Content Widget Accordion 2" element
    And I should see "All rights reserved - Tab 2 Content updated" in the "Tabbed Content Widget Accordion 1" element
    And I should see "All rights reserved - Tab 2 Content updated" in the "Tabbed Content Widget Accordion 2" element
    And I should not see "Tab 1 Content updated" in the "Tabbed Content Widget Accordion 1" element
    And I should not see "Tab 1 Content updated" in the "Tabbed Content Widget Accordion 2" element
