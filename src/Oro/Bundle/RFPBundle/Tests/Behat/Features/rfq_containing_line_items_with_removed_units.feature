@fixture-OroRFPBundle:RFQ_with_removed_unit.yml
@ticket-BB-14067
Feature: RFQ containing line items with removed units
  In order to process RFQ
  As an Administrator
  I want to be able to have access to RFQs that contains line items with removed units

  Scenario: View RFQ containing line items with removed units
    Given I login as administrator
    And I go to Sales/Requests For Quote
    And I click view 0111 in grid
    Then I should see RFQ with:
      | PO Number       | 0111      |
      | Internal Status | Open      |
      | Customer Status | Submitted |
    And I should see "SKU123 product1 1 item - removed"
