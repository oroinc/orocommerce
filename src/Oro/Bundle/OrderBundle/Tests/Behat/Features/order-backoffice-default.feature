@fixture-OrderBackofficeDefaultFixture.yml
Feature: Order Backoffice Default

  Scenario: See Order without Customer User by frontend administrator.
    Given I login as administrator
    And go to Sales/Orders
    And click view OrderWithoutCustomerUser in grid
    Then I should see Order with:
      | Order Number | OrderWithoutCustomerUser |
      | Customer | first customer |
      | Customer User | N/A |

    When I signed in as NancyJSallee@example.org on the store frontend
    And click "Orders"
    Then I should see following records in grid:
      | OrderWithoutCustomerUser |
    When I click view OrderWithoutCustomerUser in grid
    Then I should see "Order #OrderWithoutCustomerUser"

  Scenario: See Order with child Customer by frontend administrator.
    Given I login as administrator
    And go to Sales/Orders
    And click view OrderWithChildCustomerAndWithCustomerUser in grid
    Then I should see Order with:
      | Order Number | OrderWithChildCustomerAndWithCustomerUser |
      | Customer | child of first customer |
      | Customer User | Ruth Maxwell |
    And go to Sales/Orders
    And click view OrderWithChildCustomerAndWithoutCustomerUser in grid
    Then I should see Order with:
      | Order Number | OrderWithChildCustomerAndWithoutCustomerUser |
      | Customer | child of first customer |
      | Customer User | N/A |

    When I signed in as NancyJSallee@example.org on the store frontend
    And click "Orders"
    Then I should see following records in grid:
      | OrderWithChildCustomerAndWithCustomerUser |
      | OrderWithChildCustomerAndWithoutCustomerUser |
    When I click view OrderWithChildCustomerAndWithCustomerUser in grid
    Then I should see "Order #OrderWithChildCustomerAndWithCustomerUser"
    When click "Orders"
    And I click view OrderWithChildCustomerAndWithoutCustomerUser in grid
    Then I should see "Order #OrderWithChildCustomerAndWithoutCustomerUser"

  Scenario: Don't see Order with Customer by frontend administrator of child customer.
    Given I login as administrator
    And go to Sales/Orders
    And click view OrderWithoutCustomerUser in grid
    Then I should see Order with:
      | Order Number | OrderWithoutCustomerUser |
      | Customer | first customer |
      | Customer User | N/A |

    When I signed in as RuthWMaxwell@example.org on the store frontend
    And click "Orders"
    Then I should not see "OrderWithoutCustomerUser"
    And I should see following records in grid:
      | OrderWithChildCustomerAndWithCustomerUser |
      | OrderWithChildCustomerAndWithoutCustomerUser |

  Scenario: Don't see Orders by frontend administrator of another customer.
    Given I login as administrator
    And go to Sales/Orders
    And I should see following records in grid:
      | OrderWithoutCustomerUser |
      | OrderWithChildCustomerAndWithCustomerUser |
      | OrderWithChildCustomerAndWithoutCustomerUser |
      | OrderWithCustomerAndCustomerUser |

    When I signed in as JuanaPBrzezinski@example.net on the store frontend
    And click "Orders"
    Then I should see "No records found"

  Scenario: See Orders with Customer by creator (buyer).
    Given I login as administrator
    And go to Sales/Orders
    And click view OrderWithCustomerAndCustomerUser in grid
    Then I should see Order with:
      | Order Number | OrderWithCustomerAndCustomerUser |
      | Customer | first customer |
      | Customer User | Amanda Cole |

    When I signed in as AmandaRCole@example.org on the store frontend
    And click "Orders"
    Then I should not see "OrderWithoutCustomerUser"
    And I should see following records in grid:
      | OrderWithCustomerAndCustomerUser |
