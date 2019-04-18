@ticket-BB-11014
@ticket-BB-14789
@fixture-OroOrderBundle:OrderAddressesFixture.yml
@fixture-OroCustomerBundle:CustomerUserAddressAmericanSamoaFixture.yml
Feature: Order Addresses
  ToDo: BAP-16103 Add missing descriptions to the Behat features

  Scenario: Check Order Billing and Shipping Address Labels
    Given I login as administrator
    And go to Sales/ Orders
    And click edit Order1 in grid
    Then I should see "Order Billing Address Select" with options:
      | Value                                                    | Type   |
      | Customer Address Book                                    | Group  |
      | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844             | Option |
      | User Address Book                                        | Group  |
      | ORO, EASTERN DISTRICT, PAGO PAGO AMERICAN SAMOA 96799    | Option |
      | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844             | Option |
      | Enter other address                                      | Option |
    And I should see "Order Shipping Address Select" with options:
      | Value                                                    | Type   |
      | Customer Address Book                                    | Group  |
      | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844             | Option |
      | User Address Book                                        | Group  |
      | ORO, EASTERN DISTRICT, PAGO PAGO AMERICAN SAMOA 96799    | Option |
      | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844             | Option |
      | Enter other address                                      | Option |
    When I fill "Order Form" with:
      | Billing Address | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
      | Billing Address | Enter other address                          |
    Then I should see an "Order Billing Address State Selector" element
    And I should not see an "Order Billing Address State Text Field" element
    When I fill "Order Form" with:
      | Billing Address | ORO, EASTERN DISTRICT, PAGO PAGO AMERICAN SAMOA 96799 |
      | Billing Address | Enter other address                                   |
    Then I should not see an "Order Billing Address State Selector" element
    And I should see an "Order Billing Address State Text Field" element
