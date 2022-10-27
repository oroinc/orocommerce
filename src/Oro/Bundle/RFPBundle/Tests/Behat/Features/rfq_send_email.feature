@ticket-BB-16499
@fixture-OroRFPBundle:RFQWorkflows.yml
@regression
Feature: RFQ Send Email
  In order to efficiently communicate in a request for quote's context
  As an Administrator
  I should be able to send email to email specified in a request for quote

  Scenario: Check that email is sent to the specific email
    Given I login as administrator
    And I go to Sales/Requests For Quote
    And I click view 0111 in grid
    When I follow "More actions"
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
