@ticket-BB-18698
@regression
@waf-skip

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
      | Title            | SubCategory                                |
      | Long Description | <iframe src=\"http://example.org\"></iframe> |
    And click "Short Description"
    And press "English" in "Short Description" section
    When fill "Category Form" with:
      | Short Description English (United States) fallback selector | Custom |
    When I click "Save"
    Then I should see "Please remove not permitted HTML-tags in the content field: - \"src\" attribute on \"<iframe>\" should be removed (near <iframe src=\"http://examp...)." error message
    When fill "Category Form" with:
      | Long Description | Sample content |
    And I click "Save"
    When I click "Short Description"
    And press "English" in "Short Description" section
    Then the "Custom" option from "Short Description English (United States) fallback selector" is selected
