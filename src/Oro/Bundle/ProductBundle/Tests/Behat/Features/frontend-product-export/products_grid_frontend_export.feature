@regression
@feature-BB-19874
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroProductBundle:products_grid_frontend.yml

Feature: Products grid frontend export
  In order to ensure frontend products grid export works correctly
  As a buyer
  I want to check frontend product listing export is working as well as filtered product listing.

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Buyer

  Scenario: Checks that export button is not displayed when export is not enabled
    Given I am on the homepage
    When I click "Search Button"
    Then I should not see an "Frontend Product Grid Export Button" element

  Scenario: Enables export product listing in system configuration
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Product/Customer Settings" on configuration sidebar
    And uncheck "Use default" for "Enable Product Grid Export" field
    And I fill "System Config Form" with:
      | Enable Product Grid Export | true |
    When I save form
    Then I should see "Configuration saved" flash message

  Scenario: Checks that export button is not available for guests users
    Given I proceed as the Buyer
    When I click "Search Button"
    Then I should not see an "Frontend Product Grid Export Button" element
    When I click "Category 1"
    Then I should not see an "Frontend Product Grid Export Button" element
    
  Scenario: Checks that export button is not visible when no results
    Given I login as AmandaRCole@example.org buyer
    When I type "Unsearchable" in "search"
    And I click "Search Button"
    Then I should not see an "Frontend Product Grid Export Button" element

  Scenario: Checks that export button is visible and is working
    Given I am on the homepage
    When I click "Search Button"
    And I set range filter "Price" as min value "5" and max value "7" use "each" unit
    Then I should see "PSKU5"
    And I should see "PSKU7"
    And I should not see "PSKU4"
    And I should see an "Frontend Product Grid Export Button" element

  Scenario: Check product data exports correctly
    When I click "Frontend Product Grid Export Button"
    Then I should see "The product data export has started. You will receive download instructions by email once the export is finished." flash message
    And email with Subject "Products export result is ready" containing the following was sent:
      | Body | Your products data export has been finished. Download Results |
    And take the link from email and download the file from this link
    And the downloaded file from email contains at least the following data:
      | name      | sku   | inventory_status.id |
      | Product 5 | PSKU5 | in_stock            |
      | Product 7 | PSKU7 | out_of_stock        |

  Scenario: Check category products export works as expected
    When I click "Category 1"
    And I click "Frontend Product Grid Export Button"
    Then I should see "The product data export has started. You will receive download instructions by email once the export is finished." flash message
    And email with Subject "Products export result is ready" containing the following was sent:
      | Body | Your products data export has been finished. Download Results |
    And take the link from email and download the file from this link
    And the downloaded file from email contains at least the following data:
      | name      | sku   | inventory_status.id |
      | Product 1 | PSKU1 | in_stock            |
      | Product 2 | PSKU2 | in_stock            |
      | Product 3 | PSKU3 | in_stock            |
      | Product 4 | PSKU4 | in_stock            |
      | Product 5 | PSKU5 | in_stock            |

  Scenario: Check filtered product data exported correctly
    When I set range filter "Price" as min value "3" and max value "4" use "each" unit
    Then I should see "PSKU3"
    And I should see "PSKU4"
    When I click "Frontend Product Grid Export Button"
    Then I should see "The product data export has started. You will receive download instructions by email once the export is finished." flash message
    And email with Subject "Products export result is ready" containing the following was sent:
      | Body | Your products data export has been finished. Download Results |
    And take the link from email and download the file from this link
    And the downloaded file from email contains at least the following data:
      | name      | sku   | inventory_status.id |
      | Product 3 | PSKU3 | in_stock            |
      | Product 4 | PSKU4 | in_stock            |

  Scenario: Check product search results exported correctly
    Given I go to homepage
    And I type "PSKU2" in "search"
    When click "Search Button"
    Then number of records in "Product Frontend Grid" should be 2
    And I click "Frontend Product Grid Export Button"
    And I should see "The product data export has started. You will receive download instructions by email once the export is finished." flash message
    And email with Subject "Products export result is ready" containing the following was sent:
      | Body | Your products data export has been finished. Download Results |
    And take the link from email and download the file from this link
    And the downloaded file from email contains at least the following data:
      | name       | sku    | inventory_status.id |
      | Product 2  | PSKU2  | in_stock            |
      | Product 20 | PSKU20 | in_stock            |
