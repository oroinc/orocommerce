@regression
@ticket-BB-9130
@ticket-BB-11080
@ticket-BB-12075
@automatically-ticket-tagged
@waf-skip
@fixture-OroSaleBundle:QuoteBackofficeApprovalsFixture.yml
Feature: Backoffice Quote Flow with Approvals
  In order to edit quote internal statuses and aprove quotes after price changes
  As an Administrator
  I want to have ability to change Quote internal status and approve quotes by Workflow transitions

  Scenario: Check workflow variable
    Given sessions active:
      | Admin   | first_session  |
      | Manager | second_session |
    And I proceed as the Admin
    And I login as administrator
    When I go to System/Workflows
    And I click Configuration Backoffice Quote Flow with Approvals in grid
    Then the "Price override requires approval" checkbox should be checked

  Scenario: Check workflow permissions for user
    Given I go to System/ User Management/ Roles
    When I filter Label as is equal to "Sales Rep"
    And I click view Sales Rep in grid
    Then the role has following active workflow permissions:
      | Backoffice Quote Flow with Approvals | View Workflow:Global | Perform transitions:Global |

  Scenario: Draft -> Edit: Quote prices not changed
    Given I proceed as the Manager
    And I login as "john" user
    When I go to Sales/Quotes
    And I filter PO Number as is equal to "PO1"
    And I click View PO1 in grid
    And I should see Quote with:
      | Quote #         | 1     |
      | PO Number       | PO1   |
      | Internal Status | Draft |
      | Customer Status | N/A   |
    And I click "Edit"
    And I fill "Quote Form" with:
      | PO Number | PO1_edit |
    And I click "Submit"
    Then I should see "Quote #1 successfully updated" flash message
    And should see Quote with:
      | Quote #         | 1        |
      | PO Number       | PO1_edit |
      | Internal Status | Draft    |
      | Customer Status | N/A      |
    And I should see "Send to Customer"
    And I should not see "Submit for Review"

  Scenario: Draft -> Sent to Customer: Quote prices not changed
    Given I click "Send to Customer"
    When I click "Send"
    Then I should see "Quote #1 successfully sent to customer" flash message
    And I should see Quote with:
      | PO Number       | PO1_edit         |
      | Internal Status | Sent to Customer |

  Scenario: Sales Rep role granting "Override quote prices"
    Given I proceed as the Admin
    When I go to System/User Management/Roles
    And I filter Label as is equal to "Sales Rep"
    And I click Edit Sales Rep in grid
    And I check "Override Quote Prices" entity permission
    And I save and close form
    Then I should see "Role saved" flash message
    And following capability permissions should be checked:
      | Override quote prices |

  Scenario: Draft -> Edit: Quotes prices changed
    Given I proceed as the Manager
    When I go to Sales/Quotes
    And I filter PO Number as is equal to "PO2"
    And I click View PO2 in grid
    And I click "Edit"
    And I fill "Quote Form" with:
      | LineItemPrice | 1 |
    And I wait 2 seconds until submit button becomes available
    And I click "Submit"
    And I click "Save" in modal window
    Then I should see "Quote #2 successfully updated" flash message
    And I should see "Send to Customer"
    And "Send to Customer" button is disabled
    And I should see "Submit for Review"

  Scenario: Draft -> Submitted for Review: Quote prices changed
    Given I should see Quote with:
      | PO Number       | PO2   |
      | Internal Status | Draft |
    When I click "Submit for Review"
    And I fill form with:
      | Comment | Test comment for submitting <script>alert(1)</script> |
    And I click "Submit"
    Then I should see "Quote #2 successfully submitted for review" flash message
    And I should see Quote with:
      | PO Number       | PO2                  |
      | Internal Status | Submitted for Review |
    And I collapse "Test comment for submitting alert(1)" in activity list

  Scenario Outline: Quotes change and Submit for Review
    Given I go to Sales/Quotes
    When I filter PO Number as is equal to "<PONumber>"
    And I click View <PONumber> in grid
    And I click "Edit"
    And I fill "Quote Form" with:
      | LineItemPrice | 1 |
    And I wait 2 seconds until submit button becomes available
    And I click "Submit"
    And I click "Save" in modal window
    And I click "Submit for Review"
    And I fill form with:
      | Comment | Test comment for submitting |
    And I click "Submit"
    Then I should see Quote with:
      | PO Number       | <PONumber>           |
      | Internal Status | Submitted for Review |
    Examples:
      | PONumber |
      | PO3      |
      | PO4      |
      | PO5      |

  Scenario: Sales Rep role granting "Review and approve quotes"
    Given I proceed as the Admin
    When I go to System/User Management/Roles
    And I filter Label as is equal to "Sales Rep"
    And I click Edit Sales Rep in grid
    And I check "Review and approve quotes" entity permission
    And I save and close form
    Then I should see "Role saved" flash message
    And following capability permissions should be checked:
      | Review and approve quotes |

  Scenario: Submitted for Review -> Under Review: Quote prices changed
    Given I proceed as the Manager
    When I go to Sales/Quotes
    And I filter PO Number as is equal to "PO2"
    And I click View PO2 in grid
    Then I should see Quote with:
      | PO Number       | PO2                  |
      | Internal Status | Submitted for Review |
    When I click "Review"
    Then I should see "Quote #2 on review" flash message
    And I should see Quote with:
      | PO Number       | PO2          |
      | Internal Status | Under Review |

  Scenario Outline: Quotes review
    Given I go to Sales/Quotes
    When I filter PO Number as is equal to "<PONumber>"
    And I click View <PONumber> in grid
    And I click "Review"
    Then I should see Quote with:
      | PO Number       | <PONumber>   |
      | Internal Status | Under Review |
    Examples:
      | PONumber |
      | PO3      |
      | PO4      |
      | PO5      |

  Scenario: Under Review -> Return: Quote prices changed
    Given I go to Sales/Quotes
    When I filter PO Number as is equal to "PO2"
    And click View PO2 in grid
    And I click "Return"
    And I fill form with:
      | Comment | Return reason note text <script>alert(1)</script> |
    And I click "Submit"
    Then I should see "Quote #2 returned" flash message
    And I should see Quote with:
      | PO Number       | PO2   |
      | Internal Status | Draft |
    And I collapse "Return reason note text alert(1)" in activity list

  Scenario: Under Review -> Approve and Send to Customer: Quote prices changed
    Given I go to Sales/Quotes
    When I filter PO Number as is equal to "PO3"
    And click View PO3 in grid
    And I click "Approve and Send to Customer"
    And click "Send"
    Then I should see "Quote #3 successfully sent to customer" flash message
    And I should see Quote with:
      | PO Number       | PO3              |
      | Internal Status | Sent To Customer |

  Scenario: Sent to Customer > Cancel: Quote prices changed
    Given I should see Quote with:
      | Quote #         | 3                |
      | PO Number       | PO3              |
      | Internal Status | Sent to customer |
      | Customer Status | N/A              |
    When I click "Cancel"
    Then I should see "Quote #3 successfully cancelled" flash message
    And should see Quote with:
      | Quote #         | 3         |
      | PO Number       | PO3       |
      | Internal Status | Cancelled |
      | Customer Status | N/A       |

  Scenario: Cancelled > Reopen: Redirect to new Quote, Internal status: Draft, customer status: N/A
    Given I go to Sales/Quotes
    When I filter PO Number as is equal to "PO3"
    And I click view PO3 in grid
    And I should see Quote with:
      | Quote #         | 3         |
      | PO Number       | PO3       |
      | Internal Status | Cancelled |
      | Customer Status | N/A       |
    And I click "Reopen"
    Then I should see "Quote #31 successfully created" flash message
    And I should see Quote with:
      | Quote #         | 31    |
      | PO Number       | PO3   |
      | Internal Status | Draft |
      | Customer Status | N/A   |
    And I click "Edit"
    And fill form with:
      | PO Number | PO31 |
    And click "Submit"

  Scenario: Under Review -> Approve: Quote prices changed
    Given I go to Sales/Quotes
    When I filter PO Number as is equal to "PO4"
    And click View PO4 in grid
    When I click "Approve"
    And I fill form with:
      | Comment | Approve reason note text <script>alert(1)</script> |
    And I click "Submit"
    Then I should see "Quote #4 approved" flash message
    And I should see Quote with:
      | PO Number       | PO4      |
      | Internal Status | Reviewed |
    And I collapse "Approve reason note text alert(1)" in activity list
    And I should see "Send to Customer"

  Scenario: Approved -> Sent to Customer: Quote prices changed
    Given I click "Send to Customer"
    When click "Send"
    Then I should see "Quote #4 successfully sent to customer" flash message
    And I should see Quote with:
      | PO Number       | PO4              |
      | Internal Status | Sent To Customer |

  Scenario: Sent to Customer > Expire: Quote prices changed
    Given I should see Quote with:
      | Quote #         | 4                |
      | PO Number       | PO4              |
      | Internal Status | Sent to customer |
      | Customer Status | N/A              |
    When I click "Expire"
    And click "Mark as Expired"
    Then I should see "Quote #4 was successfully marked as expired" flash message
    And should see Quote with:
      | Quote #         | 4       |
      | PO Number       | PO4     |
      | Internal Status | Expired |
      | Customer Status | N/A     |

  Scenario: Under Review -> Decline: Quote prices changed
    Given I go to Sales/Quotes
    And I filter PO Number as is equal to "PO5"
    And click View PO5 in grid
    When I click "Decline"
    And I fill form with:
      | Comment | Decline reason note text <script>alert(1)</script> |
    And I click "Submit"
    Then I should see "Quote #5 declined" flash message
    And I should see Quote with:
      | PO Number       | PO5          |
      | Internal Status | Not Approved |
    And I collapse "Decline reason note text alert(1)" in activity list

  Scenario: Draft -> Delete: Internal status: Deleted, customer status: N/A
    Given I proceed as the Manager
    When go to Sales/Quotes
    And I filter PO Number as is equal to "PO6"
    And click view PO6 in grid
    And I should see Quote with:
      | Quote #         | 6     |
      | PO Number       | PO6   |
      | Internal Status | Draft |
      | Customer Status | N/A   |
    And I click "Delete"
    Then I should see "Quote #6 successfully deleted" flash message
    And should see Quote with:
      | Quote #         | 6       |
      | PO Number       | PO6     |
      | Internal Status | Deleted |
      | Customer Status | N/A     |

  Scenario: Delete -> Undelete: Internal status: Draft, customer status: N/A
    Given I go to Sales/Quotes
    When I filter PO Number as is equal to "PO6"
    And click view PO6 in grid
    And I should see Quote with:
      | Quote #         | 6       |
      | PO Number       | PO6     |
      | Internal Status | Deleted |
      | Customer Status | N/A     |
    And I click "Undelete"
    Then I should see "Quote #6 successfully undeleted" flash message
    And should see Quote with:
      | Quote #         | 6     |
      | PO Number       | PO6   |
      | Internal Status | Draft |
      | Customer Status | N/A   |

  Scenario Outline: Quotes send to customer
    Given I go to Sales/Quotes
    When I filter PO Number as is equal to "<PONumber>"
    And I click View <PONumber> in grid
    And I click "Send to Customer"
    And I click "Send"
    Then I should see Quote with:
      | PO Number       | <PONumber>       |
      | Internal Status | Sent to Customer |
    Examples:
      | PONumber |
      | PO7      |
      | PO8      |
      | PO9      |
      | PO10     |

  Scenario: Sent to Customer > Delete: Internal status: Deleted, customer status: N/A
    Given I go to Sales/Quotes
    When I filter PO Number as is equal to "PO7"
    And click view PO7 in grid
    And I should see Quote with:
      | Quote #         | 7                |
      | PO Number       | PO7              |
      | Internal Status | Sent to Customer |
      | Customer Status | N/A              |
    And I click "Delete"
    Then I should see "Quote #7 successfully deleted" flash message
    And should see Quote with:
      | Quote #         | 7       |
      | PO Number       | PO7     |
      | Internal Status | Deleted |
      | Customer Status | N/A     |

  Scenario: Sent to Customer > Create new Quote + Do Not Expire: Redirect to new Quote, Internal status: Draft, customer status: N/A
    Given I go to Sales/Quotes
    When I filter PO Number as is equal to "PO8"
    And click view PO8 in grid
    And I should see Quote with:
      | Quote #         | 8                |
      | PO Number       | PO8              |
      | Internal Status | Sent to customer |
      | Customer Status | N/A              |
    And I click "Create new Quote"
    And click "Submit"
    Then I should see "Quote #32 successfully created" flash message
    And email with Subject "Quote #32 has been created" containing the following was sent:
      | Subject | Quote #32 has been created |
    And should see Quote with:
      | Quote #         | 32    |
      | PO Number       | PO8   |
      | Internal Status | Draft |
      | Customer Status | N/A   |
    And I click "Edit"
    And fill form with:
      | PO Number | PO32 |
    And click "Submit"

    When I go to Sales/Quotes
    And I filter PO Number as is equal to "PO8"
    And click view PO8 in grid
    Then I should see Quote with:
      | Quote #         | 8                |
      | PO Number       | PO8              |
      | Internal Status | Sent to customer |
      | Customer Status | N/A              |

  Scenario: Sent to Customer > Create new Quote + Expire Immediately: Redirect to new Quote, Internal status: Draft, customer status: N/A
    Given I go to Sales/Quotes
    When I filter PO Number as is equal to "PO9"
    And click view PO9 in grid
    And I should see Quote with:
      | Quote #         | 9                |
      | PO Number       | PO9              |
      | Internal Status | Sent to customer |
      | Customer Status | N/A              |
    When I click "Create new Quote"
    And uncheck "Copy All Notes"
    And fill form with:
      | Expire Existing Quote | Immediately |
    And click "Submit"
    Then I should see "Quote #33 successfully created" flash message
    And should see "Quote #9 was successfully marked as expired" flash message
    And should see Quote with:
      | Quote #         | 33    |
      | PO Number       | PO9   |
      | Internal Status | Draft |
      | Customer Status | N/A   |
    And I click "Edit"
    And fill form with:
      | PO Number | PO33 |
    And click "Submit"

    When I go to Sales/Quotes
    And I filter PO Number as is equal to "PO9"
    And click view PO9 in grid
    Then I should see Quote with:
      | Quote #         | 9       |
      | PO Number       | PO9     |
      | Internal Status | Expired |
      | Customer Status | N/A     |

  Scenario: Expired > Reopen: Redirect to new Quote, Internal status: Draft, customer status: N/A
    Given I should see Quote with:
      | Quote #         | 9       |
      | PO Number       | PO9     |
      | Internal Status | Expired |
      | Customer Status | N/A     |
    When I click "Reopen"
    Then I should see "Quote #34 successfully created" flash message
    And should see Quote with:
      | Quote #         | 34    |
      | PO Number       | PO9   |
      | Internal Status | Draft |
      | Customer Status | N/A   |
    And I click "Edit"
    And fill form with:
      | PO Number | PO34 |
    And click "Submit"

  Scenario: Sent to Customer > Declined by Customer: Internal status: Declined, customer status: N/A
    Given I go to Sales/Quotes
    And I filter PO Number as is equal to "PO10"
    And I click view PO10 in grid
    And I should see Quote with:
      | Quote #         | 10               |
      | PO Number       | PO10             |
      | Internal Status | Sent to customer |
      | Customer Status | N/A              |
    And I click "Declined by Customer"
    Then I should see "Quote #10 successfully declined" flash message
    And should see Quote with:
      | Quote #         | 10       |
      | PO Number       | PO10     |
      | Internal Status | Declined |
      | Customer Status | N/A      |

  Scenario: Create a Quote from RFQ: Internal status: Draft, customer status: N/A, invisible for customer
    Given I signed in as AmandaRCole@example.org on the store frontend in old session
    And I open page with shopping list Shopping List 1
    And I click "More Actions"
    And I click "Request Quote"
    And I fill form with:
      | PO Number | PO35 |
    And I click "Submit Request"

    Then I am on dashboard
    And create a quote from RFQ with PO Number "PO35"
    Then I should see Quote with:
      | Quote #         | 35    |
      | PO Number       | PO35  |
      | Internal Status | Draft |
      | Customer Status | N/A   |

  Scenario: Draft -> Clone: Redirect to new Quote, Internal status: Draft, customer status: N/A
    Given I go to Sales/Quotes
    And I filter PO Number as is equal to "PO12"
    And click view PO12 in grid
    And I should see Quote with:
      | Quote #         | 12    |
      | PO Number       | PO12  |
      | Internal Status | Draft |
      | Customer Status | N/A   |
    When I click "Clone"
    Then I should see "Quote #36 successfully created" flash message
    And should see Quote with:
      | Quote #         | 36    |
      | PO Number       | PO12  |
      | Internal Status | Draft |
      | Customer Status | N/A   |

  Scenario: Sales Rep role remove "Override quote prices" and "Review and approve quotes"
    Given I proceed as the Admin
    When I go to System/User Management/Roles
    And I filter Label as is equal to "Sales Rep"
    And I click Edit Sales Rep in grid
    And I uncheck "Override quote prices" entity permission
    And I uncheck "Review and approve quotes" entity permission
    And I save and close form
    Then I should see "Role saved" flash message

  Scenario: Draft: Quote prices change are not allowed
    Given I proceed as the Manager
    And I go to Sales/Quotes
    When I filter PO Number as is equal to "PO13"
    And I click Edit PO13 in grid
    And I fill "Quote Form" with:
      | LineItemPrice | 10 |
    And I wait 2 seconds until submit button becomes available
    And I click "Submit"
    And I click "Save" in modal window
    Then I should see "Price overriding allowed by tier price only"
