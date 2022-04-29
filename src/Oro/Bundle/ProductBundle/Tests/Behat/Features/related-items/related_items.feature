@regression
@fixture-OroProductBundle:related_items_products.yml
@fixture-OroProductBundle:related_items_system_users.yml

Feature: Related items

  Scenario: Check if related items label changes if only one relation is active
    Given I login as administrator
    And go to Products/ Products
    And I click Edit "PSKU1" in grid
    Then I should see that "Product navbar" contains "Related Items"
    And go to System/ Configuration
    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
    And I fill "UpsellProductsConfig" with:
      | Enable Up-sell Products Use Default | false |
      | Enable Up-sell Products             | false |
    And I click "Save settings"
    And go to Products/ Products
    And I click Edit "PSKU1" in grid
    Then I should see that "Product navbar" contains "Related Products"
    And go to System/ Configuration
    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
    And I fill "UpsellProductsConfig" with:
      | Enable Up-sell Products Use Default | true |
      | Enable Up-sell Products             | true |
    And I fill "RelatedProductsConfig" with:
      | Enable Related Products Use Default | false |
      | Enable Related Products             | false |
    And I click "Save settings"
    And go to Products/ Products
    And I click Edit "PSKU1" in grid
    Then I should see that "Product navbar" contains "Up-sell Products"
