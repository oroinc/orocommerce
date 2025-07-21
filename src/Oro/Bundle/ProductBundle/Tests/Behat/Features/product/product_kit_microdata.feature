@ticket-BB-21285
@ticket-BB-22776
@fixture-OroProductBundle:product_kit_microdata.yml
Feature: Product Kit microdata

  Scenario: Feature Background
    Given sessions active:
      | Buyer | first_session |
      | Admin | second_session |
    And I enable configuration options:
      | oro_product.microdata_without_prices_disabled |
    And I set configuration property "oro_product.related_products_min_items" to "1"
    And I set configuration property "oro_product.upsell_products_min_items" to "1"
    And I set configuration property "oro_product.schema_org_description_field" to "oro_product_full_description"
    And I proceed as the Admin
    And I login as administrator

    Given go to Products/ Products
    And I click Edit "productkit1" in grid
    When I click "Select related products"
    Then I select following records in "SelectRelatedProductsGrid" grid:
      | relatedproduct01 |
      | relatedproduct02 |
      | relatedproduct03 |
    And I click "Select products"
    And I click "Up-sell Products"
    And I click "Select up-sell products"
    And I select following records in "SelectUpsellProductsGrid" grid:
      | upsellproduct01 |
      | upsellproduct02 |
      | upsellproduct03 |
    And I click "Select products"
    And I save form

    Given go to Products/ Products
    And I click Edit "simpleproduct01" in grid
    When I click "Select related products"
    Then I select following records in "SelectRelatedProductsGrid" grid:
      | productkit1      |
      | relatedproduct01 |
      | relatedproduct02 |
      | relatedproduct03 |
    And I click "Select products"
    And I click "Up-sell Products"
    And I click "Select up-sell products"
    And I select following records in "SelectUpsellProductsGrid" grid:
      | productkit1     |
      | upsellproduct01 |
      | upsellproduct02 |
      | upsellproduct03 |
    And I click "Select products"
    And I save form

  Scenario: Check microdata definition with switched ON protection for products microdata without prices
    Given I proceed as the Buyer
    And I am on the homepage

    When I type "simpleproduct01" in "search"
    And I click "Search Button"
    Then "simpleproduct01" product in "Product Frontend Grid" should contains microdata:
      | Product Type Microdata Declaration  |
      | Product Brand Microdata Declaration |
      | Product Price Microdata Declaration |
      | SchemaOrg SKU                       |
      | SchemaOrg Name                      |
      | SchemaOrg Description               |
      | SchemaOrg Image                     |
      | SchemaOrg Brand Name                |
      | SchemaOrg Price Currency            |
      | SchemaOrg Price                     |
    And "simpleproduct01" product in "Product Frontend Grid" should contains "SchemaOrg Description" with attributes:
      | content | Simple Product Description1 |
    And "simpleproduct01" product in "Product Frontend Grid" should contains "SchemaOrg Brand Name" with attributes:
      | content | ACME Default |
    And "simpleproduct01" product in "Product Frontend Grid" should contains microdata elements with text:
      | SchemaOrg SKU            | simpleproduct01       |
      | SchemaOrg Price Currency | USD                   |
      | SchemaOrg Price          | 31.00                 |

    When click "View Details" for "simpleproduct01" product
    Then "Product Details" should contains microdata:
      | Product Type Microdata Declaration  |
      | Product Brand Microdata Declaration |
      | SchemaOrg SKU                       |
      | SchemaOrg Name                      |
      | SchemaOrg Description               |
      | SchemaOrg Image                     |
      | SchemaOrg Brand Name                |
    And "Product Details" should not contains microdata:
      | Product Price Microdata Declaration |
      | SchemaOrg Price Currency            |
      | SchemaOrg Price                     |
    And "Product Details" should contains "SchemaOrg Description" with attributes:
      | content | Simple Product Description1 |
    And "Product Details" should contains microdata elements with text:
      | SchemaOrg SKU            | simpleproduct01       |
      | SchemaOrg Name           | ProductTheKit Child 1 |
      | SchemaOrg Brand Name     | ACME Default          |

    And "productkit1" product in "Related Products Block" should not contains microdata:
      | Product Type Microdata Declaration  |
      | Product Price Microdata Declaration |
      | SchemaOrg Description               |
      | SchemaOrg Price Currency            |
      | SchemaOrg Price                     |
# Should be fixed at BB-22776
#      | Product Brand Microdata Declaration |
#      | SchemaOrg Brand Name                |
#      | SchemaOrg SKU                       |
#      | SchemaOrg Name                      |
#      | SchemaOrg Image                     |

    And "productkit1" product in "Upsell Products Block" should not contains microdata:
      | Product Type Microdata Declaration  |
      | Product Price Microdata Declaration |
      | SchemaOrg Description               |
      | SchemaOrg Price Currency            |
      | SchemaOrg Price                     |
