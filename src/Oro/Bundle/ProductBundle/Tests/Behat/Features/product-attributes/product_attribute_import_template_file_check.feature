@regression
@ticket-BB-16260

Feature: Product attribute import template file check
  In order to effectively manage custom product attributes
  As an Administrator
  I want to be sure that generated import template have no errors

  Scenario: Check product attributes template
    Given I login as administrator
    And I go to Products/Product Attributes
    And I download Product Attributes' Data Template file
    When I import downloaded template file
    Then Email should contains the following "Errors: 0" text
    When I reload the page
    Then I should see an "Update Schema" element
