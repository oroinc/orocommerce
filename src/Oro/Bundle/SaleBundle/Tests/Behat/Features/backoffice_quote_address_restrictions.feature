@regression
@ticket-BB-15930
@fixture-OroSaleBundle:QuoteAddressPermissionsCustomerUsers.yml

Feature: Backoffice Quote address restrictions
  In order to check address ACL permissions
  As an Administrator
  I want to check system behavior when the user tries to edit quote shipping address when it is forbidden

  Scenario: Disallow all quote address related permissions
    Given I login as administrator
    And I go to System/ User Management/ Roles
    And click edit "Administrator" in grid
    And select following permissions:
      | Customer Address      | View:None |
      | Customer User Address | View:None |
    And I click "Quotes" tab
    And I uncheck "Enter The Shipping Address Manually" entity permission
    And I uncheck "Use Any Shipping Address From The Customer User's Address Book" entity permission
    And I uncheck "Use The Default Shipping Address From The Customer User's Address Book" entity permission
    And I uncheck "Use Any Shipping Address From The Customer Address Book" entity permission
    When I save form
    Then I should see "Role saved" flash message

  Scenario: Create quote has no shipping address form when all permissions disabled
    Given go to Sales/Quotes
    And I click "Create Quote"
    And I should not see "Shipping Address"
    When I fill "Quote Form" with:
      | Customer | Acme_1 |
    Then I should not see "There was an error performing the requested operation" flash message
    And I click "Cancel"

  Scenario: Shipping address form is available for quote when Enter The Shipping Address Manually is disabled
    Given I go to System/ User Management/ Roles
    And click edit "Administrator" in grid
    And select following permissions:
      | Customer Address      | View:Global |
      | Customer User Address | View:Global |
    And I click "Quotes" tab
    And I uncheck "Enter The Shipping Address Manually" entity permission
    And I check "Use Any Shipping Address From The Customer User's Address Book" entity permission
    And I check "Use The Default Shipping Address From The Customer User's Address Book" entity permission
    And I check "Use Any Shipping Address From The Customer Address Book" entity permission
    And I save form
    And I should see "Role saved" flash message
    And go to Sales/Quotes
    And I click "Create Quote"
    And I should see "Shipping Address"
    When I fill "Quote Form" with:
      | Customer | Acme_1 |
    Then I should see "Quote Shipping Address Select" with options:
      | Value                                       | Type   |
      | Customer Address Book                       | Group  |
      | ORO, Fifth avenue C1, 10115 Berlin, Germany | Option |
    When I fill "Quote Form" with:
      | Shipping Address | ORO, Fifth avenue C1, 10115 Berlin, Germany |
    And I fill "Quote Form" with:
      | Customer User | Acme_User_1 |
    Then Quote Shipping Address Select field is empty
    And I should see "Quote Shipping Address Select" with options:
      | Value                                        | Type   |
      | Customer Address Book                        | Group  |
      | ORO, Fifth avenue C1, 10115 Berlin, Germany  | Option |
      | User Address Book                            | Group  |
      | ORO, Fifth avenue CU1, 10115 Berlin, Germany | Option |
    When I fill "Quote Form" with:
      | Shipping Address | ORO, Fifth avenue CU1, 10115 Berlin, Germany |
    And I fill "Quote Form" with:
      | Customer | Acme_2 |
    Then Quote Shipping Address Select field is empty
    And Quote Shipping Address Select has no options
