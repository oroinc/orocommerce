@regression
@ticket-BB-14917
@fixture-OroUserBundle:manager.yml
@fixture-OroPricingBundle:Pricelists.yml

Feature: Price List ACL
  In order to have the ability to manage access to price lists
  As an Manager
  I want to manage ACL by role for entity "Price List"

  Scenario: Feature Background
    Given sessions active:
      | admin    |first_session |
      | manager  |second_session|

  Scenario: Disallow DELETE permission for PriceList
    Given I proceed as the manager
    And I login as "ethan" user
    And I go to Sales/ Price Lists
    Then I should see "first price list"
    And I should see following buttons:
      | Create Price List |
    And I should see following actions for first price list in grid:
      | Disable     |
      | View        |
      | Edit        |
      | Delete      |
    And I click view "first price list" in grid
    And I should see following buttons:
      | Add Product Price    |
      | Disable              |
      | Duplicate Price List |
      | Recalculate          |
      | Export               |
      | Import file          |
      | Edit                 |
      | Delete               |

    When I proceed as the admin
    And I login as administrator
    And I go to System/User Management/Roles
    And click edit "Sales Manager" in grid
    And select following permissions:
      | Price List | Delete:None |
    And I save and close form

    And I proceed as the manager
    And I reload the page
    Then I should not see following buttons:
      | Delete   |
    And I should see following buttons:
      | Add Product Price    |
      | Disable              |
      | Duplicate Price List |
      | Recalculate          |
      | Export               |
      | Import file          |
      | Edit                 |
    When I go to Sales/ Price Lists
    Then I should see following buttons:
      | Create Price List |
    And I should not see following actions for first price list in grid:
      | Delete   |
    And I should see following actions for first price list in grid:
      | Disable     |
      | View        |
      | Edit        |

  Scenario: Disallow EDIT permission for PriceList
    Given I proceed as the admin
    And click "Edit"
    And select following permissions:
      | Price List | Edit:None |
    And I save and close form

    And I proceed as the manager
    When I reload the page
    Then I should see following buttons:
      | Create Price List |
    And I should not see following actions for first price list in grid:
      | Disable     |
      | Edit        |
      | Delete      |
    And I should see following actions for first price list in grid:
      | View        |
    When I click view "first price list" in grid
    Then I should not see following buttons:
      | Import file       |
      | Disable           |
      | Delete            |
      | Edit              |
    And I should see following buttons:
      | Add Product Price    |
      | Duplicate Price List |
      | Recalculate          |
      | Export               |

  Scenario: Disallow CREATE permission for PriceList
    Given I proceed as the admin
    And click "Edit"
    And select following permissions:
      | Price List | Create:None |
    And I save and close form

    And I proceed as the manager
    When I reload the page
    Then I should not see following buttons:
      | Import file       |
      | Disable           |
      | Delete            |
      | Edit              |
      | Duplicate Price List |
    And I should see following buttons:
      | Add Product Price    |
      | Recalculate          |
      | Export               |
    When I go to Sales/ Price Lists
    Then I should not see following buttons:
      | Create Price List |
    And I should see following actions for first price list in grid:
      | View        |

  Scenario: Disallow RECALCULATE permission for PriceList
    Given I proceed as the admin
    And click "Edit"
    And select following permissions:
      | Price List | Recalculate:None |
    And I save and close form

    And I proceed as the manager
    And I reload the page
    When I click view "first price list" in grid
    Then I should not see following buttons:
      | Recalculate |

  Scenario: Check if Export PriceList button is visible with only View permissions
    Given I should see following buttons:
      | Export |
