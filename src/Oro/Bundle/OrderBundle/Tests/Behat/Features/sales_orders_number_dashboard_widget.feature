@random-failed
@feature-BB-21858
Feature: Sales Orders Number dashboard widget

  Scenario: Add Sales Orders Number widget
    Given I login as administrator
    When I click "Add widget"
    And I type "Sales Orders Number" in "Enter keyword"
    And I click "First Widget Add Button"
    And I click "Close" in modal window
    Then I should see "Sales Orders Number" widget on dashboard
    And I should see "Sales Orders Number" dashboard widget config data:
      | Date Range 1 | <Date:this month> | <Date:today> |
    And I should see "Sales Orders Number" dashboard widget config data:
      | Included Order Statuses | Open; Closed |
      | Include Sub-Orders      | No           |
    And I should not see "Sales Orders Number" dashboard widget config data:
      | Date Range 2 | None |
      | Date Range 3 | None |

  Scenario: Check widget configuration options
    When I click "Sales Orders Number Actions"
    And I click "Configure" in "Sales Orders Number" widget
    Then "Sales Orders Number Widget Configuration Form" must contains values:
      | Date Range 1 Type       | Month-To-Date  |
      | Date Range 2 Type       | None           |
      | Date Range 3 Type       | None           |
      | Included Order Statuses | [Open, Closed] |
      | Include Sub-Orders      | No             |
    And should see the following options for "Date Range 1 Type" select in form "Sales Orders Number Widget Configuration Form":
      | Today           |
      | Month-To-Date   |
      | Quarter-To-Date |
      | Year-To-Date    |
      | All Time        |
      | Custom          |
    And should not see the following options for "Date Range 1 Type" select in form "Sales Orders Number Widget Configuration Form":
      | None        |
      | Starting At |
    And I should not see "Date Range 2 Type Readonly" element inside "Sales Orders Number Widget Configuration Form" element
    And should see the following options for "Date Range 2 Type" select in form "Sales Orders Number Widget Configuration Form":
      | None        |
      | Starting At |
    And I should not see "Date Range 3 Type Readonly" element inside "Sales Orders Number Widget Configuration Form" element
    And should see the following options for "Date Range 3 Type" select in form "Sales Orders Number Widget Configuration Form":
      | None        |
      | Starting At |
    And should see the following options for "Include Sub-Orders" select in form "Sales Orders Number Widget Configuration Form":
      | Yes |
      | No  |
    And should see the following options for "Included Order Statuses" select in form "Sales Orders Number Widget Configuration Form":
      | Cancelled |
    And I click "Close" in modal window

  Scenario: Apply All Time date range 1 type
    When I click "Sales Orders Number Actions"
    And I click "Configure" in "Sales Orders Number" widget
    And I fill "Sales Orders Number Widget Configuration Form" with:
      | Date Range 2 Type       | Starting At   |
      | Date Range 2 Start Date | <Date: today> |
      | Date Range 3 Type       | Starting At   |
      | Date Range 3 Start Date | <Date: today> |
      | Date Range 1 Type       | All Time      |
    Then "Sales Orders Number Widget Configuration Form" must contains values:
      | Date Range 1 Type | All Time |
      | Date Range 2 Type | None     |
      | Date Range 3 Type | None     |
    And I should see "Date Range 2 Type Readonly" element inside "Sales Orders Number Widget Configuration Form" element
    And I should see "Date Range 3 Type Readonly" element inside "Sales Orders Number Widget Configuration Form" element
    When I click "Widget Save Button"
    Then I should see "Widget has been successfully configured" flash message
    And I should see "Sales Orders Number" dashboard widget config data:
      | Date Range 1 | <Date:1900-01-01> | <Date:today> |
    And I should not see "Date Range 2"
    And I should not see "Date Range 3"

  Scenario Outline: Apply non custom date range type
    When I click "Sales Orders Number Actions"
    And I click "Configure" in "Sales Orders Number" widget
    And I fill "Sales Orders Number Widget Configuration Form" with:
      | Date Range 1 Type       | <Date Range 1 Type>       |
      | Date Range 2 Type       | Starting At               |
      | Date Range 2 Start Date | <Date Range 2 Start Date> |
      | Date Range 3 Type       | Starting At               |
      | Date Range 3 Start Date | <Date Range 3 Start Date> |
      | Included Order Statuses | [Cancelled]               |
      | Include Sub-Orders      | Yes                       |
    And I click "Widget Save Button"
    Then I should see "Widget has been successfully configured" flash message
    And I should see "Sales Orders Number" dashboard widget config data:
      | Date Range 1 | <Date Range 1 Expected Start Date> | <Date Range 1 Expected End Date> |
      | Date Range 2 | <Date Range 2 Expected Start Date> | <Date Range 2 Expected End Date> |
      | Date Range 3 | <Date Range 3 Expected Start Date> | <Date Range 3 Expected End Date> |
    And I should see "Sales Orders Number" dashboard widget config data:
      | Included Order Statuses | Cancelled |
      | Include Sub-Orders      | Yes       |

    Examples:
      | Date Range 1 Type | Date Range 1 Expected Start Date | Date Range 1 Expected End Date | Date Range 2 Start Date | Date Range 2 Expected Start Date | Date Range 2 Expected End Date |  Date Range 3 Start Date | Date Range 3 Expected Start Date | Date Range 3 Expected End Date |
      | Today             | <Date:today>                     | <Date:today>                   | <Date:today>            | <Date:today>                     | <Date:today>                   | <Date:today>             | <Date:today>                     | <Date:today>                   |
      | Month-To-Date     | <Date:this month>                | <Date:today>                   | <Date:this month>       | <Date:this month>                | <Date:today>                   | <Date:this month>        | <Date:this month>                | <Date:today>                   |
      | Quarter-To-Date   | <Date:this quarter>              | <Date:today>                   | <Date:this quarter>     | <Date:this quarter>              | <Date:today>                   | <Date:this quarter>      | <Date:this quarter>              | <Date:today>                   |
      | Year-To-Date      | <Date:this year>                 | <Date:today>                   | <Date:this year>        | <Date:this year>                 | <Date:today>                   | <Date:this year>         | <Date:this year>                 | <Date:today>                   |

  Scenario Outline: Apply custom date range type
    When I click "Sales Orders Number Actions"
    And I click "Configure" in "Sales Orders Number" widget
    And I fill "Sales Orders Number Widget Configuration Form" with:
      | Date Range 1 Type       | Custom                     |
      | Date Range 1 Start Date | <Date Range 1 Start Date>  |
      | Date Range 1 End Date   | <Date Range 1 End Date>    |
      | Date Range 2 Type       | Starting At                |
      | Date Range 2 Start Date | <Date Range 2 Start Date>  |
      | Date Range 3 Type       | Starting At                |
      | Date Range 3 Start Date | <Date Range 3 Start Date>  |
    And I click "Widget Save Button"
    Then I should see "Widget has been successfully configured" flash message
    And I should see "Sales Orders Number" dashboard widget config data:
      | Date Range 1 | <Date Range 1 Expected Start Date> | <Date Range 1 Expected End Date> |
      | Date Range 2 | <Date Range 2 Expected Start Date> | <Date Range 2 Expected End Date> |
      | Date Range 3 | <Date Range 3 Expected Start Date> | <Date Range 3 Expected End Date> |

    Examples:
      | Date Range 1 Start Date | Date Range 1 End Date | Date Range 1 Expected Start Date | Date Range 1 Expected End Date | Date Range 2 Start Date | Date Range 2 Expected Start Date | Date Range 2 Expected End Date |  Date Range 3 Start Date | Date Range 3 Expected Start Date | Date Range 3 Expected End Date |
      | <Date:2023-01-04>       | <Date:2023-01-05>     | <Date:2023-01-04>                | <Date:2023-01-05>              | <Date:2023-01-01>       | <Date:2023-01-01>                | <Date:2023-01-02>              | <Date:today>             | <Date:today>                     | <Date:today + 1 day>           |
      | <Date:2023-01-05>       | <Date:2023-01-04>     | <Date:2023-01-04>                | <Date:2023-01-05>              | <Date:2023-01-01>       | <Date:2023-01-01>                | <Date:2023-01-02>              | <Date:2023-01-02>        | <Date:2023-01-02>                | <Date:2023-01-03>              |
      |                         |                       | <Date:1900-01-01>                | <Date:today>                   | <Date:1900-01-01>       | <Date:1900-01-01>                | <Date:today>                   | <Date:1900-01-01>        | <Date:1900-01-01>                | <Date:today>                   |
      |                         | <Date:today>          | <Date:1900-01-01>                | <Date:today>                   | <Date:1900-01-01>       | <Date:1900-01-01>                | <Date:today>                   | <Date:1900-01-01>        | <Date:1900-01-01>                | <Date:today>                   |
      | <Date:today>            |                       | <Date:today>                     | <Date:today>                   | <Date:2023-01-01>       | <Date:2023-01-01>                | <Date:2023-01-01>              | <Date:2023-01-02>        | <Date:2023-01-02>                | <Date:2023-01-02>              |
      | <Date:2023-01-04>       | <Date:2023-01-05>     | <Date:2023-01-04>                | <Date:2023-01-05>              |                         | <Date:1900-01-01>                | <Date:1900-01-02>              |                          | <Date:1900-01-01>                | <Date:1900-01-02>              |

  Scenario: Delete "Sales Orders Number" dashboard widget
    When I click "Sales Orders Number Actions"
    And I click "Delete" in "Sales Orders Number" widget
    And I confirm deletion
    Then I should not see "Sales Orders Number" widget on dashboard
