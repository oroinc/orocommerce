@ticket-BB-20220
@fixture-OroProductBundle:ProductAttributesFixture.yml

Feature: Zoom for Product image attributes

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Edit product
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    And I set Images with:
      | Main | Listing | Additional |
      | 1    | 1       | 1          |
    And I click on "Digital Asset Choose"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat1.jpg |
      | Title | cat1.jpg |
    And I click "Upload"
    And click on cat1.jpg in grid
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check product image zoom
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I type "SKU123" in "search"
    And I click "Search Button"
    When I click "View Details" for "SKU123" product
    And I set window size to 1100x1080
    And I hover on "Product Main Image"
    Then I check element "Zoom Container" has width "564"
    And I set window size to 1920x1080
    And I hover on "Product Main Image"
    Then I check element "Zoom Container" has width "564"
