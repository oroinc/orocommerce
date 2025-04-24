@feature-BB-25440
@regression

Feature: Scorecard Content Widget CRUD

  Scenario: Create scorecard content widget
    Given I login as administrator
    And I go to Marketing/Content Widgets
    When I click "Create Content Widget"
    And I fill "Content Widget Form" with:
      | Type          | Scorecard                                                      |
      | Name          | my-users                                                       |
      | Description   | Scorecard Description                                          |
      | Scorecard     | Users                                                          |
      | Default Label | My Users                                                       |
      | Link          | Oro Shopping List Frontend Index (Shopping Lists - My Account) |
    And I save and close form
    Then I should see "Content widget has been saved" flash message
    And I should see "Type: Scorecard"
    And I should see Content Widget with:
      | Name           | my-users                   |
      | Description    | Scorecard Description      |
      | Scorecard Name | users                      |
      | Label          | My Users                   |
      | Link           | /customer/shoppinglist/all |

  Scenario: Update scorecard content widget
    When I click "Edit"
    And I fill "Content Widget Form" with:
      | Description   | Scorecard Description2                                           |
      | Scorecard     | Open RFQs                                                        |
      | Default Label | Open RFQs                                                        |
      | Link          | Oro Rfp Frontend Request Index (Requests For Quote - My Account) |
    And I save and close form
    Then I should see "Type: Scorecard"
    And I should see Content Widget with:
      | Name           | my-users               |
      | Description    | Scorecard Description2 |
      | Scorecard Name | open_rfqs              |
      | Label          | Open RFQs              |
      | Link           | /customer/rfp/         |

  Scenario: Check scorecard content widgets datagrid
    When I go to Marketing/Content Widgets
    Then there is 13 records in grid
    And I should see following grid:
      | Name      | Description            | Type      | Layout |
      | my-users  | Scorecard Description2 | Scorecard |        |

  Scenario: Delete scorecard content widgets
    When I click Delete my-users in grid
    And I confirm deletion
    Then I should see "Content Widget deleted" flash message
