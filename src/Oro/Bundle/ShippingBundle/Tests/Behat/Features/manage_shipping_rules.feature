@ticket-BB-12309
@waf-skip
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroShippingBundle:ShippingMethodsConfigsRule_BB12309.yml

Feature: Manage Shipping Rules
  In order to have ability to manage Shipping rules
  As a Administrator
  I should have an ability to manage Shipping rules

  Scenario: Verify Shipping rules grid
    Given I login as administrator
    When I go to System/Shipping Rules
    Then I should see following grid:
      | ID | Name                      | Enabled | Sort Order | Currency | Expression | CONFIGURATIONS                                | Destinations                                     |
      | 1  | <script>alert(1)</script> | Yes     | 5          | USD      | expression | Flat Rate (Price: $1.50, Handling Fee: $1.50) | Florida, United States <script>alert(2)</script> |

  Scenario: Verify Shipping rule view page
    When I click view "<script>alert(1)</script>" in grid
    Then should see Shipping Rule with:
      | Name           | <script>alert(1)</script>                     |
      | Enabled        | Yes                                           |
      | Sort Order     | 5                                             |
      | Currency       | USD                                           |
      | Expression     | expression                                    |
      | Configurations | Flat Rate (Price: $1.50, Handling Fee: $1.50) |
    And I should see "Florida, United States <script>alert(2)</script>"

  Scenario: Verify Shipping rule edit form
    When I click "Edit"
    Then "Shipping Rule" must contains values:
      | Name       | <script>alert(1)</script> |
      | Sort Order | 5                         |
      | Expression | expression                |
      | ZIP        | <script>alert(2)</script> |

  Scenario: Verify Shipping rule edit form after save
    When I fill "Shipping Rule" with:
      | State | Florida |
    And I save form
    Then "Shipping Rule" must contains values:
      | Name       | <script>alert(1)</script> |
      | Sort Order | 5                         |
      | Expression | expression                |
      | ZIP        | alert(2)                  |

  Scenario: Verify Shipping rule view page after save
    When I save and close form
    Then should see Shipping Rule with:
      | Name           | <script>alert(1)</script>                     |
      | Enabled        | Yes                                           |
      | Sort Order     | 5                                             |
      | Currency       | USD                                           |
      | Expression     | expression                                    |
      | Configurations | Flat Rate (Price: $1.50, Handling Fee: $1.50) |
    And I should not see "Florida, United States <script>alert(2)</script>"
    And I should see "Florida, United States alert(2)"

  Scenario: Verify Shipping rules grid after update
    When I go to System/Shipping Rules
    Then I should see following grid:
      | ID | Name                      | Enabled | Sort Order | Currency | Expression | CONFIGURATIONS                                | Destinations                    |
      | 1  | <script>alert(1)</script> | Yes     | 5          | USD      | expression | Flat Rate (Price: $1.50, Handling Fee: $1.50) | Florida, United States alert(2) |
