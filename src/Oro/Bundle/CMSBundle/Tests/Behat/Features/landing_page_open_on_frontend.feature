@ticket-BB-15244
@ticket-BB-19940
Feature: Landing page open on frontend
  In order to see landing page info
  As buyer
  I need to be able to open landing page on frontend

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create digital assets
    Given I proceed as the Admin
    And I login as administrator
    And I go to Marketing/ Digital Assets
    And I click "Create Digital Asset"
    When I fill "Digital Asset Form" with:
      | File  | cat1.jpg |
      | Title | cat1.jpg |
    And I save form
    Then I should see "Digital Asset has been saved" flash message
    And I go to Marketing/ Digital Assets
    And I click "Create Digital Asset"
    When I fill "Digital Asset Form" with:
      | File  | cat2.jpg |
      | Title | cat2.jpg |
    And I save form
    Then I should see "Digital Asset has been saved" flash message

  Scenario: Create Landing Page
    When I go to Marketing/Landing Pages
    And click "Create Landing Page"
    And I fill in Landing Page Titles field with "Other page"
    Then I should see URL Slug field filled with "other-page"
    And I fill in WYSIWYG "CMS Page Content" with "GrapesJS content: <img alt=\"cat1_wysiwyg_image\" src=\"{{ wysiwyg_image(13, 'f23ac0ff-2cc0-4d9e-8d00-78053a569a50') }}\"/><a title=\"cat1_wysiwyg_file\" href=\"{{ wysiwyg_file(13, '902dfb57-57c0-4a2f-88bf-adf365d74895') }}\">File of cat1</a>"
    When I save form
    Then I should not see text matching "\{\{ wysiwyg_image\(" in WYSIWYG editor
    And I should not see text matching "\{\{ wysiwyg_file\(" in WYSIWYG editor
    When I save and close form
    Then I should see "Page has been saved" flash message
    And image "cat1 wysiwyg image" is loaded
    And I remember filename of the image "cat1 wysiwyg image"
    And I remember filename of the file "cat1 wysiwyg file"
    And I click on "More Link"
    And I should see "File of cat1"

    When I go to System/Frontend Menus
    And click "view" on row "commerce_main_menu" in grid
    And click "Create Menu Item"
    And I fill "Commerce Menu Form" with:
      | Title       | Other page |
      | Target Type | URI        |
      | URI         | other-page |
    And I save form
    Then I should see "Menu item saved successfully" flash message

  Scenario: Check content and image are shown on store front
    When I proceed as the Buyer
    And I am on the homepage
    Then I should see "Other page"
    When I click "Other page"
    Then Page title equals to "Other page"
    And I should see "GrapesJS content"
    And image "cat1 wysiwyg image" is loaded
    And filename of the image "cat1 wysiwyg image" is as remembered
    And filename of the file "cat1 wysiwyg file" is as remembered
    And I should see "File of cat1"

  Scenario: Add incorrect twig function
    Given I proceed as the Admin
    And I go to Marketing/Landing Pages
    And I click Edit "Other page" in grid
    And I fill in WYSIWYG "CMS Page Content" with "GrapesJS content: {{ test(123) }} <img alt=\"cat1_wysiwyg_image\" src=\"{{ wysiwyg_image(13, 'f23ac0ff-2cc0-4d9e-8d00-78053a569a50') }}\"/><a title=\"cat1_wysiwyg_file\" href=\"{{ wysiwyg_file(13, '902dfb57-57c0-4a2f-88bf-adf365d74895') }}\">File of cat1</a><img alt=\"cat2_wysiwyg_image\" src=\"{{ wysiwyg_image(14, 'c840eec3-4b10-4682-b5cd-4d51fe008b6f') }}\"/>"
    And I save form
    Then I should see "The entered content contains invalid twig constructions."
    And I should not see text matching "\{\{ wysiwyg_image\(" in WYSIWYG editor
    And I should not see text matching "\{\{ wysiwyg_file\(" in WYSIWYG editor

  Scenario: Add another image
    When I fill in WYSIWYG "CMS Page Content" with "GrapesJS content: <img alt=\"cat1_wysiwyg_image\" src=\"{{ wysiwyg_image(13, 'f23ac0ff-2cc0-4d9e-8d00-78053a569a50') }}\"/><a title=\"cat1_wysiwyg_file\" href=\"{{ wysiwyg_file(13, '902dfb57-57c0-4a2f-88bf-adf365d74895') }}\">File of cat1</a><img alt=\"cat2_wysiwyg_image\" src=\"{{ wysiwyg_image(14, 'c840eec3-4b10-4682-b5cd-4d51fe008b6f') }}\"/>"
    When I save form
    Then I should not see text matching "\{\{ wysiwyg_image\(" in WYSIWYG editor
    And I should not see text matching "\{\{ wysiwyg_file\(" in WYSIWYG editor
    When I save and close form
    Then I should see "Page has been saved" flash message
    And image "cat1 wysiwyg image" is loaded
    And image "cat2 wysiwyg image" is loaded
    And filename of the image "cat1 wysiwyg image" is as remembered
    And filename of the file "cat1 wysiwyg file" is as remembered
    And I remember filename of the image "cat2 wysiwyg image"
    And I should see "File of cat1"

  Scenario: Check another image is shown store front
    Given I proceed as the Buyer
    When I reload the page
    Then I should see "GrapesJS content"
    And image "cat1 wysiwyg image" is loaded
    And image "cat2 wysiwyg image" is loaded
    And filename of the image "cat1 wysiwyg image" is as remembered
    And filename of the image "cat2 wysiwyg image" is as remembered
    And filename of the file "cat1 wysiwyg file" is as remembered
    And I should see "File of cat1"

  Scenario: Change digital asset image
    Given I proceed as the Admin
    And I go to Marketing/ Digital Assets
    And I click edit "cat1.jpg" in grid
    And I fill "Digital Asset Form" with:
      | File | blue-dot.jpg |
    When I save and close form
    Then I should see "Digital Asset has been saved" flash message

  Scenario: Check new image is displayed on store front
    Given I proceed as the Buyer
    When I reload the page
    Then I should see "GrapesJS content"
    And image "cat1 wysiwyg image" is loaded
    And image "cat2 wysiwyg image" is loaded
    And filename of the image "cat1 wysiwyg image" is not as remembered
    And filename of the image "cat2 wysiwyg image" is as remembered
    And filename of the file "cat1 wysiwyg file" is not as remembered
    And I should see "File of cat1"
