@regression
@ticket-BB-12736
@fixture-OroProductBundle:ProductBrandFilterFixture.yml

Feature: Brand Filter
  In order to have ability to filter product by brands
  As a Buyer
  I want to have the multi-select brand filter on frontend for the product grid

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Enable custom localization
    Given I proceed as the Admin
    And I login as administrator
    And I go to System / Websites
    When I click "Configuration" on row "Default" in grid
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use System" for "Default Localization" field
    And fill form with:
      | Enabled Localizations | [English, Localization1] |
      | Default Localization  | English                  |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Update translations cache
    Given I go to System/Localization/Translations
    When I click "Update Cache"
    Then I should see "Translation Cache has been updated" flash message

  Scenario: Add name of Brands for German Localization
    Given I go to Products / Product Brands
    When I click Edit ACME in grid
    And I click on "Brand Form Name Fallbacks"
    And fill "Brand Form" with:
      | Name Second Use Default | false  |
      | Name Second             | GIPFEL |
    And I save and close form
    Then I should see "Brand has been saved" flash message

    When I go to Products / Product Brands
    And I click Edit Default Ltd. in grid
    And I click on "Brand Form Name Fallbacks"
    And fill "Brand Form" with:
      | Name Second Use Default | false         |
      | Name Second             | Standard Ltd. |
    And I save and close form
    Then I should see "Brand has been saved" flash message

  Scenario: Enable Filter by Brand
    Given I proceed as the Admin
    And I go to Products/Product Attributes
    And I click edit "brand" in grid
    When I fill form with:
      | Filterable | Yes |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Filter by Brand in default localization
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "NewCategory"
    When I check "ACME" in Brand filter in frontend product grid
    Then I should see "PSKU2" product
    And I should not see "PSKU1" product

    When I check "Default Ltd." in Brand filter in frontend product grid
    Then I should see "PSKU1" product
    And I should see "PSKU2" product

# Skipped due to problems with /js/translation/lang1.js urls in js
#
#  Scenario: Filter by Brand in custom localization
#    Given I click on "Localization dropdown"
#    And I click "Localization 1"
#    # click second time to uncheck selected filter value
#    When I check "GIPFEL" in Brand filter in frontend product grid
#    Then I should not see "PSKU2" product
#    And I should see "PSKU1" product
#
#    # click second time to uncheck selected filter value
#    When I check "Standard Ltd." in Brand filter in frontend product grid
#    Then I should see "PSKU1" product
#    And I should see "PSKU2" product
