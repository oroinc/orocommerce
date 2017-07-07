@fixture-OroCheckoutBundle:CheckoutDefaultFixture.yml
Feature: Checkout Default

  Scenario: See Open Orders with child Customer by frontend administrator.
    Given I signed in as NancyJSallee@example.org on the store frontend
    When click "Orders"
    Then I should see following records in grid:
      | CheckoutWithParentCustomer |
      | CheckoutWithChildCustomer |

  Scenario: Don't see Open Orders with Customer by frontend administrator of child customer.
    Given I signed in as RuthWMaxwell@example.org on the store frontend
    When click "Orders"
    Then I should not see "CheckoutWithParentCustomer"
    And I should see following records in grid:
      | CheckoutWithChildCustomer |

  Scenario: Don't see Open Orders by frontend administrator of another customer.
    Given I signed in as JuanaPBrzezinski@example.net on the store frontend
    When click "Orders"
    Then I should see "No entity were found to match your search. Try modifying your search criteria..."

  Scenario: See Open Orders with Customer by creator (buyer).
    Given I signed in as AmandaRCole@example.org on the store frontend
    And click "Orders"
    Then I should not see "CheckoutWithChildCustomer"
    And I should see following records in grid:
      | CheckoutWithParentCustomer |
