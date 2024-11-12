@regression
@feature-BB-23028

Feature: Product Search Autocomplete Suggestions CRUD

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator

  Scenario Outline: Create a product with string and numeric SKU
    Given I proceed as the Admin
    And I go to Products/ Products
    And I click "Create Product"
    And click "Continue"
    When fill "Create Product Form" with:
      | SKU    | <SKU>   |
      | Name   | <Name>  |
      | Status | Enabled |
    And I save and close form
    Then I should see "Product has been saved" flash message
    Examples:
      | SKU   | Name                           |
      | PSKU1 | New Product with Original Name |
      | 12345 | Product with Numeric SKU 12345 |

  Scenario: Check the search suggestion autocomplete contains suggestions
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I go to the homepage
    When I type "12345" in "search"
    Then I should see an "Search Autocomplete" element
    And I should see "12345" in the "Search Suggestion Autocomplete Item" element
    When I type "New" in "search"
    Then I should see an "Search Autocomplete" element
    And I should see an "Search Suggestion Autocomplete Item" element
    And I should see 4 elements "Search Suggestion Autocomplete Item"
    And I should see "new" in the "Search Suggestion Autocomplete Item" element

  Scenario: Update a product
    Given I proceed as the Admin
    And I go to Products/ Products
    And click edit "PSKU1" in grid
    When fill "Product Form" with:
      | Name | Updated Product with Original Name |
    And I save and close form
    And I click "Apply" in modal window
    Then I should see "Product has been saved" flash message

  Scenario: Check the search suggestion autocomplete contains updated suggestions
    Given I proceed as the Buyer
    And I go to the homepage
    When I type "Updated" in "search"
    Then I should see an "Search Autocomplete" element
    And I should see an "Search Suggestion Autocomplete Item" element
    And I should see 4 elements "Search Suggestion Autocomplete Item"
    And I should see "updated" in the "Search Suggestion Autocomplete Item" element
