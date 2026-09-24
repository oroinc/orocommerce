@regression
@ticket-BB-27833
@fixture-OroLocaleBundle:GermanLocalization.yml
@fixture-OroProductBundle:product_slug.yml

Feature: Localized product redirect for URL shared with another localization
  In order to keep browsing the store in my own language
  As a customer
  I want to be redirected to the new product URL of my localization when the slug is changed for it only

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Enable German localization
    Given I proceed as the Admin
    And login as administrator
    And go to System / Configuration
    When I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And fill form with:
      | Enabled Localizations | [English (United States), German_Loc] |
      | Default Localization  | English (United States)               |
    And submit form
    Then I should see "Configuration saved" flash message

  Scenario: Set the same product slug for the default and the German localizations
    Given I go to Products/ Products
    And click Edit "SKU1" in grid
    And click on "Product Form Slug Fallbacks"
    When I fill "ProductForm" with:
      | URL Slug                | oro-product |
      | Name German Use Default | false       |
      | Name German             | oro-product |
    And save and close form
    And check "Create 301 Redirect from old to new URLs"
    And click "Apply" in modal window
    Then I should see "Product has been saved" flash message

  Scenario: Check the product is available by the shared slug
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I select "German Localization" localization
    When I am on "/oro-product"
    Then I should see "Product1"
    And the url should match "/oro-product"

  Scenario: Change the product slug for the German localization only
    Given I proceed as the Admin
    And I go to Products/ Products
    And click Edit "SKU1" in grid
    And click on "Product Form Slug Fallbacks"
    When I fill "ProductForm" with:
      | Name German | oro-product-de |
    And save and close form
    And check "Create 301 Redirect from old to new URLs"
    And click "Apply" in modal window
    Then I should see "Product has been saved" flash message

  Scenario: Check the customer is redirected to the slug of their own localization
    Given I proceed as the Buyer
    When I am on "/oro-product"
    Then the url should match "/oro-product-de"
    And I should see "Product1"

  Scenario: Check the slug of another localization is still served without a redirect
    Given I select "English (United States)" localization
    When I am on "/oro-product"
    Then the url should match "/oro-product"
    And I should see "Product1"
