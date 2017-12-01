@ticket-BB-11014
@fixture-OroSaleBundle:Quote.yml
Feature: Quote Address
  ToDo: BAP-16103 Add missing descriptions to the Behat features

  Scenario: Check Quote Shipping Address Labels
    Given I login as administrator
    And go to Sales/ Quotes
    And click edit Quote1 in grid
    Then I should see "Quote Shipping Address Select" with options:
      | Value                                        | Type   |
      | Customer Address Book                        | Group  |
      | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 | Option |
      | User Address Book                            | Group  |
      | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 | Option |
      | Enter other address                          | Option |
