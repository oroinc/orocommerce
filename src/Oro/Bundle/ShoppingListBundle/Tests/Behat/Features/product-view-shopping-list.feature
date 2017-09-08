@fixture-OroShoppingListBundle:product_shopping_list.yml
Feature:
  In order to edit content node
  As an Buyer
  I want to have ability to create quote on product page

  Scenario: Requests a quote button exists in shopping list dropdown after changing units
    Given I signed in as AmandaRCole@example.org on the store frontend
    And type "PSKU1" in "search"
    And click "Search Button"
    And click "View Details" for "PSKU1" product
    And I click on "Shopping List Dropdown"
    And I should see "Request A Quote Button" element inside "Product Shopping List Dropdown" element
    When I fill "Product Shopping List Form" with:
      | Unit | set |
    And I click on "Shopping List Dropdown"
    And I should see "Request A Quote Button" element inside "Product Shopping List Dropdown" element
    And I click "Request A Quote Button"
    And I should see "REQUEST A QUOTE" in the "RequestForQuoteTitle" element
