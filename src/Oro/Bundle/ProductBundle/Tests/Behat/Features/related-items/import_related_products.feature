@regression
@ticket-BB-18183
@fixture-OroProductBundle:RelatedProductsFixture.yml
Feature: Import Related Products
  In order to import related products
  As an Administrator
  I want to have an ability Import all related products from the file into the system

  Scenario: Check import error page from the email after validating import file when import with missing columns
    Given I login as administrator
    When I go to Products/ Products
    And I open "Related Products" import tab
    And fill import file with data:
      | test1 | test2 |
      | test3 | test4 |
    When I open "Related Products" import tab
    And I validate file
    Then Email should contains the following "Errors: 2 processed: 0, read: 1" text
    And I follow "Error log" link from the email
    And I should see "Error in row #1. SKU column is missing"
    And I should see "Error in row #1. Related SKUs column is missing"

  Scenario: Verify export Related Products template
    Given I login as administrator
    When I go to Products/ Products
    And I open "Related Products" import tab
    And I download "Related Products" Data Template file
    Then I see SKU column
    And I see Related SKUs column

  Scenario: Check import error page from the email after validating import file when import unknown SKU
    Given fill template with data:
      | SKU   | Related SKUs |
      | psku4 | psku1,psku3  |
    When I open "Related Products" import tab
    And I validate file
    Then Email should contains the following "Errors: 1 processed: 0, read: 1" text
    And I follow "Error log" link from the email
    And I should see "Error in row #1. SKU not found"

  Scenario: Check import error page from the email after validating import file when import unknown related SKU
    Given I login as administrator
    And I go to Products/ Products
    And fill template with data:
      | SKU   | Related SKUs |
      | psku2 | psku4        |
    When I open "Related Products" import tab
    And I validate file
    Then Email should contains the following "Errors: 1 processed: 0, read: 1" text
    And I follow "Error log" link from the email
    And I should see "Error in row #1. Not found entity \"Product\". Item data: \"psku4\"."

  Scenario: Check import error page from the email after validating import file when import own SKU
    Given I login as administrator
    When I go to Products/ Products
    And fill template with data:
      | SKU   | Related SKUs |
      | psku2 | psku2        |
    And I open "Related Products" import tab
    And I validate file
    Then Email should contains the following "Errors: 1 processed: 0, read: 1" text
    And I follow "Error log" link from the email
    And I should see "Error in row #1. It is not possible to create relations from product to itself."

  Scenario: Import more than limit
    Given I login as administrator
    And I go to System/Configuration
    And follow "Commerce/Catalog/Related Items" on configuration sidebar
    And fill "RelatedProductsConfig" with:
      | Maximum Number Of Assigned Items Use Default | false |
      | Maximum Number Of Assigned Items             | 1     |
    And click "Save settings"
    And I should see "Configuration saved" flash message
    When I go to Products/ Products
    And fill template with data:
      | SKU   | Related SKUs |
      | psku2 | psku1,psku3  |
    When I open "Related Products" import tab
    And import file
    Then Email should contains the following "Errors: 1 processed: 0, read: 1" text
    And I follow "Error log" link from the email
    And I should see "Error in row #1. It is not possible to add more items, because of the limit of relations."

  Scenario: Verify import Related Products from the file
    Given I login as administrator
    And I go to System/Configuration
    And follow "Commerce/Catalog/Related Items" on configuration sidebar
    And fill "RelatedProductsConfig" with:
      | Maximum Number Of Assigned Items | 3 |
    And click "Save settings"
    And I should see "Configuration saved" flash message
    When I go to Products/ Products
    And fill template with data:
      | SKU   | Related SKUs |
      | psku2 | psku1,psku3  |
    And I open "Related Products" import tab
    And import file
    Then Email should contains the following "Errors: 0 processed: 2, read: 1, added: 2, updated: 0, replaced: 0" text
    And I click View psku2 in grid
    And I click "Related Items"
    And records in "RelatedProductsViewGrid" should be 2
    And I sort "RelatedProductsViewGrid" by "SKU"
    And I should see following "RelatedProductsViewGrid" grid:
      | SKU   |
      | psku1 |
      | psku3 |

  Scenario: Import already exist relation
    And I go to Products/ Products
    And fill template with data:
      | SKU   | Related SKUs |
      | psku2 | psku1,psku3  |
    When I open "Related Products" import tab
    And import file
    Then Email should contains the following "Errors: 0 processed: 0, read: 1, added: 0, updated: 0, replaced: 0" text

  Scenario: Import empty related SKU
    And I go to Products/ Products
    And fill template with data:
      | SKU   | Related SKUs |
      | psku2 | psku1,,psku3  |
    When I open "Related Products" import tab
    And import file
    Then Email should contains the following "Errors: 1 processed: 0, read: 1, added: 0, updated: 0, replaced: 0" text
    And I follow "Error log" link from the email
    And I should see "Error in row #1. Related SKUs collection contains empty SKU. Item data:"

  Scenario: Import when no edit permission
    Given I login as administrator
    And I go to Products/ Products
    And I click "Import file"
    And I should see "Related Products"
    And I click "Cancel"
    When I go to System/User Management/Roles
    And click edit "Administrator" in grid
    And I uncheck "[Related Products] Edit Related Products" entity permission
    And I save and close form
    And I should see "Role saved" flash message
    Then I go to Products/ Products
    And I click "Import file"
    And I should not see "Related Products"
    And I click "Cancel"
    And I go to System/User Management/Roles
    And click edit "Administrator" in grid
    And I check "[Related Products] Edit Related Products" entity permission
    And I save and close form
    And I should see "Role saved" flash message

  Scenario: Import when feature is disabled
    Given I go to Products/ Products
    And I click "Import file"
    And I should see "Related Products"
    And I click "Cancel"
    And I go to System/Configuration
    And follow "Commerce/Catalog/Related Items" on configuration sidebar
    When fill "RelatedProductsConfig" with:
      | Enable Related Products Use Default | false |
      | Enable Related Products             | false |
    And click "Save settings"
    And I should see "Configuration saved" flash message
    Then I go to Products/ Products
    And I click "Import file"
    And I should not see "Related Products"
