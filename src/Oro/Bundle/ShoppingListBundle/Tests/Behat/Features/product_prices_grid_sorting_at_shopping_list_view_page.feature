@ticket-BB-10008
@fixture-OroShoppingListBundle:ShoppingListWithItemsFixture.yml

Feature: Product prices grid sorting at shopping list view page
  In order to have ability to observe product prices in shopping list
  As an Administrator
  I want to see & sort datagrid with product prices at shopping list view page

  Scenario: Check product prices at view page of Shopping List
    Given I login as administrator
    And I go to Sales/ Shopping Lists

    When I click view Shopping List 1 in grid
    Then I should see following "Shopping list Line items Grid" grid:
      | SKU | Product  | Quantity | Unit  |
      | AA1 | Product1 | 5        | item  |
      | BB2 | Product2 | 10       | piece |

    When I sort grid by Unit
    Then I should see following "Shopping list Line items Grid" grid:
      | SKU | Product  | Quantity | Unit  |
      | AA1 | Product1 | 5        | item  |
      | BB2 | Product2 | 10       | piece |

    When I sort grid by Unit again
    Then I should see following "Shopping list Line items Grid" grid:
      | SKU | Product  | Quantity | Unit  |
      | BB2 | Product2 | 10       | piece |
      | AA1 | Product1 | 5        | item  |
