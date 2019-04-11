@ticket-BB-16499
@fixture-OroSaleBundle:Quote.yml
@regression
Feature: Quote Send Email
  In order to efficiently communicate in quote's context
  As an Administrator
  I should be able to send email to quote's customer user

  Scenario: Check that email is sent to the specific customer user's email
    Given I login as administrator
    And go to Sales/ Quotes
    When I click view Quote1 in grid
    And I follow "More actions"
    And I click "Send email"
    Then "Email Form" must contains values:
      | From    | "John Doe" <admin@example.com>                            |
      | ToField | ["Amanda Cole" <AmandaRCole@example.org> (Customer User)] |
    When I fill "Email Form" with:
      | Subject | Test      |
      | Body    | Test body |
    And I click "Send"
    Then Email should contains the following:
      | To      | AmandaRCole@example.org |
      | Subject | Test                    |
      | Body    | Test body               |
