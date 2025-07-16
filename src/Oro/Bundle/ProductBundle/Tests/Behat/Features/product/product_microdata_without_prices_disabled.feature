@regression
@ticket-BB-21285
@ticket-BB-22581
@fixture-OroProductBundle:products.yml
Feature: Product microdata without prices disabled
  In order to prevent products being excluded from search results, due to prices are missing in the product microdata definition
  As an administrator
  I want to be able to disable product microdata definition when prices are missing

  Scenario: Feature Background
    Given sessions active:
      | Buyer | first_session |
    And I enable configuration options:
      | oro_product.microdata_without_prices_disabled |

  Scenario: Check microdata with switched ON protection for products with prices
    Given I proceed as the Buyer
    And I am on the homepage
    When I type "PSKU1" in "search"
    And I click "Search Button"
    Then "PSKU1" product in "Product Frontend Grid" should contains microdata:
      | Product Type Microdata Declaration  |
      | Product Price Microdata Declaration |
      | SchemaOrg Description               |
      | SchemaOrg Price Currency            |
      | SchemaOrg Price                     |
    And "PSKU1" product in "Product Frontend Grid" should contains "SchemaOrg Description" with attributes:
      | content | Product Description1 |
    And "PSKU1" product in "Product Frontend Grid" should contains microdata elements with text:
      | SchemaOrg Price Currency | USD   |
      | SchemaOrg Price          | 10.00 |
    # Because product without brand association, just ensure that element not rendered
    Then "PSKU1" product in "Product Frontend Grid" should not contains microdata:
      | Product Brand Microdata Declaration |
      | SchemaOrg Brand Name                |

    When click "View Details" for "PSKU1" product
    Then "Product Details" should contains microdata:
      | Product Type Microdata Declaration |
      | SchemaOrg Description              |

    And "Product Details" should not contains microdata:
      | Product Price Microdata Declaration |
      | SchemaOrg Price Currency            |
      | SchemaOrg Price                     |
    And "Product Details" should contains "SchemaOrg Description" with attributes:
      | content | Product Description1 |

    # Because product without brand association, just ensure that element not rendered
    And "Product Details" should not contains microdata:
      | Product Brand Microdata Declaration |
      | SchemaOrg Brand Name                |

  Scenario: Check no microdata with switched off Oro Pricing feature
    Given I disable configuration options:
      | oro_pricing.feature_enabled |
    And I am on the homepage
    When I type "PSKU1" in "search"
    And I click "Search Button"
    Then "PSKU1" product in "Product Frontend Grid" should not contains microdata:
      | Product Type Microdata Declaration  |
      | Product Brand Microdata Declaration |
      | Product Price Microdata Declaration |
      | SchemaOrg Description               |
      | SchemaOrg Brand Name                |
      | SchemaOrg Price Currency            |
      | SchemaOrg Price                     |

    When click "View Details" for "PSKU1" product
    Then "Product Details" should not contains microdata:
      | Product Type Microdata Declaration  |
      | Product Brand Microdata Declaration |
      | SchemaOrg Brand Name                |
      | Product Price Microdata Declaration |
      | SchemaOrg Description               |
      | SchemaOrg Price Currency            |
      | SchemaOrg Price                     |

  Scenario: Check microdata with switched OFF protection for products with prices and Oro Pricing feature is OFF
    Given I disable configuration options:
      | oro_product.microdata_without_prices_disabled |
    And I am on the homepage
    When I type "PSKU1" in "search"
    And I click "Search Button"
    Then "PSKU1" product in "Product Frontend Grid" should contains microdata:
      | Product Type Microdata Declaration |
      | SchemaOrg Description              |
    And "PSKU1" product in "Product Frontend Grid" should contains "SchemaOrg Description" with attributes:
      | content | Product Description1 |
    And "PSKU1" product in "Product Frontend Grid" should not contains microdata:
      | Product Price Microdata Declaration |
      | SchemaOrg Price Currency            |
      | SchemaOrg Price                     |
      | Product Brand Microdata Declaration |
      | SchemaOrg Brand Name                |

    When click "View Details" for "PSKU1" product
    Then "Product Details" should contains microdata:
      | Product Type Microdata Declaration |
      | SchemaOrg Description              |
    And "Product Details" should contains "SchemaOrg Description" with attributes:
      | content | Product Description1 |
    And "Product Details" should not contains microdata:
      | Product Price Microdata Declaration |
      | SchemaOrg Price Currency            |
      | SchemaOrg Price                     |
      | Product Brand Microdata Declaration |
      | SchemaOrg Brand Name                |
