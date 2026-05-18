@ticket-BB-27179
@fixture-OroProductBundle:product_with_price.yml

Feature: Order line item taxes recalculate on price change in edit mode

  Scenario: Taxes recalculate after changing line item price in order edit form
    Given I login as administrator
    When I go to Sales/Orders
    And click "Create Order"
    And I should see "You are editing a draft. Your changes will still be here if you leave or reload this page. Click Save when you are done editing."
    And I fill "Order Form" with:
      | Customer      | first customer |
      | Customer User | Amanda Cole    |
    And fill "Order Line Item Draft Create Form" with:
      | Product | PSKU1 |
      | Price   | 15    |
    And click "Add Product"
    And I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

    When I click edit PSKU1 in grid
    And I click "View taxes & discounts"
    Then I see the next line item taxes for backoffice order edit for "PSKU1":
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $15.00    | $15.00    | $0.00      |
      | Row Total  | $15.00    | $15.00    | $0.00      |

    When fill "Order Line Item Draft Edit Form" with:
      | Price | 11 |
    Then I see the next line item taxes for backoffice order edit for "PSKU1":
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $11.00    | $11.00    | $0.00      |
      | Row Total  | $11.00    | $11.00    | $0.00      |
