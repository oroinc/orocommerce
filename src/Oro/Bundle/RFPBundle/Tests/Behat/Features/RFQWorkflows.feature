@fixture-RFQWorkflows.yml
Feature: Default RFQ Workflows

  Scenario: Logged in as buyer and manager on different window sessions
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    Given I login as administrator and use in "second_session" as "Manager"

  Scenario: Create RFQ from Quick Order Form and Check Internal status: Open and Customer status: Submitted
    Given I operate as the Buyer
    When I click "Account"
    And I click "Requests For Quote"
    And I click view 0110 in grid
    Then I should see RFQ status is "Submitted"
    When I continue as the Manager
    And I go to Sales/Requests For Quote
    And I click view 0110 in grid
    Then I should see RFQ with:
      | PO Number       | 0110      |
      | Internal Status | Open      |
      | Customer Status | Submitted |

  Scenario: Cancel RFQ and Check Internal status: Cancelled By Customer and Customer status: Cancelled
    Given I operate as the Buyer
    And I click "Account"
    And I click "Requests For Quote"
    And I click view 0110 in grid
    When I click "Cancel"
    Then I should see RFQ status is "Cancelled"
    When I continue as the Manager
    And I go to Sales/Requests For Quote
    And I click view 0110 in grid
    Then I should see RFQ with:
      | PO Number       | 0110                  |
      | Internal Status | Cancelled By Customer |
      | Customer Status | Cancelled             |

  Scenario: Resubmit RFQ and Check Internal status: Cancelled By Customer and Customer status: Cancelled
    Given I proceed as the Buyer
    And I click "Account"
    And I click "Requests For Quote"
    And I click view 0110 in grid
    When I click "Resubmit"
    Then I should see RFQ status is "Cancelled"
    And I should see "Your Request For Quote has been successfully resubmitted." flash message
    Then I follow "Request For Quote"
    And I should see RFQ status is "Submitted"
    And I remember Request id as "Submitted request Id"
    Then I continue as the Manager
    When I go to Sales/Requests For Quote
    And I click view 0110 in grid
    And I should see Resubmitted RFQ with:
      | PO Number       | 0110      |
      | Internal Status | Open      |
      | Customer Status | Submitted |
    When I click "Edit"
    And I fill form with:
      | PO Number | RE_01_10 |
    And I save and close form
    Then I should see Resubmitted RFQ with:
      | PO Number | RE_01_10 |
    When I go to Sales/Requests For Quote
    And I click view 0110 in grid
    And I should see Cancelled RFQ with:
      | PO Number       | 0110                  |
      | Internal Status | Cancelled By Customer |
      | Customer Status | Cancelled             |

  Scenario: Delete RFQ and Check Internal status: Deleted and Customer status: Cancelled
    Given I proceed as the Manager
    And I go to Sales/Requests For Quote
    And I click view 0110 in grid
    When I click "Delete"
    Then I should see RFQ with:
      | PO Number       | 0110      |
      | Internal Status | Deleted   |
      | Customer Status | Cancelled |
    And I remember Request id as "Deleted request Id"
    Then I switch to the "Buyer" session
    When I open RFQ view page on frontend with id "Deleted request Id"
    Then I should see "404 Not Found"

  Scenario: Undelete RFQ and Check Internal status: Deleted and Customer status: Cancelled
    Given I operate as the Manager
    And I go to Sales/Requests For Quote
    And I click view 0110 in grid
    When I click "Undelete"
    Then I should see RFQ with:
      | PO Number       | 0110                  |
      | Internal Status | Cancelled By Customer |
      | Customer Status | Cancelled             |
    When I switch to the "Buyer" session
    And I click "Account"
    And I click "Requests For Quote"
    And I click view 0110 in grid
    Then I should see RFQ status is "Cancelled"

  Scenario: Undelete RFQ and Check previous internal status after undelete
    Given I proceed as the Manager
    And I go to Sales/Requests For Quote
    And I click view 0111 in grid
    And I should see RFQ with:
      | PO Number       | 0111      |
      | Internal Status | Open      |
      | Customer Status | Submitted |
    When I click "Delete"
    Then I should see RFQ with:
      | PO Number       | 0111      |
      | Internal Status | Deleted   |
      | Customer Status | Submitted |
    And I remember RFQ id as "Deleted RFQ"
    Then I continue as the Buyer
    When I open RFQ view page on frontend with id "Deleted RFQ"
    Then I should see "404 Not Found"
    When I switch to the "Manager" session
    And I open RFQ view page on backend with id "Deleted RFQ"
    And I click "Undelete"
    Then I should see RFQ with:
      | PO Number       | 0111      |
      | Internal Status | Open      |
      | Customer Status | Submitted |

  Scenario: Create RFQ and Check Internal status: Processed and Customer status: Submitted
    Given I act like a Manager
    And I go to Sales/Requests For Quote
    And I click view 0112 in grid
    And I should see RFQ with:
      | PO Number       | 0112      |
      | Internal Status | Open      |
      | Customer Status | Submitted |
    When I click "Mark as Processed"
    Then I should see RFQ with:
      | PO Number       | 0112      |
      | Internal Status | Processed |
      | Customer Status | Submitted |
    When I click "Delete"
    Then I should see RFQ with:
      | PO Number       | 0112      |
      | Internal Status | Deleted   |
      | Customer Status | Submitted |
    When I click "Undelete"
    Then I should see RFQ with:
      | PO Number       | 0112      |
      | Internal Status | Open      |
      | Customer Status | Submitted |

  Scenario: Create RFQ and Check Internal status: More Info Requested and Customer status: Requires Attention
    Given I act like a Manager
    And I go to Sales/Requests For Quote
    And I click view 0112 in grid
    And I should see RFQ with:
      | PO Number       | 0112      |
      | Internal Status | Open      |
      | Customer Status | Submitted |
    When I click "Request More Information"
    And I fill "Request More Information Popup Form" with:
      | Notes | Message for customer |
    And I click "Submit"
    Then I should see RFQ with:
      | PO Number       | 0112                |
      | Internal Status | More Info Requested |
      | Customer Status | Requires Attention  |
    Then I continue as the Buyer
    And I click "Account"
    And I click "Requests For Quote"
    And I click view 0112 in grid
    Then I should see RFQ status is "Requires Attention"
    And I should see that "Request Notes Block" contains "Message for customer"
    When I click "Provide More Information"
    And I fill "Request More Information Popup Form" with:
      | Notes | Answer for manager |
    And I click "Submit"
    Then I should see that "Request Notes Block" contains "Answer for manager"
    And I should see RFQ status is "Submitted"
    When I continue as the Manager
    And I go to Sales/Requests For Quote
    And I click view 0112 in grid
    Then I should see RFQ with:
      | PO Number       | 0112      |
      | Internal Status | Open      |
      | Customer Status | Submitted |

  Scenario: Cancel RFQ after Customer status: Requires Attention
    Given I operate as the Manager
    And I go to Sales/Requests For Quote
    And I click view 0113 in grid
    And I should see RFQ with:
      | PO Number       | 0113      |
      | Internal Status | Open      |
      | Customer Status | Submitted |
    When I click "Request More Information"
    And I fill "Request More Information Popup Form" with:
      | Notes | Note 'Test' for customer |
    And I click "Submit"
    Then I should see RFQ with:
      | PO Number       | 0113                |
      | Internal Status | More Info Requested |
      | Customer Status | Requires Attention  |
    Then I continue as the Buyer
    And I click "Account"
    And I click "Requests For Quote"
    And I click view 0113 in grid
    Then I should see RFQ status is "Requires Attention"
    When I click "Cancel"
    Then I should see RFQ status is "Cancelled"
    Then I proceed as the Manager
    When I go to Sales/Requests For Quote
    And I click view 0113 in grid
    Then I should see RFQ with:
      | PO Number       | 0113                  |
      | Internal Status | Cancelled By Customer |
      | Customer Status | Cancelled             |

  Scenario: Reprocess RFQ after Customer status: Cancelled By Customer
    Given I operate as the Manager
    And I go to Sales/Requests For Quote
    And I click view 0113 in grid
    And I should see RFQ with:
      | PO Number       | 0113                  |
      | Internal Status | Cancelled By Customer |
      | Customer Status | Cancelled             |
    When I click "Reprocess"
    Then I should see RFQ with:
      | PO Number       | 0113      |
      | Internal Status | Open      |
      | Customer Status | Submitted |

  Scenario: Delete RFQ with Customer status: Requires Attention and Check status after Undelete
    Given I operate as the Manager
    And I go to Sales/Requests For Quote
    And I click view 0113 in grid
    And I should see RFQ with:
      | PO Number       | 0113      |
      | Internal Status | Open      |
      | Customer Status | Submitted |
    When I click "Request More Information"
    And I fill "Request More Information Popup Form" with:
      | Notes | Note 'Test' for customer |
    And I click "Submit"
    And I click "Delete"
    Then I should see RFQ with:
      | PO Number       | 0113               |
      | Internal Status | Deleted            |
      | Customer Status | Requires Attention |
    And I remember RFQ id as "Deleted RFQ"
    When I proceed as the Buyer
    And I open RFQ view page on frontend with id "Deleted RFQ"
    Then I should see "404 Not Found"
    When I continue as the Manager
    And I go to Sales/Requests For Quote
    And I click view 0113 in grid
    Then I click "Undelete"
    And I should see RFQ with:
      | PO Number       | 0113                |
      | Internal Status | More Info Requested |
      | Customer Status | Requires Attention  |
    When I switch to the "Buyer" session
    And I click "Account"
    And I click "Requests For Quote"
    And I click view 0113 in grid
    Then I should see RFQ status is "Requires Attention"

  Scenario: Decline RFQ and Check Internal status: Declined and Customer status: Cancelled
    Given I operate as the Manager
    And I go to Sales/Requests For Quote
    And I click view 0114 in grid
    And I should see RFQ with:
      | PO Number       | 0114      |
      | Internal Status | Open      |
      | Customer Status | Submitted |
    When I click "Decline"
    Then I should see RFQ with:
      | PO Number       | 0114      |
      | Internal Status | Declined  |
      | Customer Status | Cancelled |
    When I switch to the "Buyer" session
    And I click "Account"
    And I click "Requests For Quote"
    And I click view 0114 in grid
    Then I should see RFQ status is "Cancelled"

  Scenario: Reprocess RFQ after Internal Status: Declined
    Given I operate as the Manager
    And I go to Sales/Requests For Quote
    And I click view 0114 in grid
    And I should see RFQ with:
      | PO Number       | 0114      |
      | Internal Status | Declined  |
      | Customer Status | Cancelled |
    When I click "Reprocess"
    Then I should see RFQ with:
      | PO Number       | 0114      |
      | Internal Status | Open      |
      | Customer Status | Submitted |

  Scenario: Delete RFQ after Internal Status: Declined
    Given I operate as the Manager
    And I go to Sales/Requests For Quote
    And I click view 0114 in grid
    And I should see RFQ with:
      | PO Number       | 0114      |
      | Internal Status | Open      |
      | Customer Status | Submitted |
    When I click "Decline"
    Then I should see RFQ with:
      | PO Number       | 0114      |
      | Internal Status | Declined  |
      | Customer Status | Cancelled |
    When I click "Delete"
    Then I should see RFQ with:
      | PO Number       | 0114      |
      | Internal Status | Deleted   |
      | Customer Status | Cancelled |
    And I remember RFQ id as "Deleted RFQ"
    Then I switch to the "Buyer" session
    When I open RFQ view page on frontend with id "Deleted RFQ"
    Then I should see "404 Not Found"
