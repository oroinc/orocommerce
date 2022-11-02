@ticket-BB-19396
@fixture-OroCMSBundle:content_templates_in_cms_page.yml
@fixture-OroCMSBundle:content_templates_in_cms_page_tags.yml

Feature: Content Templates in CMS Page

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator

  Scenario: Check Content Templates tab
    Given I go to Marketing/Landing Pages
    And I click Edit "CMSPage1" in grid
    When I click on "GrapesJs Content Templates Tab"
    Then I should see a "GrapesJs Content Template 1" element
    And I should see following content templates categories in GrapesJs:
      | tag1 |
      | tag2 |
      | tag3 |
      | tag4 |
    And there are 1 content template in category "tag1"
    And there are 4 content templates in category "tag2"
    And there are 2 content templates in category "tag3"
    And there are 1 content template in category "tag4"

  Scenario: Add content template to CMS page
    When I drag and drop the block "GrapesJs Content Template 1" to the GrapesJs Wysiwyg Root Area
    And I save and close form
    Then I should see "Page has been saved" flash message

  Scenario: Check that page with content template is rendered on storefront
    Given I proceed as the Buyer
    And I am on the homepage
    When I click "CMSPage1"
    Then Page title equals to "CMSPage1"
    And I should see "TestContentTemplateContent1" in the "CMS Page" element
    And I should see picture "CMSPage1 Image" element

  Scenario: Delete the used content template
    Given I proceed as the Admin
    When I go to Marketing/Content Templates
    And I click delete "TestContentTemplate1" in grid
    And I click "Yes, Delete" in confirmation dialogue
    Then I should see "Content Template deleted" flash message

  Scenario: Check that page with content template is still rendered on storefront
    Given I proceed as the Buyer
    When I reload the page
    Then Page title equals to "CMSPage1"
    And I should see "TestContentTemplateContent1" in the "CMS Page" element
    And I should see picture "CMSPage1 Image" element
