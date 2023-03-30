@behat-test-env
@ticket-BB-17552
@fixture-OroCMSBundle:content_widget_in_cms_page.yml

Feature: Content Widget in CMS Page
  In order to display content widgets on store front
  As an administrator

  I want to be able to add content widget to CMS page content
  I do not want to be able to delete content widget which is used in some CMS page
  I want to be able to view usages of content widget in CMS pages

  As a buyer
  I want to be able to see rendered content widget on CMS page

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Add content widget to CMS page
    Given I proceed as the Admin
    And I login as administrator
    And I go to Marketing/Landing Pages
    And I click Edit "Test CMS Page" in grid
    And I fill in WYSIWYG "CMS Page Content" with "{{ widget('copyright') }}"
    When I save and close form
    Then I should see "Page has been saved" flash message

  Scenario: Ensure content widget cannot be deleted when used
    When I go to Marketing/ Content Widgets
    Then I should not see following actions for copyright in grid:
      | Delete |
    When I click View "copyright" in grid
    Then I should not see "Delete"

  Scenario: Check content widget usages grid
    When I go to Marketing/ Content Widgets
    And I click View "copyright" in grid
    Then number of records in "CMS Pages Content Widget Usages Grid" should be 1
    And I should see following "CMS Pages Content Widget Usages Grid" grid:
      | Title         |
      | Test CMS Page |
    And It should be 2 columns in "CMS Pages Content Widget Usages Grid" grid
    And I should see "Id" column in "CMS Pages Content Widget Usages Grid"
    When I click "Grid Settings" in "CMS Pages Content Widget Usages Grid" element
    And I click "Filters" tab in "CMS Pages Content Widget Usages Grid" element
    Then I should see following filters in the grid settings in exact order:
      | ID    |
      | Title |

  Scenario: Check content widget is rendered on store front
    Given I proceed as the Buyer
    And I am on the homepage
    When I click "Test CMS Page"
    Then Page title equals to "Test CMS Page"
    And I should see ". All rights reserved" in the "CMS Page" element

  Scenario: Ensure content widget can be deleted when there are no usages
    Given I proceed as the Admin
    And I go to Marketing/ Landing Pages
    And I click edit "Test CMS Page" in grid
    And I fill in WYSIWYG "CMS Page Content" with "another content"
    When I save and close form
    Then I should see "Page has been saved" flash message
    And I go to Marketing/ Content Widgets
    And I keep in mind number of records in list
    When I click Delete "copyright" in grid
    And I confirm deletion
    Then the number of records decreased by 1
    And I should not see "copyright"
