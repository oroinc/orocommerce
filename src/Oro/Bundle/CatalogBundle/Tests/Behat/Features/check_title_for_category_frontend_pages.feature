@ticket-BB-19940
@fixture-OroCatalogBundle:categories.yml
@fixture-OroLocaleBundle:ZuluLocalization.yml
Feature: Check title for category frontend pages
  For category pages must be the title and description of the category
  Name and description of category must be for the current locale

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I enable the existing localizations

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

  Scenario: Update category description
    Given I go to Products/Master Catalog
    And I click "Lighting Products"
    And I fill "Category Form" with:
      | Long Description | Long description: <img alt=\"cat1_wysiwyg_image\" src=\"{{ wysiwyg_image(13, 'f23ac0ff-2cc0-4d9e-8d00-78053a569a50') }}\"/> |
    And press "Zulu" in "Long Description" section
    And I fill "Category Form" with:
      | Long Description Localization 2 fallback selector | Custom                                                                                                                      |
      | Long Description Localization 2                   | Zulu description: <img alt=\"cat1_wysiwyg_image\" src=\"{{ wysiwyg_image(13, '4da44a71-1ffb-4bf4-bf4a-6d0d6c1781a7') }}\"/> |
    When I click "Save"
    Then I should see "Category has been saved" flash message

  Scenario: Check title, description and image on store front
    Given I proceed as the Buyer
    And I am on the homepage
    When I click "Lighting Products"
    Then Page title equals to "Lighting Products"
    And I should see "Long description:"
    And image "cat1 wysiwyg image" is loaded
    And I remember filename of the image "cat1 wysiwyg image"

  Scenario: Add another image to category description
    Given I proceed as the Admin
    And I go to Products/Master Catalog
    And I click "Lighting Products"
    And I fill "Category Form" with:
      | Long Description | Long description: <img alt=\"cat1_wysiwyg_image\" src=\"{{ wysiwyg_image(13, 'f23ac0ff-2cc0-4d9e-8d00-78053a569a50') }}\"/><img alt=\"cat2_wysiwyg_image\" src=\"{{ wysiwyg_image(14, 'c840eec3-4b10-4682-b5cd-4d51fe008b6f') }}\"/> |
    When I click "Save"
    Then I should see "Category has been saved" flash message

  Scenario: Check description new images on store front
    Given I proceed as the Buyer
    When I reload the page
    Then image "cat1 wysiwyg image" is loaded
    And image "cat2 wysiwyg image" is loaded
    And filename of the image "cat1 wysiwyg image" is as remembered

  Scenario: Check title, description and image on store front in Zulu localization
    Given I click "Localization Switcher"
    When I select "Zulu" localization
    Then I should see "Zulu description:"
    And image "cat1 wysiwyg image" is loaded
    And filename of the image "cat1 wysiwyg image" is as remembered
    And I should not see an "cat2 wysiwyg image" element
