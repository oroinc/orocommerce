@behat-test-env
@ticket-BB-21219
Feature: Image with svg mime type on Landing page
  In order to see landing page info
  As a buyer
  I need to be able to see image with unsupported mime type on landing page on storefront

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create digital assets
    Given I proceed as the Admin
    When I login as administrator
    And I go to Marketing/ Digital Assets
    And I click "Create Digital Asset"
    And I fill "Digital Asset Form" with:
      | File  | tiger.svg |
      | Title | tiger.svg |
    And I save form
    Then I should see "Digital Asset has been saved" flash message

  Scenario: Create Landing Page
    When I go to Marketing/Landing Pages
    And click "Create Landing Page"
    And I fill in Landing Page Titles field with "Other page"
    Then I should see URL Slug field filled with "other-page"
    When I fill in WYSIWYG "CMS Page Content" with "<picture><source srcset=\"{{ wysiwyg_image('13','86a7fe19-9de6-48f5-aa87-32f9eac7c62a','wysiwyg_original','webp') }}\" type=\"image/webp\"><img src=\"{{ wysiwyg_image('13','ba1e8f90-3300-4d11-996b-334851663661','wysiwyg_original','') }}\" alt=\"example1_svg_wysiwyg_image\"></picture>"
    And I save and close form
    Then I should see "Page has been saved" flash message
    And image "Example1 svg wysiwyg image" is loaded
    And "Example1 svg wysiwyg picture source" element "srcset" attribute should contain "-tiger.svg"
    And "Example1 svg wysiwyg picture source" element "srcset" attribute should not contain "-tiger.svg.webp"

    When I go to System/Frontend Menus
    And click "view" on row "commerce_main_menu" in grid
    And click "Create Menu Item"
    And I fill "Commerce Menu Form" with:
      | Title       | Other page |
      | Target Type | URI        |
      | URI         | other-page |
    And I save form
    Then I should see "Menu item saved successfully" flash message

  Scenario: Check content and image are shown on storefront
    When I proceed as the Buyer
    And I am on the homepage
    Then I should see "Other page"
    When I click "Other page"
    Then Page title equals to "Other page"
    And image "Example1 svg wysiwyg image" is loaded
    And "Example1 svg wysiwyg picture source" element "srcset" attribute should contain "-tiger.svg"
    And "Example1 svg wysiwyg picture source" element "srcset" attribute should not contain "-tiger.svg.webp"
    And expect public image files created:
      | tiger.svg |
