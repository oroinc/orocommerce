@ticket-BB-9594
@fixture-OroOrderBundle:order.yml
Feature: Order Internal Statuses
  In order to change order statuses
  As an Administrator
  I want to have actions at view order page to change internal statuses

  Scenario: Verify internal statuses flow Open => Closed
    Given I login as administrator
    And I go to Sales/Orders
    When I click view "SimpleOrder" in grid
    Then I should see that order internal status is "Open"
    And I should see following buttons:
      | Cancel               |
      | Close                |
      | Add Special Discount |

    When I click "More actions"
    And I click "Close"
    And I click "Yes" in confirmation dialogue
    Then I should see "Order #SimpleOrder has been closed." flash message
    And I should see that order internal status is "Closed"
    And I should not see following buttons:
      | Cancel               |
      | Close                |
      | Add Special Discount |

  Scenario: Verify internal statuses at BackOffice Order grid
    Given I go to Sales/Orders
    And there is one record in grid
    And I should see following grid:
      | Order Number | Internal Status |
      | SecondOrder  | Open            |
    When click grid view list
    And I click "All Orders"
    Then number of records should be 2
    When I sort grid by "Internal Status"
    Then I should see following grid:
      | Order Number | Internal Status |
      | SimpleOrder  | Closed          |
      | SecondOrder  | Open            |
    When I sort grid by "Internal Status" again
    Then I should see following grid:
      | Order Number | Internal Status |
      | SecondOrder  | Open            |
      | SimpleOrder  | Closed          |

  Scenario: Verify internal statuses flow Open => Cancelled => Closed
    Given I go to Sales/Orders
    When I click view "SecondOrder" in grid
    Then I should see that order internal status is "Open"
    And I should see following buttons:
      | Cancel               |
      | Close                |
      | Add Special Discount |

    When I click "More actions"
    And I click "Cancel"
    And I click "Yes" in confirmation dialogue
    Then I should see "Order #SecondOrder has been cancelled." flash message
    And I should see that order internal status is "Cancelled"
    And I should see following buttons:
      | Close |
    And I should not see following buttons:
      | Cancel               |
      | Add Special Discount |

    When I click "More actions"
    And I click "Close"
    And I click "Yes" in confirmation dialogue
    Then I should see "Order #SecondOrder has been closed." flash message
    And I should see that order internal status is "Closed"
    And I should not see following buttons:
      | Cancel               |
      | Close                |
      | Add Special Discount |
