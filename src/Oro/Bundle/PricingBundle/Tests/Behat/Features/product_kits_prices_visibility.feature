@regression
@feature-BB-21125
@ticket-BB-22598
@fixture-OroPricingBundle:ProductKitPriceVisibilityFixture.yml

Feature: Product kits prices visibility

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator
    When go to System/ Configuration
    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
    And I fill "RelatedProductsConfig" with:
      | Enable Related Products Use Default | false |
      | Enable Related Products             | true  |
      | Maximum Items Use Default           | false |
      | Maximum Items                       | 4     |
      | Minimum Items Use Default           | false |
      | Minimum Items                       | 4     |
    And I fill "UpsellProductsConfig" with:
      | Enable Up-sell Products Use Default | false |
      | Enable Up-sell Products             | true  |
      | Maximum Items Use Default           | false |
      | Maximum Items                       | 4     |
      | Minimum Items Use Default           | false |
      | Minimum Items                       | 4     |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check storefront product listing blocks
    Given I proceed as the Buyer
    When I signed in as AmandaRCole@example.org on the store frontend
    And I am on homepage

    Then should see the following products in the "Featured Products Block":
      | SKU         |
      | productkit2 |
    And I should not see "Product Price Container" for "productkit2" product
    And should see the following products in the "Featured Products Block":
      | SKU             | Product Price Your | Product Price Listed |
      | secondproduct01 | $32.00 / item      | $32.00 / item        |
      | secondproduct02 | $32.00 / item      | $32.00 / item        |
      | secondproduct03 | $32.00 / item      | $32.00 / item        |

    And I should see the following products in the "New Arrivals Block":
      | SKU         |
      | productkit1 |
    And I should not see "Product Price Container" for "productkit1" product
    And I should see the following products in the "New Arrivals Block":
      | SKU             | Product Price Your | Product Price Listed |
      | simpleproduct01 | $31.00 / piece     | $31.00 / piece       |
      | simpleproduct02 | $31.00 / piece     | $31.00 / piece       |
      | simpleproduct03 | $31.00 / piece     | $31.00 / piece       |

  Scenario: Check product prices in category page
    And I should see "FEATURED CATEGORIES"
    And should see "3 items" for "NewCategory" category

    When I click "NewCategory" in hamburger menu
    Then I should not see "Product Price Container" for "productkit1" product
    And should not see "$11.00" for "productkit1" product

    And I should see "Product Price Container" for "simpleproduct01" product
    And should see "$31.00" for "simpleproduct01" product

    And I should see "Product Price Container" for "secondproduct01" product
    And should see "$32.00" for "secondproduct01" product

  Scenario: Check prices in search Autocomplete
    When I type "simpleproduct01" in "search"
    Then I should see an "Search Autocomplete" element
    And should see an "Search Autocomplete Product Price" element
    And I should see "$31.00" in the "Search Autocomplete Product" element

    When I type "productkit1" in "search"
    Then I should see an "Search Autocomplete" element
    And should not see an "Search Autocomplete Product Price" element
    And I should not see "$11.00" in the "Search Autocomplete Product" element

    When I type "ProductTheKit" in "search"
    And I click "Search Button"
    Then I should see "Product Price Container" for "simpleproduct03" product
    And should see "$31.00" for "simpleproduct03" product

    And I should not see "Product Price Container" for "productkit1" product
    And should not see "$11.00" for "productkit1" product

  Scenario: Check prices in product details page
    When click "View Details" for "productkit1" product
    Then I should see an "Configure and Add to Shopping List" element
    And I should not see an "Default Page Prices" element
    And I should see "Related Products"
    And I should see the following products in the "Related Products Block":
      | SKU         |
      | productkit2 |
    And I should not see "Product Price Container" for "productkit2" product
    And I should see the following products in the "Related Products Block":
      | SKU             | Product Price Your | Product Price Listed |
      | secondproduct01 | $32.00 / item      | $32.00 / item        |
      | secondproduct02 | $32.00 / item      | $32.00 / item        |
      | secondproduct03 | $32.00 / item      | $32.00 / item        |

  Scenario: Enable Flat pricing
    Given I proceed as the Admin
    And I run Symfony "oro:price-lists:switch-pricing-storage flat" command in "prod" environment
    And I run Symfony "oro:website-search:reindex" command in "prod" environment

  Scenario: Check flat prices in search Autocomplete
    Given I proceed as the Buyer
    And I am on homepage
    When I type "simpleproduct01" in "search"
    Then I should see an "Search Autocomplete" element
    And should see an "Search Autocomplete Product Price" element
    And I should see "$31.00" in the "Search Autocomplete Product" element

    When I type "productkit1" in "search"
    Then I should see an "Search Autocomplete" element
    And should not see an "Search Autocomplete Product Price" element
    And I should not see "$11.00" in the "Search Autocomplete Product" element

    When I type "ProductTheKit" in "search"
    And I click "Search Button"
    Then I should see "Product Price Container" for "simpleproduct03" product
    And should see "$31.00" for "simpleproduct03" product

    And I should not see "Product Price Container" for "productkit1" product
    And should not see "$11.00" for "productkit1" product
