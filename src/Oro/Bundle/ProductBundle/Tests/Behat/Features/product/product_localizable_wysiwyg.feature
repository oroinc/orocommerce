@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroLocaleBundle:DutchLocalization.yml
@fixture-OroLocaleBundle:FrenchLocalization.yml
@ticket-17977
Feature: Product localizable wysiwyg
  In order to be able to modify product description in different localizations
  As an Administrator
  I need to be able to work with localizable wyziwyg fields

  Scenario: Feature Background
    Given I enable the existing localizations
    And sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Add default and custom localization for product description
    Given I proceed as the Admin
    And I login as administrator
    When I go to Products / Products
    And I click "Create Product"
    And I click "Continue"
    Given fill "ProductForm" with:
      | SKU         | Test123                                  |
      | Name        | Test Product                             |
      | Status      | Enable                                   |
      | Description | Default localization product description |
    And click "Description"
    And press "Dutch" in "Description" section
    And fill "ProductForm" with:
      | Description Localization 2 fallback selector | Custom |
    And press "French" in "Description" section
    And fill "ProductForm" with:
      | Description Localization 3 fallback selector | Custom                                  |
      | Description Localization 3                   | French localization product description |
    And save form
    Then I should see "Product has been saved" flash message

  Scenario: Check product on the storefront for English localization
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open product with sku "Test123" on the store frontend
    Then I should see "Default localization product description"

  Scenario: Check product on the storefront for Dutch localization (custom pre-defined value from default value)
    Given I click "Localization Switcher"
    When I select "Dutch" localization
    Then I should see "Default localization product description"

  Scenario: Check product on the storefront for French localization (custom value)
    Given I click "Localization Switcher"
    When I select "French" localization
    Then I should see "French localization product description"

  Scenario: Add default and custom localization for product description
    Given I proceed as the Admin
    And click "Description"
    And press "Dutch" in "Description" section
    And fill "ProductForm" with:
      | Description Localization 2 fallback selector | Default Value |
    And press "French" in "Description" section
    And fill "ProductForm" with:
      | Description Localization 3 fallback selector | English (United States) [Parent Localization] |
    And save form
    Then I should see "Product has been saved" flash message

  Scenario: Check product on the storefront for Dutch localization (default value)
    Given I proceed as the Buyer
    And I click "Localization Switcher"
    When I select "Dutch" localization
    Then I should see "Default localization product description"

  Scenario: Check product on the storefront for French localization (parent localization)
    Given I click "Localization Switcher"
    When I select "French" localization
    Then I should see "Default localization product description"
