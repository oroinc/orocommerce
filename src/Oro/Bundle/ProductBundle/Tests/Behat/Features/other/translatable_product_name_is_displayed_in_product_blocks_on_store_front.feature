@ticket-BB-12917
@waf-skip
@fixture-OroProductBundle:translatable_product.yml
Feature: Translatable product name is displayed in product blocks on store front
  In order to have products with translation for products blocks on the store front
  As an administrator
  I want to control the names of the products with different localizations

  Scenario: Feature Background
    Given I enable the existing localizations
    And I login as administrator
    And I go to Products / Products
    And I click Edit SKU1 in grid
    And I set Images with:
      | Main | Listing | Additional |
      | 1    | 1       | 1          |
    And I click on "Digital Asset Choose"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat1.jpg |
      | Title | cat1.jpg |
    And I click "Upload"
    And click on cat1.jpg in grid
    And I save and close form

  Scenario: Check the product names for Zulu localization
    Given I am on the homepage
    And I click "Localization Switcher"
    When I select "Zulu" localization
    Then should see the following products in the "New Arrivals Block":
      | SKU  | Product Name in Embedded Block |
      | SKU1 | Product1_ZU                    |
      | SKU2 | Product2_ZU                    |
      | SKU3 | Product3_ZU                    |
    And should see the following products in the "Featured Products Block":
      | SKU  | Product Name in Embedded Block |
      | SKU1 | Product1_ZU                    |
      | SKU2 | Product2_ZU                    |
      | SKU3 | Product3_ZU                    |
    And should see the following products in the "Top Selling Items Block":
      | SKU  | Product Name in Embedded Block |
      | SKU1 | Product1_ZU                    |
    When I open product with sku "SKU1" on the store frontend
    Then should see the following products in the "Related Products Block":
      | SKU  | Product Name in Embedded Block |
      | SKU1 | Product1_ZU                    |
      | SKU2 | Product2_ZU                    |
      | SKU3 | Product3_ZU                    |
    And should see the following products in the "Upsell Products Block":
      | SKU  | Product Name in Embedded Block |
      | SKU1 | Product1_ZU                    |
      | SKU2 | Product2_ZU                    |
      | SKU3 | Product3_ZU                    |
