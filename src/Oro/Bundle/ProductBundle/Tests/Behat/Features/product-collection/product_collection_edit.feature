@fixture-OroProductBundle:product_collection_edit.yml
Feature: Product collection edit
  In order to edit content node
  As an Administrator
  I want to have ability of editing Product Collection variant

  Scenario: Logged in as buyer and manager on different window sessions
    Given sessions active:
      | Admin  | first_session  |
      | Buyer  | second_session |

  Scenario: Product Collection can be edited
    Given I proceed as the Admin
    And I login as administrator
    And I set "Default Web Catalog" as default web catalog
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click "Content Variants"
    And I click on "First Content Variant Expand Button"
    And I type "PSKU" in "value"
    And I click on "Preview Results"
    Then I should see following grid:
      | SKU   | NAME      |
      | PSKU1 | Product 1 |
      | PSKU2 | Product 2 |
    And type "Some Custom Segment Name" in "Segment Name"

  Scenario: Edited Product Collection can be saved
    When I save form
    Then I should see "Content Node has been saved" flash message
    When I reload the page
    And I click on "First Content Variant Expand Button"
    Then I should see 1 element "Product Collection Variant Label"
    And I should see following grid:
      | SKU   | NAME      |
      | PSKU1 | Product 1 |
      | PSKU2 | Product 2 |

  Scenario: Modification of Product Collection segment's name, reflected in Manage Segments section
    Given I proceed as the Admin
    And I click "Cancel"
    When I go to Reports & Segments/ Manage Segments
    Then I should see "Some Custom Segment Name" in grid with following data:
      | Entity | Product |
      | Type   | Dynamic |

  Scenario: Edited Product Collection accessible at frontend
    Given I operate as the Buyer
    When I am on homepage
    Then I should see "PSKU1"
    And I should see "PSKU2"

  Scenario: Changed Content Node meta information are reflected and searchable on frontend
    Given I proceed as the Admin
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I fill "Content Node Form" with:
      | Meta Title       | AnotherCollectionMetaTitle       |
      | Meta Keywords    | AnotherCollectionMetaKeyword     |
      | Meta Description | AnotherCollectionMetaDescription |
    And I save form
    And I should see "Content Node has been saved" flash message
    Then I operate as the Buyer
    When I am on homepage
    Then Page meta keywords equals "AnotherCollectionMetaKeyword"
    And Page meta description equals "AnotherCollectionMetaDescription"
    And Page meta title equals "AnotherCollectionMetaTitle"
    When type "AnotherCollectionMetaKeyword" in "search"
    And I click "Search Button"
    Then I should see "PSKU1"

  Scenario: Products Collection is deletable
    Given I proceed as the Admin
    And I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    When I click on "Remove Variant Button"
    Then I should see 0 elements "Product Collection Variant Label"
    When I click on "Show Variants Dropdown"
    And I click "Add System Page"
    When I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Products are not searchable by Content Node meta information for deleted Product Collection
    Given I proceed as the Buyer
    And I am on homepage
    When type "AnotherCollectionMetaKeyword" in "search"
    And I click "Search Button"
    Then I should not see "PSKU1"
