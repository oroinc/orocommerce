@regression
@ticket-BB-21561
@fixture-OroOrderBundle:order.yml

Feature: Check available action panel
  In order for the datagrids check available action panel with actions
  As an Frontend Theme Developer
  I want to be able to interact with action of datagrid in my theme

  Scenario: Feature Background
    Given I set configuration property "oro_frontend.frontend_theme" to "custom"
    And sessions active:
      | Buyer | second_session |

  Scenario: Check available datagrid actions
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And follow "Account"
    When I click "Order History"
    Then I should see an "PastOrdersGridToolbarActions" element
