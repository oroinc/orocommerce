@regression
@ticket-BB-11014
@ticket-BB-14789
@ticket-BB-27842
@fixture-OroSaleBundle:Quote.yml
@fixture-OroCustomerBundle:CustomerUserAddressAmericanSamoaFixture.yml
@fixture-OroSaleBundle:QuoteWithAddressBookShippingAddressFixture.yml
Feature: Quote Address

  Scenario: Check Quote Shipping Address Labels
    Given I login as administrator
    And go to Sales/ Quotes
    And click edit Quote1 in grid
    Then I should see "Quote Shipping Address Select" with options:
      | Value                                                 | Type   |
      | Customer Address Book                                 | Group  |
      | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844          | Option |
      | User Address Book                                     | Group  |
      | ORO, EASTERN DISTRICT, PAGO PAGO AMERICAN SAMOA 96799 | Option |
      | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844          | Option |
      | Enter other address                                   | Option |
    When I fill "Quote Form" with:
      | Shipping Address | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
      | Shipping Address | Enter other address                          |
    Then I should see an "Quote Shipping Address State Selector" element
    And I should not see an "Quote Shipping Address State Text Field" element
    When I fill "Quote Form" with:
      | Shipping Address | ORO, EASTERN DISTRICT, PAGO PAGO AMERICAN SAMOA 96799 |
      | Shipping Address | Enter other address                                   |
    Then I should not see an "Quote Shipping Address State Selector" element
    And I should see an "Quote Shipping Address State Text Field" element
    And I click "Cancel"

  Scenario: Update quote shipping address entered manually when previously selected from address book
    Given I login as administrator
    And go to Sales/ Quotes
    And click edit QuoteAddrBook in grid
    Then "Quote Form" must contains values:
      | Shipping Address | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
    When I fill "Quote Form" with:
      | Shipping Address              | Enter other address     |
      | Shipping Address Organization | New Manual Organization |
      | Shipping Address Street       | New Manual Street 123   |
      | Shipping Address City         | New Manual City         |
      | Shipping Address Postal Code  | 90210                   |
    And I click "Submit"
    And I click "Save" in modal window
    Then I should see "Quote #QuoteAddrBook successfully updated" flash message
    And click "Edit"
    Then "Quote Form" must contains values:
      | Shipping Address Organization | New Manual Organization |
      | Shipping Address Street       | New Manual Street 123   |
      | Shipping Address City         | New Manual City         |
      | Shipping Address Postal Code  | 90210                   |
