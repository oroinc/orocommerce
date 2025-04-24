@feature-BB-24920
@regression

Feature: Customer Dashboard Datagrid Content Widget CRUD

  Scenario: Create customer dashboard datagrid content widget
    Given I login as administrator
    And I go to Marketing/Content Widgets
    When I click "Create Content Widget"
    And I fill "Content Widget Form" with:
      | Type                        | Customer Dashboard DataGrid                           |
      | Name                        | my-orders                                             |
      | Description                 | Datagrid Description                                  |
      | Customer Dashboard DataGrid | My Latest Orders                                      |
      | Default Label               | My Orders                                             |
      | View All                    | Oro Order Frontend Index (Order History - My Account) |
    And I save and close form
    Then I should see "Content widget has been saved" flash message
    And I should see "Type: Customer Dashboard DataGrid"
    And I should see Content Widget with:
      | Name          | my-orders                                         |
      | Description   | Datagrid Description                              |
      | DataGrid Name | frontend-customer-dashboard-my-latest-orders-grid |
      | Label         | My Orders                                         |
      | View All      | /customer/order/                                  |

  Scenario: Update customer dashboard datagrid content widget
    When I click "Edit"
    And I fill "Content Widget Form" with:
      | Description                 | Datagrid Description2                               |
      | Customer Dashboard DataGrid | Open Quotes                                         |
      | Default Label               | Open Quotes                                         |
      | View All                    | Oro Sale Quote Frontend Index (Quotes - My Account) |
    And I save and close form
    Then I should see "Type: Customer Dashboard DataGrid"
    And I should see Content Widget with:
      | Name          | my-orders                                    |
      | Description   | Datagrid Description2                        |
      | DataGrid Name | frontend-customer-dashboard-open-quotes-grid |
      | Label         | Open Quotes                                  |
      | View All      | /customer/quote/                             |

  Scenario: Check customer dashboard content widgets datagrid
    When I go to Marketing/Content Widgets
    Then there is 13 records in grid
    And I should see following grid:
      | Name      | Description           | Type                        | Layout |
      | my-orders | Datagrid Description2 | Customer Dashboard DataGrid |        |

  Scenario: Delete customer dashboard content widget
    When I click Delete my-orders in grid
    And I confirm deletion
    Then I should see "Content Widget deleted" flash message
