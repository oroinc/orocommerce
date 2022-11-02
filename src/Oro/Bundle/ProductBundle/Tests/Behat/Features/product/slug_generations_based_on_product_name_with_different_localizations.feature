@regression
@ticket-BB-20552
@fixture-OroLocaleBundle:GermanLocalization.yml

Feature: Slug generations based on product name with different localizations
  In order to provide users with readable product URLs in different locations
  As administrator
  I need to be able to change the product name and get the generated Slug URL regardless of the product name locale

  Scenario: Create product with slug in different localizations
    Given I login as administrator
    And go to Products/ Products
    When I click "Create Product"
    And click "Continue"
    And click on "Product Form Slug Fallbacks"
    And fill "ProductForm" with:
      | SKU                     | oro_product    |
      | Name                    | oro_product    |
      | Name German Use Default | false          |
      | Name German             | oro_product_de |
    And save and close form
    Then I should see "Product has been saved" flash message
    When I reload the page
    Then I should see text matching "oro_product"
    And should see text matching "oro_product_de"
