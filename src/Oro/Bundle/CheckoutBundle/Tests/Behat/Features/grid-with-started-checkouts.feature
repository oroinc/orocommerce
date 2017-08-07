@fixture-OroCheckoutBundle:CheckoutDefaultFixture.yml

Feature: Started checkouts should be on Open Orders Grid
  In order to see started checkouts on frontend store
  As a Buyer
  I need to have an ability to see started checkouts in Open Orders Grid on front store

  Scenario: See Open Orders with child Customer by frontend administrator.
    Given I signed in as NancyJSallee@example.org on the store frontend
    When click "Orders"
    Then I should see following records in "Open Orders Grid":
      | CheckoutWithParentCustomer |
      | CheckoutWithChildCustomer |

  Scenario: Don't see Open Orders with Customer by frontend administrator of child customer.
    Given I signed in as RuthWMaxwell@example.org on the store frontend
    When click "Orders"
    Then I should not see "CheckoutWithParentCustomer"
    And I should see following records in "Open Orders Grid":
      | CheckoutWithChildCustomer |

  Scenario: Don't see Open Orders by frontend administrator of another customer.
    Given I signed in as JuanaPBrzezinski@example.net on the store frontend
    When click "Orders"
    Then there is no records in "Open Orders Grid"

  Scenario: See Open Orders with Customer by creator (buyer).
    Given I signed in as AmandaRCole@example.org on the store frontend
    And click "Orders"
    Then I should not see "CheckoutWithChildCustomer"
    And I should see following records in "Open Orders Grid":
      | CheckoutWithParentCustomer |
