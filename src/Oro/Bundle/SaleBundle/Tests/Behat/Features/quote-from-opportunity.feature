@regression
@ticket-BB-9611
@ticket-BB-9613
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroWarehouseBundle:Checkout.yml
Feature: Quote from Opportunity Default
  In order to check quote-opportunity workflow
  As a administrator
  I want to create quote from opportunity

  Scenario: Enable OroCommerce Opportunity Workflow
    Given I login as administrator
    And I go to System/ Workflows
    When I click activate "OroCommerce Opportunity Flow" in grid
    And I click "Activate"
    Then I should see "Workflow activated" flash message

  Scenario: Setup system configuration of Warehouses
    Given I go to System/Configuration
    When I follow Commerce/Inventory/Warehouses on configuration sidebar
    And I choose Warehouse "Test Warehouse" in 1 row
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Create new Opportunity
    Given I go to Customers/ Customers
    And I click view "Company A" in grid
    And I follow "More actions"
    And I follow "Create Opportunity"
    And I fill form with:
      | Opportunity Name | Opportunity #1 |
    When I save and close form
    Then I should see "Opportunity saved" flash message

  Scenario: Create new Quote
    Given I press "Create quote"
    And I fill "Quote Form" with:
      | Assigned Customer Users | Amanda Cole |
      | LineItemProduct         | SKU123      |
      | LineItemPrice           | 1           |
    When I save and close form
    And I click "Save" in modal window
    Then I should see "Quote has been saved" flash message
    And I should see following "Quotes Grid" grid:
      | Quote # | Step  |
      | 2       | Draft |

  Scenario: View and Clone quote
    Given I go to Sales/ Quotes
    And I open Quote with qid 2
    When I click "Clone"
    Then I should see "Quote #3 successfully created" flash message
    And should see Quote with:
      | Quote #         | 3     |
      | Internal Status | Draft |

  Scenario: Edit Quote
    Given I click "Edit"
    And I fill form with:
      | PO Number | PO#3 |
    When I click "Submit"
    Then I should see "Quote #3 successfully updated" flash message
    And should see Quote with:
      | Quote #         | 3     |
      | Internal Status | Draft |
      | PO Number       | PO#3  |

  Scenario: Delete Quote
    Given I click "Delete"
    Then I should see "Quote #3 successfully deleted" flash message
    And should see Quote with:
      | Quote #         | 3       |
      | Internal Status | Deleted |

  Scenario: Undelete Quote
    Given I click "Undelete"
    Then I should see "Quote #3 successfully undeleted" flash message
    And should see Quote with:
      | Quote #         | 3     |
      | Internal Status | Draft |

  Scenario: Send Quote to Customer
    Given I click "Send to Customer"
    And I fill form with:
      | To | vkovalenko@oroinc.com |
    When I click "Send"
    Then I should see "Quote #3 successfully sent to customer" flash message
    And should see Quote with:
      | Quote #         | 3                |
      | Internal Status | Sent to Customer |

   Scenario: Cancel sent Quote
     Given I click "Cancel"
     Then I should see "Quote #3 successfully cancelled" flash message
     And should see Quote with:
       | Quote #         | 3         |
       | Internal Status | Cancelled |

  Scenario: Reopen cancelled Quote
    Given I click "Reopen"
    Then I should see "Quote #4 successfully created" flash message
    And should see Quote with:
      | Quote #         | 4     |
      | Internal Status | Draft |

  Scenario: Expire sent Quote
    Given I click "Send to Customer"
    And I fill form with:
      | To | vkovalenko@oroinc.com |
    And I click "Send"
    When I click "Expire"
    And I click "Mark as Expired" in modal window
    Then I should see "Quote #4 was successfully marked as expired" flash message
    And should see Quote with:
      | Quote #         | 4       |
      | Internal Status | Expired |

  Scenario: Delete sent Quote
    Given I click "Reopen"
    And I click "Send to Customer"
    And I fill form with:
      | To | vkovalenko@oroinc.com |
    And I click "Send"
    When I click "Delete"
    Then I should see "Quote #5 successfully deleted" flash message
    And should see Quote with:
      | Quote #         | 5      |
      | Internal Status | Delete |

  Scenario: Decline sent Quote By Customer
    Given I click "Undelete"
    And I click "Send to Customer"
    And I fill form with:
      | To | vkovalenko@oroinc.com |
    And I click "Send"
    When I click "Declined by Customer"
    Then I should see "Quote #5 successfully declined" flash message
    And should see Quote with:
      | Quote #         | 5        |
      | Internal Status | Declined |

  Scenario: Create new Quote from existing One without expiring
    Given I click "Reopen"
    When I click "Send to Customer"
    And I fill form with:
      | To | vkovalenko@oroinc.com |
    And I click "Send"
    And I click "Create new Quote"
    And I fill form with:
      | Expire Existing Quote | Do Not Expire |
    And I click "Submit"
    Then I should see "Quote #7 successfully created" flash message
    And should see Quote with:
      | Quote #         | 7     |
      | Internal Status | Draft |
    When I go to Sales/ Quotes
    And I open Quote with qid 6
    Then should see Quote with:
      | Quote #         | 6                |
      | Internal Status | Sent to Customer |

  Scenario: Create new Quote from existing One with expiring
    Given I click "Create new Quote"
    And I fill form with:
      | Expire Existing Quote | Immediately |
    When I click "Submit"
    Then I should see "Quote #8 successfully created" flash message
    And should see Quote with:
      | Quote #         | 8     |
      | Internal Status | Draft |
    When I go to Sales/ Quotes
    And I open Quote with qid 6
    Then should see Quote with:
      | Quote #         | 6       |
      | Internal Status | Expired |

  Scenario: Create new Quote from existing One with expiring upon acceptance
    Given I go to Sales/ Quotes
    And I click view "2" in grid
    And I click "Send to Customer"
    And I fill form with:
      | To | vkovalenko@oroinc.com |
    And I click "Send"
    When I click "Create new Quote"
    And I fill form with:
      | Expire Existing Quote | Upon Acceptance |
    And I click "Submit"
    Then I should see "Quote #9 successfully created" flash message

    When I click "Send to Customer"
    And I fill form with:
      | To | vkovalenko@oroinc.com |
    And I click "Send"
    And I signed in as AmandaRCole@example.org on the store frontend
    And click "Account"
    And click "Quotes"
    And I click view "9" in grid
    And I click "Accept and Submit to Order"
    And I click "Submit"
    And I click "Continue"
    And I click "Continue"
    And I click on "CheckoutFormRow"
    And I click "Continue"
    And I click "Continue"
    And I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"
# related functionality not implemented yet
#    And I login as administrator
#    And I go to Sales/ Quotes
#    And I click view "8" in grid
#    Then should see Quote with:
#      | Quote #         | 8       |
#      | Internal Status | Expired |
