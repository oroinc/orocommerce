@ticket-BB-12309
@ticket-BB-11878
@waf-skip
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPaymentBundle:PaymentMethodsConfigsRule_BB12309.yml

Feature: Manage Payment Rules
  In order to have ability to manage payment rules
  As a Administrator
  I should have an ability to manage payment rules

  Scenario: Verify payment rules grid
    Given I login as administrator
    When I go to System/Payment Rules
    Then I should see following grid:
      | ID | Name                      | Enabled | Sort Order | Currency | Expression | Payment Methods Configs | Destinations                                     |
      | 1  | <script>alert(1)</script> | Yes     | 5          | USD      | expression | Payment Term            | Florida, United States <script>alert(2)</script> |

  Scenario: Verify payment rule view page
    When I click view "<script>alert(1)</script>" in grid
    Then should see Payment Rule with:
      | Name                    | <script>alert(1)</script> |
      | Enabled                 | Yes                       |
      | Sort Order              | 5                         |
      | Currency                | USD                       |
      | Expression              | expression                |
      | Payment Methods Configs | Payment Term              |
    And I should see "Florida, United States <script>alert(2)</script>"

  Scenario: Verify payment rule edit form
    When I click "Edit"
    Then "Payment Rule Form" must contains values:
      | Name                    | <script>alert(1)</script> |
      | Sort Order              | 5                         |
      | Expression              | expression                |
      | Destination1PostalCodes | <script>alert(2)</script> |

  Scenario: Verify validation rule on Sort Order form field
    When I fill "Payment Rule Form" with:
      | Sort Order | 21474836478 |
    Then I should see validation errors:
      | Sort Order | This value should be between -2,147,483,648 and 2,147,483,647. |
    When I fill "Payment Rule Form" with:
      | Sort Order | 5 |
    Then I should not see validation errors:
      | Sort Order | This value should be between -2,147,483,648 and 2,147,483,647. |

  Scenario: Verify payment rule edit form after save
    When I fill "Payment Rule Form" with:
      | State | Florida |
    And I save form
    Then "Payment Rule Form" must contains values:
      | Name                    | <script>alert(1)</script> |
      | Sort Order              | 5                         |
      | Expression              | expression                |
      | Destination1PostalCodes | alert(2)                  |

  Scenario: Verify payment rule view page after save
    When I save and close form
    Then should see Payment Rule with:
      | Name                    | <script>alert(1)</script> |
      | Enabled                 | Yes                       |
      | Sort Order              | 5                         |
      | Currency                | USD                       |
      | Expression              | expression                |
      | Payment Methods Configs | Payment Term              |
    And I should not see "Florida, United States <script>alert(2)</script>"
    And I should see "Florida, United States alert(2)"

  Scenario: Verify payment rules grid after update
    When I go to System/Payment Rules
    Then I should see following grid:
      | ID | Name                      | Enabled | Sort Order | Currency | Expression | Payment Methods Configs | Destinations                    |
      | 1  | <script>alert(1)</script> | Yes     | 5          | USD      | expression | Payment Term            | Florida, United States alert(2) |
