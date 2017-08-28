@fixture-OroProductBundle:product_shopping_list.yml
Feature:
  In order to edit content node
  As an Buyer
  I want to have ability to create quote on product page

  Scenario: Requests a quote button exists in shopping list dropdown after changing units
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I open product with sku "PSKU1" on the store frontend
    And I click on "Shopping List Dropdown"
    And I should see "Request A Quote Button" element inside "Product Shopping List Dropdown" element
    When I fill "Product Shopping List Form" with:
      | Unit | set |
    And I click on "Shopping List Dropdown"
    And I should see "Request A Quote Button" element inside "Product Shopping List Dropdown" element
