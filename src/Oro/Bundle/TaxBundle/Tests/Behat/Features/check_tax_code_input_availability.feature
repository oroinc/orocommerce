@ticket-BB-20488
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroTaxBundle:ProductAndTaxes.yml

Feature: Check Tax code input availability
  In order to manage Tax code
  As an administrator
  I want to be able to change, set or unset Customer's Tax code value

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session |
    And I proceed as the Admin
    And login as administrator

  Scenario Outline: Check Tax code input at edit page
    When I go to <Entity Grid Path>
    And I click "Edit" on first row in grid
    And Tax Code field should has <Tax Code Value> value
    Examples:
      | Entity Grid Path     | Tax Code Value    |
      | Products/ Products   | Product tax code  |
      | Customers/ Customers | Customer tax code |

  Scenario: Check Tax code input at edit page, for Customer Groups entity
    When I go to Customers/ Customer Groups
    And I click "Edit" on first row in grid
    And Tax Code field is empty

  Scenario Outline: Enable displaying Tax code input
    When I go to System/ Entities/ Entity Management
    And I filter Name as is equal to "<Entity Name>"
    And I click view <Entity Name> in grid
    And I click edit taxCode in grid
    And I fill "Tax Code Form" with:
      | Other, Show On Form | Yes |
    And I save and close form
    Then I should see "Field saved" flash message
    Examples:
      | Entity Name   |
      | Product       |
      | Customer      |
      | CustomerGroup |

  Scenario Outline: Recheck Tax code input at edit page, after change "Show On Form" option
    When I go to <Entity Grid Path>
    And I click "Edit" on first row in grid
    And Tax Code field should has <Tax Code Value> value
    Examples:
      | Entity Grid Path     | Tax Code Value    |
      | Products/ Products   | Product tax code  |
      | Customers/ Customers | Customer tax code |

  Scenario: Recheck Tax code input at edit page, after change "Show On Form" option for Customer Groups entity
    When I go to Customers/ Customer Groups
    And I click "Edit" on first row in grid
    And Tax Code field is empty
