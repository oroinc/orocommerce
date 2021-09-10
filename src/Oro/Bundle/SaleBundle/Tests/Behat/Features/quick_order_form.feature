@ticket-BB-20787
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroProductBundle:products.yml

Feature: Quick order form
  In order to provide customers with ability to quickly start an order
  As customer
  Should be able to use the quick order form

  Scenario: Check quick order form field clearing
    Given I login as AmandaRCole@example.org buyer
    And I click "Quick Order Form"
    And I fill "QuickAddForm" with:
      | SKU1 | PSKU1 - Product1 |
    And I wait for products to load
    And "QuickAddForm" must contains values:
      | SKU1      | PSKU1 - Product1 |
      | QTY1      | 1                |
      | UNIT1     | each             |
      | SUBTOTAL1 | $10.00           |
    And I fill "QuickAddForm" with:
      | SKU1 |  |
    And I click on empty space
    When I fill "QuickAddForm" with:
      | SKU1 | PSKU1 - Product1 |
    And I wait for products to load
    Then "QuickAddForm" must contains values:
      | SKU1      | PSKU1 - Product1 |
      | QTY1      | 1                |
      | UNIT1     | each             |
      | SUBTOTAL1 | $10.00           |
