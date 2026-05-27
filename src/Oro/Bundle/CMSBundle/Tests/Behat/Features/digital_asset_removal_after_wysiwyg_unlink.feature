@regression
@ticket-BB-27318

Feature: Digital asset removal after WYSIWYG unlink
  In order to keep Digital Assets management clean
  As an Administrator
  I need to be able to delete a digital asset after the WYSIWYG image that referenced it was removed from the page

  Scenario: Create a landing page with an image from a newly uploaded digital asset
    Given I login as administrator
    And I go to Marketing / Landing Pages
    When I click "Create Landing Page"
    And I fill in Landing Page Titles field with "Landing page with image"
    And I add new component "Image" from panel to editor area
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat1.jpg             |
      | Title | Unused image asset |
    And I click "Upload"
    And I click on cat1.jpg in grid
    And I save form
    Then I should see "Page has been saved" flash message

  Scenario: Remove the image component from the landing page
    When I select component in canvas by tree:
      | picture | 1 |
      | image   | 1 |
    And I click on "Delete" action for selected component
    And I save form
    Then I should see "Page has been saved" flash message

  Scenario: Unused digital asset can be deleted from the grid
    When I go to Marketing/ Digital Assets
    Then I should see following actions for Unused image asset in grid:
      | Delete |
    When I click delete "Unused image asset" in grid
    And I click "Yes, Delete"
    Then I should see "Digital asset deleted" flash message
    And I should not see "Unused image asset"
