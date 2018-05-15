@fixture-OroOrderBundle:OrderBackofficeDefaultFixture.yml
Feature: Order Backoffice Default
  ToDo: BAP-16103 Add missing descriptions to the Behat features

  Scenario: See Order without Customer User by frontend administrator.
    Given I login as NancyJSallee@example.org the "Buyer" at "first_session" session
    And I login as administrator and use in "second_session" as "Admin"
    And go to Sales/Orders
    And click view OrderWithoutCustomerUser in grid
    Then I should see Order with:
      | Order Number | OrderWithoutCustomerUser |
      | Customer | first customer |
      | Customer User | N/A |

    Then I operate as the Buyer
    And click "Orders"
    Then I should see following records in grid:
      | OrderWithoutCustomerUser |
    When I click view OrderWithoutCustomerUser in grid
    Then I should see "Order #OrderWithoutCustomerUser"

  Scenario: See Order with child Customer by frontend administrator.
    Given I operate as the Admin
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

    Then I operate as the Buyer
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
    Given I operate as the Admin
    And go to Sales/Orders
    And click view OrderWithoutCustomerUser in grid
    Then I should see Order with:
      | Order Number | OrderWithoutCustomerUser |
      | Customer | first customer |
      | Customer User | N/A |

    Then I operate as the Buyer
    And I signed in as RuthWMaxwell@example.org on the store frontend
    And click "Orders"
    Then I should not see "OrderWithoutCustomerUser"
    And I should see following records in grid:
      | OrderWithChildCustomerAndWithCustomerUser |
      | OrderWithChildCustomerAndWithoutCustomerUser |

  Scenario: Don't see Orders by frontend administrator of another customer.
    Given I operate as the Admin
    And go to Sales/Orders
    And I should see following records in grid:
      | OrderWithoutCustomerUser |
      | OrderWithChildCustomerAndWithCustomerUser |
      | OrderWithChildCustomerAndWithoutCustomerUser |
      | OrderWithCustomerAndCustomerUser |

    Then I operate as the Buyer
    And I signed in as JuanaPBrzezinski@example.net on the store frontend
    And click "Orders"
    Then I should see "No records found"

  Scenario: See Orders with Customer by creator (buyer).
    Given I operate as the Admin
    And go to Sales/Orders
    And click view OrderWithCustomerAndCustomerUser in grid
    Then I should see Order with:
      | Order Number | OrderWithCustomerAndCustomerUser |
      | Customer | first customer |
      | Customer User | Amanda Cole |

    Then I operate as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And click "Orders"
    Then I should not see "OrderWithoutCustomerUser"
    And I should see following records in grid:
      | OrderWithCustomerAndCustomerUser |
