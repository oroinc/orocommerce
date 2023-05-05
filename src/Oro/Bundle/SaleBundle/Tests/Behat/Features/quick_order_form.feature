@ticket-BB-20787
@fixture-OroProductBundle:products.yml

Feature: Quick order form
  In order to provide customers with ability to quickly start an order
  As customer
  Should be able to use the quick order form

  Scenario: Check quick order form field clearing
    Given I login as AmandaRCole@example.org buyer
    And I click "Quick Order Form"
    And I fill "Quick Order Form" with:
      | SKU1 | PSKU1 - Product1 |
    And I wait for products to load
    And "Quick Order Form" must contains values:
      | SKU1      | PSKU1 - Product1 |
      | QTY1      | 1                |
      | UNIT1     | each             |
      | SUBTOTAL1 | $10.00           |
    And I fill "Quick Order Form" with:
      | SKU1 |  |
    And I click on empty space
    When I fill "Quick Order Form" with:
      | SKU1 | PSKU1 - Product1 |
    And I wait for products to load
    Then "Quick Order Form" must contains values:
      | SKU1      | PSKU1 - Product1 |
      | QTY1      | 1                |
      | UNIT1     | each             |
      | SUBTOTAL1 | $10.00           |

  Scenario: Delete row button appears when row inputs have some value entered
    When I click "Quick Order Form"
    Then I should not see an "Quick Order Form > DeleteRow1" element
    And I should not see an "Quick Order Form > DeleteRow2" element
    When I fill "Quick Order Form" with:
      | SKU1 | Foo |
      | QTY2 | 7   |
    Then I should see an "Quick Order Form > DeleteRow1" element
    And I should see an "Quick Order Form > DeleteRow2" element
