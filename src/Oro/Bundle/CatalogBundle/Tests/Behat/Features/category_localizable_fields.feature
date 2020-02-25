@ticket-BB-18698
@regression

Feature: Category localizable fields
  In order to have localizable categories
  As an Administrator
  I want to be able to use localizable fields

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create new subcategory with empty custom short description
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products/Master Catalog
    And I click "All Products"
    And I click "Create Subcategory"
    And I fill "Category Form" with:
      | Title    | SubCategory  |
    And click "Short Description"
    And press "English" in "Short Description" section
    When fill "Category Form" with:
      | Short Description Localization 1 fallback selector | Custom |
    And I click "Save"
    And click "Short Description"
    And press "English" in "Short Description" section
    Then the "Custom" option from "Short Description Localization 1 fallback selector" is selected
