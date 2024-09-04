@regression
@feature-BB-23530
@fixture-OroShoppingListBundle:ShoppingListFixture.yml
@fixture-OroOrderBundle:SalesOrdersShoppingListsFixture.yml
@fixture-OroOrderBundle:ordersCreatedBy.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroOrderBundle:PaymentTransactionFixture.yml

Feature: Sales Orders on Customer view page

  Scenario: Check Customer view page
    Given I login as administrator
    When I go to Customer / Customers
    And I click view "first customer" in grid
    And I sort "Customer Sales Orders Grid" by "Order Number"
    Then I should see following "Customer Sales Orders Grid" grid:
      | Order Number    | Payment Method |
      | FirstOrder      | Payment Term   |
      | Order1CreatedBy |                |
      | Order2CreatedBy |                |
      | SecondOrder     |                |

  Scenario: Check "Created By" column
    When I show column Created By in "Customer Sales Orders Grid"
    Then I should see following "Customer Sales Orders Grid" grid:
      | Order Number    | Created By   |
      | FirstOrder      |              |
      | Order1CreatedBy | John Doe     |
      | Order2CreatedBy | Phil Collins |
      | SecondOrder     |              |

  Scenario: Sort by Created By
    When I sort grid by "Created By"
    Then I should see following "Customer Sales Orders Grid" grid:
      | Order Number    | Created By   |
      | Order1CreatedBy | John Doe     |
      | Order2CreatedBy | Phil Collins |
      | FirstOrder      |              |
      | SecondOrder     |              |

    When I sort grid by "Created By" again
    Then I should see following "Customer Sales Orders Grid" grid:
      | Order Number    | Created By   |
      | SecondOrder     |              |
      | FirstOrder      |              |
      | Order2CreatedBy | Phil Collins |
      | Order1CreatedBy | John Doe     |

  Scenario: Filter by Created By
    When I show filter "Created By" in "Customer Sales Orders Grid" grid
    And filter Created By as is equal to "Phil Collins" in "Customer Sales Orders Grid" grid
    Then I should see following "Customer Sales Orders Grid" grid:
      | Order Number    | Created By   |
      | Order2CreatedBy | Phil Collins |
    And number of records in "Customer Sales Orders Grid" grid should be one

    When filter Created By as contains "Doe" in "Customer Sales Orders Grid" grid
    Then I should see following "Customer Sales Orders Grid" grid:
      | Order Number    | Created By |
      | Order1CreatedBy | John Doe   |
    And number of records in "Customer Sales Orders Grid" grid should be one

    When I filter Created By as Does Not Contain "Doe" in "Customer Sales Orders Grid" grid
    Then I should see following "Customer Sales Orders Grid" grid:
      | Order Number    | Created By   |
      | Order2CreatedBy | Phil Collins |
    And number of records in "Customer Sales Orders Grid" grid should be one

    When I filter Created By as is empty in "Customer Sales Orders Grid" grid
    Then I should see following "Customer Sales Orders Grid" grid:
      | Order Number | Created By |
      | SecondOrder  |            |
      | FirstOrder   |            |
    And number of records in "Customer Sales Orders Grid" grid should be two

    When I filter Created By as is not empty in "Customer Sales Orders Grid" grid
    Then I should see following "Customer Sales Orders Grid" grid:
      | Order Number    | Created By   |
      | Order2CreatedBy | Phil Collins |
      | Order1CreatedBy | John Doe     |
    And number of records in "Customer Sales Orders Grid" grid should be two
