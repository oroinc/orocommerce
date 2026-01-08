@regression
@ticket-BB-26729
@fixture-OroRFPBundle:rfq_create_from_shopping_list_with_editing_line_items_on_storefront.yml
Feature: RFQ create from shopping list with editing line items on storefront
  In order create a RFQ from shopping list with additional or modified line items on storefront
  As a Buyer
  I need to be able to edit RFQ line items before submitting it

  Scenario: Create RFQ from shopping list by Amanda
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list "Shopping List 1"
    And I click "Request Quote"
    And I fill form with:
      | PO Number | PO00001 |

    And click "Edit RFQ Line Item"
    And fill "Frontstore RFQ Line Item Form1" with:
      | Quantity     | 10 |
      | Target Price | 90 |
    And click "Update Line Item"
    And I should see "PSKU1 - First Product QTY: 10 item Target Price $90.00 Listed Price: $100.01"

    And I click "Edit RFQ Line Item 2"
    And fill "Frontstore RFQ Line Item Form2" with:
      | SKU          | PSKU3 |
      | Quantity     | 33    |
      | Target Price | 250   |
    And click "Update Line Item"
    And I should see "PSKU3 - Third Product QTY: 33 item Target Price $250.00 Listed Price: $300.03"

    And I click "Add Another Product"
    And fill "Frontstore RFQ Line Item Form3" with:
      | SKU          | PSKU2 |
      | Quantity     | 22    |
      | Target Price | 180   |
    And click "Update Line Item"
    And I should see "PSKU2 - Second Product QTY: 22 item Target Price $180.00 Listed Price: $200.02"

    And I click "Submit Request"
    Then I should see "Request has been saved" flash message
