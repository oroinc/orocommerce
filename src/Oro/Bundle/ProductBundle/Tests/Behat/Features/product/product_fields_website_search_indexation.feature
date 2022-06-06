@ticket-BB-16582

Feature: Product fields website search indexation
  In order to have actual data in the website search indes
  As an Administrator
  I want to have ability to create and change various aspects of the product and get them updated in the search index

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Prepare test category
    Given I proceed as the Admin
    And I login as administrator
    When go to Products/ Master Catalog
    And click "Create Category"
    And fill "Create Category Form" with:
      | Title | Phones |
    And click "Save"
    Then should see "Category has been saved" flash message

  Scenario: Prepare test product
    When go to Products/ Products
    And click "Create Product"
    And fill form with:
      | Type | Simple |
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU    | Xiaomi_Redmi_3S_sku |
      | Name   | Xiaomi Redmi 3S     |
      | Status | Enable              |
    And save and close form
    Then should see "Product has been saved" flash message
    When I click "Edit"
    And click "Phones"
    And click "AddPrice"
    And fill "Product Price Form" with:
      | Price List | Default Price List |
      | Quantity   | 1                  |
      | Value      | 150                |
    And save and close form
    Then should see "Product has been saved" flash message

  Scenario: Check Product availability for customer user
    Given I proceed as the User
    And I am on the homepage
    When click "Phones"
    Then should see "Xiaomi Redmi 3S"