# Should be fixed at BB-22776
#      | Product Brand Microdata Declaration |
#      | SchemaOrg Brand Name                |
#      | SchemaOrg SKU                       |
#      | SchemaOrg Name                      |
#      | SchemaOrg Image                     |

    When I type "productkit1" in "search"
    And I click "Search Button"
    Then "productkit1" product in "Product Frontend Grid" should not contains microdata:
      | Product Type Microdata Declaration  |
      | Product Price Microdata Declaration |
      | SchemaOrg Description               |
      | SchemaOrg Price Currency            |
      | SchemaOrg Price                     |
# Should be fixed at BB-22776
#      | SchemaOrg SKU                       |
#      | Product Brand Microdata Declaration |
#      | SchemaOrg Name                      |
#      | SchemaOrg Image                     |
#      | SchemaOrg Brand Name                |

    When click "View Details" for "productkit1" product
    Then "Product Details" should not contains microdata:
      | Product Type Microdata Declaration  |
      | Product Price Microdata Declaration |
      | SchemaOrg Description               |
      | SchemaOrg Price Currency            |
      | SchemaOrg Price                     |
# Should be fixed at BB-22776
#      | Product Brand Microdata Declaration |
#      | SchemaOrg Brand Name                |
#      | SchemaOrg SKU                       |
#      | SchemaOrg Name                      |
#      | SchemaOrg Image                     |

    And "relatedproduct01" product in "Related Products Block" should contains microdata:
      | Product Type Microdata Declaration  |
      | Product Price Microdata Declaration |
      | Product Brand Microdata Declaration |
      | SchemaOrg SKU                       |
      | SchemaOrg Name                      |
      | SchemaOrg Description               |
      | SchemaOrg Image                     |
      | SchemaOrg Brand Name                |
      | SchemaOrg Price Currency            |
      | SchemaOrg Price                     |
    And "relatedproduct01" product in "Related Products Block" should contains "SchemaOrg Description" with attributes:
      | content | Related Product Description1 |
    And "relatedproduct01" product in "Related Products Block" should contains "SchemaOrg Brand Name" with attributes:
      | content | ACME Related |
    And "relatedproduct01" product in "Related Products Block" should contains microdata elements with text:
      | SchemaOrg SKU            | relatedproduct01 |
      | SchemaOrg Price Currency | USD              |
      | SchemaOrg Price          | 41.00            |

    And "upsellproduct01" product in "Upsell Products Block" should contains microdata:
      | Product Type Microdata Declaration  |
      | Product Price Microdata Declaration |
      | Product Brand Microdata Declaration |
      | SchemaOrg SKU                       |
      | SchemaOrg Name                      |
      | SchemaOrg Description               |
      | SchemaOrg Image                     |
      | SchemaOrg Brand Name                |
      | SchemaOrg Price Currency            |
      | SchemaOrg Price                     |
    And "upsellproduct01" product in "Upsell Products Block" should contains "SchemaOrg Description" with attributes:
      | content | Upsell Product Description1 |
    And "upsellproduct01" product in "Upsell Products Block" should contains "SchemaOrg Brand Name" with attributes:
      | content | ACME Upsell |
    And "upsellproduct01" product in "Upsell Products Block" should contains microdata elements with text:
      | SchemaOrg SKU            | upsellproduct01 |
      | SchemaOrg Price Currency | USD             |
      | SchemaOrg Price          | 51.00           |

  Scenario: Check microdata definition with switched OFF protection for products microdata without prices
    Given I disable configuration options:
      | oro_product.microdata_without_prices_disabled |
    When I reload the page
    Then "Product Details" should contains microdata:
      | Product Type Microdata Declaration  |
      | Product Brand Microdata Declaration |
      | SchemaOrg Description               |
      | SchemaOrg SKU                       |
      | SchemaOrg Name                      |
      | SchemaOrg Image                     |
      | SchemaOrg Brand Name                |
    And "Product Details" should not contains microdata:
      | Product Price Microdata Declaration |
      | SchemaOrg Price Currency            |
      | SchemaOrg Price                     |
    And "Product Details" should contains "SchemaOrg Description" with attributes:
      | content | Product Kit Description |
    And "Product Details" should contains microdata elements with text:
      | SchemaOrg SKU        | productkit1          |
      | SchemaOrg Name       | ProductTheKit Parent |
      | SchemaOrg Brand Name | ACME Default         |

    When I type "productkit1" in "search"
    And I click "Search Button"
    Then "productkit1" product in "Product Frontend Grid" should contains microdata:
      | Product Type Microdata Declaration  |
      | Product Brand Microdata Declaration |
      | SchemaOrg SKU                       |
      | SchemaOrg Name                      |
      | SchemaOrg Image                     |
      | SchemaOrg Description               |
      | SchemaOrg Brand Name                |
    And "productkit1" product in "Product Frontend Grid" should not contains microdata:
      | Product Price Microdata Declaration |
      | SchemaOrg Price Currency            |
      | SchemaOrg Price                     |
    And "productkit1" product in "Product Frontend Grid" should contains "SchemaOrg Description" with attributes:
      | content | Product Kit Description |
    And "productkit1" product in "Product Frontend Grid" should contains "SchemaOrg Brand Name" with attributes:
      | content | ACME Default |
    And "productkit1" product in "Product Frontend Grid" should contains microdata elements with text:
      | SchemaOrg SKU        | productkit1          |
