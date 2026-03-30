@ticket-BB-26425
@fixture-OroProductBundle:product_search_by_name.yml

Feature: Product search in back office select field
  In order to find products quickly in back office
  As an administrator
  I should be able to search products by name with multiple words

  Scenario: Search for product by exact name
    Given I login as administrator
    And I go to Sales / Orders
    And I click "Create Order"

  Scenario Outline: Check autocomplete
    When I fill "Order Form" with:
      | Customer      | Customer1   |
      | Customer User | Amanda Cole |
    Then I should see the following options for "Product" select in form "Order Edit Add Line Item Form" pre-filled with "<Pre-filled Phrase>":
      | <Expected Option> |

  Examples:
    | Pre-filled Phrase | Expected Option               |
    | Yellow Pine       | YELLOW-PINE-SKU - Yellow Pine |
    | Yellow            | YELLOW-PINE-SKU - Yellow Pine |
    | Pine              | YELLOW-PINE-SKU - Yellow Pine |
    | Pine Yellow       | YELLOW-PINE-SKU - Yellow Pine |
    | Yello Pine        | YELLOW-PINE-SKU - Yellow Pine |
    | Yellow  Pine      | YELLOW-PINE-SKU - Yellow Pine |
