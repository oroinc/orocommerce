@regression
@feature-BB-23530
@fixture-OroOrderBundle:order.yml
@fixture-OroOrderBundle:ordersCreatedBy.yml

Feature: Created by is saved in order on checkout in impersonation mode

  Scenario: Feature Background
    Given I login as administrator

  Scenario: Check Orders grid
    When I go to Sales/Orders
    Then I should see following grid:
      | Order Number    | Owner    |
      | Order2CreatedBy | John Doe |
      | Order1CreatedBy | John Doe |
      | SecondOrder     | John Doe |
      | SimpleOrder     | John Doe |

  Scenario: Check "Created By" column on the Orders main grid
    When I show column Created By in grid
    Then I should see following grid:
      | Order Number    | Owner    | Created By   |
      | Order2CreatedBy | John Doe | Phil Collins |
      | Order1CreatedBy | John Doe | John Doe     |
      | SecondOrder     | John Doe |              |
      | SimpleOrder     | John Doe |              |

  Scenario: Sort by Created By
    When I sort grid by "Created By"
    Then I should see following grid:
      | Order Number    | Owner    | Created By   |
      | Order1CreatedBy | John Doe | John Doe     |
      | Order2CreatedBy | John Doe | Phil Collins |
      | SimpleOrder     | John Doe |              |
      | SecondOrder     | John Doe |              |

    When I sort grid by "Created By" again
    Then I should see following grid:
      | Order Number    | Owner    | Created By   |
      | SecondOrder     | John Doe |              |
      | SimpleOrder     | John Doe |              |
      | Order2CreatedBy | John Doe | Phil Collins |
      | Order1CreatedBy | John Doe | John Doe     |

  Scenario: Filter by Created By
    When I show filter "Created By" in grid
    And filter Created By as is equal to "Phil Collins"
    Then I should see following grid:
      | Order Number    | Owner    | Created By   |
      | Order2CreatedBy | John Doe | Phil Collins |
    And there is one record in grid

    When filter Created By as contains "Doe"
    Then I should see following grid:
      | Order Number    | Owner    | Created By |
      | Order1CreatedBy | John Doe | John Doe   |
    And there is one record in grid

    When I filter Created By as Does Not Contain "Doe"
    Then I should see following grid:
      | Order Number    | Owner    | Created By   |
      | Order2CreatedBy | John Doe | Phil Collins |
    And there is one record in grid

    When I filter Created By as is empty
    Then I should see following grid:
      | Order Number | Owner    | Created By |
      | SecondOrder  | John Doe |            |
      | SimpleOrder  | John Doe |            |
    And there are 2 records in grid

    When I filter Created By as is not empty
    Then I should see following grid:
      | Order Number    | Owner    | Created By   |
      | Order2CreatedBy | John Doe | Phil Collins |
      | Order1CreatedBy | John Doe | John Doe     |
    And there are 2 records in grid

  Scenario: Created By is displayed in Orders grid when user doesn't have User View permissions
    Given administrator permissions on View User is set to None
    When I reload the page
    Then I should see following grid:
      | Order Number    | Owner    | Created By   |
      | Order2CreatedBy | John Doe | Phil Collins |
      | Order1CreatedBy | John Doe | John Doe     |
