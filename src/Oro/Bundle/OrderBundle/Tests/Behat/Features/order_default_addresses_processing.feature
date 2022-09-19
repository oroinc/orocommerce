@fixture-OroPricingBundle:PriceListsWithPrices.yml
@fixture-OroOrderBundle:OrderDefaultAddressesFixture.yml
@ticket-BB-21324

Feature: Order Default Addresses processing
  In order to create Order faster
  As an administrator
  I need to have ability to get default address filled for a selected Customer User

  Scenario: Open Create Order page
    Given I login as administrator
    And go to Sales/ Orders
    And click "Create Order"

  Scenario: Check order addresses for customer
    When I fill "Order Form" with:
      | Customer | first customer |
    Then Order Billing Address Select field is empty
    And Order Shipping Address Select field is empty

  Scenario: Check order addresses for customer user
    When I fill "Order Form" with:
      | Customer User | Amanda Cole |
    Then "Order Form" must contains values:
      | Billing Address  | ORO, 23400 Caldwell Road, ROCHESTER NY US 14608 |
      | Shipping Address | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844    |

  Scenario: Check order addresses are not changed on changes to line items
    When click "Add Product"
    And I fill "Order Form" with:
      | Product | PSKU1 |
    Then "Order Form" must contains values:
      | Billing Address  | ORO, 23400 Caldwell Road, ROCHESTER NY US 14608 |
      | Shipping Address | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844    |
    And I fill "Order Form" with:
      | Price | 50 |
    Then "Order Form" must contains values:
      | Billing Address  | ORO, 23400 Caldwell Road, ROCHESTER NY US 14608 |
      | Shipping Address | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844    |

  Scenario: Check order addresses for another customer user
    When I fill "Order Form" with:
      | Customer User | Nancy Sallee |
    Then "Order Form" must contains values:
      | Billing Address  | ORO, 2849 Junkins Avenue, ALBANY NY US 31707 |
      | Shipping Address | ORO, 2849 Junkins Avenue, ALBANY NY US 31707 |

  Scenario: Check order addresses for another customer
    When I fill "Order Form" with:
      | Customer | Wholesaler B |
    Then Order Billing Address Select field is empty
    And Order Shipping Address Select field is empty
