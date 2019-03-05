@ticket-BB-11014
@fixture-OroOrderBundle:OrderAddressesFixture.yml
Feature: Order Addresses
  ToDo: BAP-16103 Add missing descriptions to the Behat features

  Scenario: Check Order Billing and Shipping Address Labels
    Given I login as administrator
    And go to Sales/ Orders
    And click edit Order1 in grid
    Then I should see "Order Billing Address Select" with options:
      | Value                                           | Type   |
      | Customer Address Book                           | Group  |
      | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844    | Option |
      | User Address Book                               | Group  |
      | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844    | Option |
      | Enter other address                             | Option |
    Then I should see "Order Shipping Address Select" with options:
      | Value                                        | Type   |
      | Customer Address Book                        | Group  |
      | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 | Option |
      | User Address Book                            | Group  |
      | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 | Option |
      | Enter other address                          | Option |
