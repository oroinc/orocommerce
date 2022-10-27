@ticket-BB-11014
@ticket-BB-14789
@ticket-BB-16591
@fixture-OroOrderBundle:OrderAddressesFixture.yml
@fixture-OroCustomerBundle:CustomerUserAddressAmericanSamoaFixture.yml
Feature: Order Addresses

  Scenario: Check Order Billing and Shipping Address Labels
    Given I login as administrator
    And go to Sales/ Orders
    And click edit PO1 in grid
    When click "Billing Address"
    Then I should see "Order Billing Address Select" with options:
      | Value                                                 | Type   |
      | Customer Address Book                                 | Group  |
      | ORO, 23555 Hard Road, YORK NY US 12103                | Option |
      | User Address Book                                     | Group  |
      | ORO, EASTERN DISTRICT, PAGO PAGO AMERICAN SAMOA 96799 | Option |
      | ORO, 23400 Caldwell Road, ROCHESTER NY US 14608       | Option |
      | Enter other address                                   | Option |
    And "Order Form" must contains values:
      | Billing Address First name   | John          |
      | Billing Address Last name    | Doo           |
      | Billing Address Organization | Acme          |
      | Billing Address Country      | United States |
      | Billing Address Street       | Street 1      |
      | Billing Address City         | Los Angeles   |
      | Billing Address State        | California    |
      | Billing Address Postal Code  | 90001         |
    When I fill "Order Form" with:
      | Billing Address | ORO, 23400 Caldwell Road, ROCHESTER NY US 14608 |
      | Billing Address | Enter other address                             |
    Then I should see an "Order Billing Address State Selector" element
    And I should not see an "Order Billing Address State Text Field" element
    When fill "Order Form" with:
      | Billing Address  | ORO, 23555 Hard Road, YORK NY US 12103 |
    Then "Order Form" must contains values:
      | Billing Address Organization | ORO             |
      | Billing Address Country      | United States   |
      | Billing Address Street       | 23555 Hard Road |
      | Billing Address City         | York            |
      | Billing Address State        | New York        |
      | Billing Address Postal Code  | 12103           |
    When click "Shipping Address"
    Then I should see "Order Shipping Address Select" with options:
      | Value                                                 | Type   |
      | Customer Address Book                                 | Group  |
      | ORO, 905 New Street, GAMBURG FL US 33987              | Option |
      | User Address Book                                     | Group  |
      | ORO, EASTERN DISTRICT, PAGO PAGO AMERICAN SAMOA 96799 | Option |
      | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844          | Option |
      | Enter other address                                   | Option |
    And "Order Form" must contains values:
      | Shipping Address First name   | John          |
      | Shipping Address Last name    | Doo           |
      | Shipping Address Organization | Acme          |
      | Shipping Address Country      | United States |
      | Shipping Address Street       | NewStreet 1   |
      | Shipping Address City         | London        |
      | Shipping Address State        | California    |
      | Shipping Address Postal Code  | 90002         |
    When I fill "Order Form" with:
      | Billing Address | ORO, EASTERN DISTRICT, PAGO PAGO AMERICAN SAMOA 96799 |
      | Billing Address | Enter other address                                   |
    Then I should not see an "Order Billing Address State Selector" element
    And I should see an "Order Billing Address State Text Field" element
    When fill "Order Form" with:
      | Shipping Address              | Enter other address |
      | Shipping Address First name   | John                |
      | Shipping Address Last name    | Doo                 |
      | Shipping Address Organization | Acme                |
      | Shipping Address Country      | United States       |
      | Shipping Address Street       | Old Street          |
      | Shipping Address City         | Moskow              |
      | Shipping Address State        | California          |
      | Shipping Address Postal Code  | 90005               |
    And I save form
    And click "Save on conf window"
    Then I should see "Order has been saved" flash message
    And click "Shipping Address"
    And "Order Form" must contains values:
      | Shipping Address First name   | John                |
      | Shipping Address Last name    | Doo                 |
      | Shipping Address Organization | Acme                |
      | Shipping Address Country      | United States       |
      | Shipping Address Street       | Old Street          |
      | Shipping Address City         | Moskow              |
      | Shipping Address State        | California          |
      | Shipping Address Postal Code  | 90005               |
