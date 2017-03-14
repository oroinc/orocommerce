@fixture-QuoteBackofficeDefaultFixture.yml
Feature: Quote Backoffice Default

  Scenario: Draft -> Edit, Quote #11. Internal status: Draft, customer status: N/A
    Given I login as administrator
    And go to Sales/Quotes
    And click view PO11 in grid
    Then I should see Quote with:
      | Quote # | 11 |
      | PO Number | PO11 |
      | Internal Status | Draft |
      | Customer Status | N/A |
    When I click "Edit"
    And fill form with:
      | PO Number | PO11-updated |
    And click "Submit"
    Then I should see "Quote #11 successfully updated" flash message
    And should see Quote with:
      | Quote # | 11 |
      | PO Number | PO11-updated |
      | Internal Status | Draft |
      | Customer Status | N/A |

    Given I login as AmandaRCole@example.org buyer
    And click "Quotes"
    Then there is no "PO11" in grid

  Scenario: Drfat -> Clone, Quote #11. Redirect to new Quote, internal status: Draft, customer status: N/A
    Given I login as administrator
    And go to Sales/Quotes
    And click view PO11 in grid
    Then I should see Quote with:
      | Quote # | 11 |
      | PO Number | PO11 |
      | Internal Status | Draft |
      | Customer Status | N/A |
    When I click "Clone"
    Then I should see "Quote #31 successfully created" flash message
    And should see Quote with:
      | Quote # | 31 |
      | PO Number | PO11 |
      | Internal Status | Draft |
      | Customer Status | N/A |

    Given I login as AmandaRCole@example.org buyer
    And click "Quotes"
    Then there is no "PO31" in grid

  Scenario: Draft -> Delete, Quote #12. Internal status: Deleted, customer status: N/A
    Given I login as administrator
    And go to Sales/Quotes
    And click view PO12 in grid
    Then I should see Quote with:
      | Quote # | 12 |
      | PO Number | PO12 |
      | Internal Status | Draft |
      | Customer Status | N/A |
    When I click "Delete"
    Then I should see "Quote #12 successfully deleted" flash message
    And should see Quote with:
      | Quote # | 12 |
      | PO Number | PO12 |
      | Internal Status | Deleted |
      | Customer Status | N/A |

    Given I login as AmandaRCole@example.org buyer
    And click "Quotes"
    Then there is no "PO12" in grid

  Scenario: Delete -> Undelete, Quote #12. Internal status: Draft, customer status: N/A
    Given I login as administrator
    And go to Sales/Quotes
    And click view PO12 in grid
    Then I should see Quote with:
      | Quote # | 12 |
      | PO Number | PO12 |
      | Internal Status | Deleted |
      | Customer Status | N/A |
    When I click "Undelete"
    Then I should see "Quote #12 successfully undeleted" flash message
    And should see Quote with:
      | Quote # | 12 |
      | PO Number | PO12 |
      | Internal Status | Draft |
      | Customer Status | N/A |

    Given I login as AmandaRCole@example.org buyer
    And click "Quotes"
    Then there is no "PO12" in grid

  Scenario: Draft -> Send to Customer, Quote #13. Internal status: Sent to Customer, customer status: N/A
    Given I login as administrator
    And go to Sales/Quotes
    And click view PO13 in grid
    Then I should see Quote with:
      | Quote # | 13 |
      | PO Number | PO13 |
      | Internal Status | Draft |
      | Customer Status | N/A |
    When I click "Send to Customer"
    And click "Send"
    Then I should see "Quote #13 successfully sent to customer" flash message
    And should see Quote with:
      | Quote # | 13 |
      | PO Number | PO13 |
      | Internal Status | Sent to Customer |
      | Customer Status | N/A |

    Given I login as AmandaRCole@example.org buyer
    And click "Quotes"
    And click View PO13 in grid
    Then I should see Quote on front store with data:
      | PO Number | PO13 |

  Scenario: Sent to Customer > Cancel, Quote #14. Internal status: Cancelled, customer status: N/A
    Given I login as administrator
    And go to Sales/Quotes
    And click view PO14 in grid
    And click "Send to Customer"
    And click "Send"
    Then I should see Quote with:
      | Quote # | 14 |
      | PO Number | PO14 |
      | Internal Status | Sent to customer |
      | Customer Status | N/A |
    When I click "Cancel"
    Then I should see "Quote #14 successfully cancelled" flash message
    And should see Quote with:
      | Quote # | 14 |
      | PO Number | PO14 |
      | Internal Status | Cancelled |
      | Customer Status | N/A |

    Given I login as AmandaRCole@example.org buyer
    And click "Quotes"
    And click View PO14 in grid
    Then I should see Quote on front store with data:
      | PO Number | PO14 |

  Scenario: Sent to Customer > Expire, Quote #15. Internal status: Expired, customer status: N/A
    Given I login as administrator
    And go to Sales/Quotes
    And click view PO15 in grid
    And click "Send to Customer"
    And click "Send"
    Then I should see Quote with:
      | Quote # | 15 |
      | PO Number | PO15 |
      | Internal Status | Sent to customer |
      | Customer Status | N/A |
    When I click "Expire"
    Then I should see "Quote #15 was successfully marked as expired" flash message
    And should see Quote with:
      | Quote # | 15 |
      | PO Number | PO15 |
      | Internal Status | Expired |
      | Customer Status | N/A |

    Given I login as AmandaRCole@example.org buyer
    And click "Quotes"
    And click View PO15 in grid
    Then I should see Quote on front store with data:
      | PO Number | PO15 |

  Scenario: Sent to Customer > Delete, Quote #16. Internal status: Deleted, customer status: N/A
    Given I login as administrator
    And go to Sales/Quotes
    And click view PO16 in grid
    And click "Send to Customer"
    And click "Send"
    Then I should see Quote with:
      | Quote # | 16 |
      | PO Number | PO16 |
      | Internal Status | Sent to customer |
      | Customer Status | N/A |
    When I click "Delete"
    Then I should see "Quote #16 successfully deleted" flash message
    And should see Quote with:
      | Quote # | 16 |
      | PO Number | PO16 |
      | Internal Status | Deleted |
      | Customer Status | N/A |

    Given I login as AmandaRCole@example.org buyer
    And I click "Quotes"
    Then there is no "PO16" in grid

  Scenario: Sent to Customer > Create new Quote + Do Not Expire, Quote #17. Redirect to new Quote, internal status: Draft, customer status: N/A
    Given I login as administrator
    And go to Sales/Quotes
    And click view PO17 in grid
    And click "Send to Customer"
    And click "Send"
    Then I should see Quote with:
      | Quote # | 17 |
      | PO Number | PO17 |
      | Internal Status | Sent to customer |
      | Customer Status | N/A |
    When I click "Create new Quote"
    And click "Submit"
    Then I should see "Quote #32 successfully created" flash message
    And should see Quote with:
      | Quote # | 32 |
      | PO Number | PO17 |
      | Internal Status | Draft |
      | Customer Status | N/A |
    And should see "Customer Notes17"
    And should see "Seller Notes17"
    And I click "Edit"
    And fill form with:
      | PO Number | PO32 |
    And click "Submit"

    When I go to Sales/Quotes
    And click view PO17 in grid
    Then I should see Quote with:
      | Quote # | 17 |
      | PO Number | PO17 |
      | Internal Status | Sent to customer |
      | Customer Status | N/A |

    Given I login as AmandaRCole@example.org buyer
    And click "Quotes"
    Then there is no "PO32" in grid
    When I click View PO17 in grid
    Then I should see Quote on front store with data:
      | PO Number | PO17 |
    And should see "My Notes: Customer Notes17"
    And should see "Seller Notes: Seller Notes17"

  Scenario: Sent to Customer > Create new Quote + Expire Immediately, Quote #18. Redirect to new Quote, internal status: Draft, customer status: N/A
    Given I login as administrator
    And go to Sales/Quotes
    And click view PO18 in grid
    And click "Send to Customer"
    And click "Send"
    Then I should see Quote with:
      | Quote # | 18 |
      | PO Number | PO18 |
      | Internal Status | Sent to customer |
      | Customer Status | N/A |
    When I click "Create new Quote"
    And uncheck "Copy All Notes"
    And fill form with:
      | Expire Existing Quote | Immediately |
    And click "Submit"
    Then I should see "Quote #33 successfully created" flash message
    And should see "Quote #18 was successfully marked as expired" flash message
    And should see Quote with:
      | Quote # | 33 |
      | PO Number | PO18 |
      | Internal Status | Draft |
      | Customer Status | N/A |
    And should not see "Customer Notes17"
    And should not see "Seller Notes17"
    And I click "Edit"
    And fill form with:
      | PO Number | PO33 |
    And click "Submit"

    When I go to Sales/Quotes
    And click view PO18 in grid
    Then I should see Quote with:
      | Quote # | 18 |
      | PO Number | PO18 |
      | Internal Status | Expired |
      | Customer Status | N/A |

    Given I login as AmandaRCole@example.org buyer
    And click "Quotes"
    Then there is no "PO33" in grid
    When I click View PO18 in grid
    Then I should see Quote on front store with data:
      | PO Number | PO18 |
    And should see "This quote has expired. You may submit a new request for quote." flash message
    And should not see "My Notes: Customer Notes17"
    And should not see "Seller Notes: Seller Notes17"

  Scenario: Sent to Customer > Declined by Customer, Quote #19. Internal status: Declined, customer status: N/A
    Given I login as administrator
    And go to Sales/Quotes
    And click view PO19 in grid
    And click "Send to Customer"
    And click "Send"
    Then I should see Quote with:
      | Quote # | 19 |
      | PO Number | PO19 |
      | Internal Status | Sent to customer |
      | Customer Status | N/A |
    When I click "Declined by Customer"
    Then I should see "Quote #19 successfully declined" flash message
    And should see Quote with:
      | Quote # | 19 |
      | PO Number | PO19 |
      | Internal Status | Declined |
      | Customer Status | N/A |

    Given I login as AmandaRCole@example.org buyer
    And click "Quotes"
    And click View PO19 in grid
    Then I should see Quote on front store with data:
      | PO Number | PO19 |

  Scenario: Expired > Reopen, Quote #15. Redirect to new Quote, internal status: Draft, customer status: N/A
    Given I login as administrator
    And go to Sales/Quotes
    And click view PO15 in grid
    Then I should see Quote with:
      | Quote # | 15 |
      | PO Number | PO15 |
      | Internal Status | Expired |
      | Customer Status | N/A |
    When I click "Reopen"
    Then I should see "Quote #34 successfully created" flash message
    And should see Quote with:
      | Quote # | 34 |
      | PO Number | PO15 |
      | Internal Status | Draft |
      | Customer Status | N/A |
    And I click "Edit"
    And fill form with:
      | PO Number | PO34 |
    And click "Submit"

    Given I login as AmandaRCole@example.org buyer
    When I click "Quotes"
    Then there is no "PO34" in grid

  Scenario: Cancelled > Reopen, Quote #14. Redirect to new Quote, internal status: Draft, customer status: N/A
    Given I login as administrator
    And go to Sales/Quotes
    And I click view PO14 in grid
    Then I should see Quote with:
      | Quote # | 14 |
      | PO Number | PO14 |
      | Internal Status | Cancelled |
      | Customer Status | N/A |
    And I click "Reopen"
    Then I should see "Quote #35 successfully created" flash message
    And I should see Quote with:
      | Quote # | 35 |
      | PO Number | PO14 |
      | Internal Status | Draft |
      | Customer Status | N/A |
    And I click "Edit"
    And fill form with:
      | PO Number | PO35 |
    And click "Submit"

    Given I login as AmandaRCole@example.org buyer
    When I click "Quotes"
    Then there is no "PO35" in grid

  Scenario: Create a Quote #36 from RFQ. Internal status: Draft, customer status: N/A, invisible for customer
    Given I login as AmandaRCole@example.org buyer
    And request a quote from shopping list "Shopping List 1" with data:
      | PO Number | PO36 |

    Given I login as administrator
    And create a quote from RFQ with PO Number "PO36"
    Then I should see Quote with:
      | Quote # | 36 |
      | PO Number | PO36 |
      | Internal Status | Draft |
      | Customer Status | N/A |

    Given I login as AmandaRCole@example.org buyer
    And click "Quotes"
    Then there is no "PO36" in grid
