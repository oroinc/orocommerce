@elasticsearch
@feature-BB-21129
@ticket-BB-23173
@ticket-BB-23174
@fixture-OroCustomerBundle:CustomerUserFixture.yml
@fixture-OroProductBundle:ConfigurableAttributeFamily.yml
@fixture-OroProductBundle:ProductKitsExportFixture.yml

Feature: Product Kit search
  In order to be able to search for product kits in the back-office or storefront
  As a user of the back-office or a buyer
  I search for products through the main product search functionality

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Check the search of product kits in the back-office
    Given I proceed as the Admin
    And I login as administrator
    And I click "Search"
    When I type "PSKU_KIT1" in "search"
    Then I should see 1 search suggestion
    When I follow "Product Kit 1"
    Then I should be on Product View page
    And I should see "PSKU_KIT1 - Product Kit 1"

  Scenario: Check the search of product kits by kit item label in the back-office
    Given I click "Search"
    When I type "Base Unit" in "search"
    Then I should see 1 search suggestion
    When I click "Search Submit"
    Then I should be on Search Result page
    And I should see following search entity types:
      | Type     | N | isSelected |
      | All      | 1 | yes        |
      | Products | 1 |            |
    And number of records should be 1
    And I should see following search results:
      | Title                     | Type    |
      | PSKU_KIT1 - Product Kit 1 | Product |

  Scenario: Check the search of product kits by simple product related to the product kit in the back-office
    Given I click "Search"
    When I type "PSKU1" in "search"
    Then I should see 2 search suggestion
    When I click "Search Submit"
    Then I should be on Search Result page
    And I should see following search entity types:
      | Type     | N | isSelected |
      | All      | 2 | yes        |
      | Products | 2 |            |
    And number of records should be 2
    And I should see following search results:
      | Title                     | Type    |
      | PSKU1 - Product 1         | Product |
      | PSKU_KIT1 - Product Kit 1 | Product |

  Scenario: Check the search of product kits on the storefront
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I go to the homepage
    When I type "PSKU_KIT1" in "search"
    Then I should see an "Search Autocomplete" element
    And I should see "PSKU_KIT1" in the "Search Autocomplete Product" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product Kit 1" inside "Search Autocomplete" element
    When I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 1
    And PSKU_KIT1 must be first record in "Product Frontend Grid"

  Scenario: Check the search of product kits by kit item label on the storefront
    Given I go to the homepage
    When I type "Base Unit" in "search"
    Then I should see an "Search Autocomplete" element
    And I should see "PSKU_KIT1" in the "Search Autocomplete Product" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product Kit 1" inside "Search Autocomplete" element
    When I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 1
    And PSKU_KIT1 must be first record in "Product Frontend Grid"

  Scenario: Check the search of product kits by simple product related to the product kit on the storefront
    Given I go to the homepage
    When I type "PSKU1" in "search"
    Then I should see an "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product 1" inside "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product Kit 1" inside "Search Autocomplete" element
    When I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 2
    And PSKU1 must be first record in "Product Frontend Grid"
    And PSKU_KIT1 must be second record in "Product Frontend Grid"

  Scenario: Change kit item label
    Given I proceed as the Admin
    When I go to Products/Products
    And click edit "PSKU_KIT1" in grid
    And I fill "ProductKitForm" with:
      | Kit Item 1 Label | Base Unit Edited |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check the search of product kits after changing the kit item label in the back-office
    When I click "Search"
    And I type "Edited" in "search"
    Then I should see 1 search suggestion
    When I click "Search Submit"
    Then I should be on Search Result page
    And number of records should be 1
    And I should see following search results:
      | Title                     | Type    |
      | PSKU_KIT1 - Product Kit 1 | Product |

  Scenario: Check the search of product kits after changing the kit item label on the storefront
    Given I proceed as the Buyer
    When I type "Edited" in "search"
    Then I should see an "Search Autocomplete" element
    And I should see "PSKU_KIT1" in the "Search Autocomplete Product" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product Kit 1" inside "Search Autocomplete" element
    When I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 1
    And PSKU_KIT1 must be first record in "Product Frontend Grid"

  Scenario: Remove kit item product in the back-office
    Given I proceed as the Admin
    When I go to Products/Products
    And click edit "PSKU_KIT1" in grid
    And I click "Kit Item 1 Remove Button"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check the search of product kits after removing a kit item product in the back-office
    When I click "Search"
    And I type "Edited" in "search"
    Then I should see "No results found"

  Scenario: Check the search of product kits after removing a kit item product on the storefront
    Given I proceed as the Buyer
    When I type "Edited" in "search"
    Then I should not see an "Search Autocomplete Item" element
    And I should not see an "Search Autocomplete Submit" element
    And I should see "No products were found to match your search" in the "Search Autocomplete No Found" element
    When I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 0
    And I should see "There are no products"

  Scenario: Add new kit item product in the back-office
    Given I proceed as the Admin
    When I go to Products/Products
    And click edit "PSKU_KIT1" in grid
    And I click "Add Kit Item"
    And I fill "ProductKitForm" with:
      | Kit Item 3 Label        | Flash Drives |
      | Kit Item 3 Product Unit | item         |
    And I click "Add Product" in "Product Kit Item 3" element
    And I click on PSKU2 in grid "KitItemProductsAddGrid"
    And I click "Kit Item 3 Toggler"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check the search of product kits after adding a new kit item product in the back-office
    When I click "Search"
    And I type "Flash Drives" in "search"
    Then I should see 1 search suggestion
    When I click "Search Submit"
    Then I should be on Search Result page
    And number of records should be 1
    And I should see following search results:
      | Title                     | Type    |
      | PSKU_KIT1 - Product Kit 1 | Product |

    When I click "Search"
    And I type "PSKU2" in "search"
    Then I should see 2 search suggestion
    When I click "Search Submit"
    Then I should be on Search Result page
    And number of records should be 2
    And I should see following search results:
      | Title                     | Type    |
      | PSKU2 - Product 2         | Product |
      | PSKU_KIT1 - Product Kit 1 | Product |

  Scenario: Check the search of product kits after adding a new kit item product on the storefront
    Given I proceed as the Buyer
    When I type "Flash Drives" in "search"
    Then I should see an "Search Autocomplete" element
    And I should see "PSKU_KIT1" in the "Search Autocomplete Product" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product Kit 1" inside "Search Autocomplete" element
    When I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 1
    And PSKU_KIT1 must be first record in "Product Frontend Grid"

    When I type "PSKU2" in "search"
    Then I should see an "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product 2" inside "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product Kit 1" inside "Search Autocomplete" element
    When I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 2
    And PSKU2 must be first record in "Product Frontend Grid"
    And PSKU_KIT1 must be second record in "Product Frontend Grid"

  Scenario: Change simple product related to product kit in the back-office
    Given I proceed as the Admin
    When I go to Products/Products
    And click edit "PSKU2" in grid
    And I fill "ProductForm" with:
      | Name | Handheld Flashlight |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check the search of product kits after changing simple product related to product kit in the back-office
    When I click "Search"
    And I type "Handheld Flashlight" in "search"
    Then I should see 2 search suggestion
    When I click "Search Submit"
    Then I should be on Search Result page
    And number of records should be 2
    And I should see following search results:
      | Title                       | Type    |
      | PSKU2 - Handheld Flashlight | Product |
      | PSKU_KIT1 - Product Kit 1   | Product |

  Scenario: Check the search of product kits after changing simple product related to product kit on the storefront
    Given I proceed as the Buyer
    When I type "Handheld Flashlight" in "search"
    Then I should see an "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Handheld Flashlight" inside "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product Kit 1" inside "Search Autocomplete" element
    When I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 2
    And PSKU2 must be first record in "Product Frontend Grid"
    And PSKU_KIT1 must be second record in "Product Frontend Grid"

  Scenario: Enable Fuzzy Search
    Given I proceed as the Admin
    When I go to System/ Configuration
    And follow "Commerce/Search/Fuzzy Search" on configuration sidebar
    And uncheck "Use default" for "Enable Fuzzy Search" field
    And check "Enable Fuzzy Search"
    And save form
    Then I should see "Configuration saved" flash message

  Scenario: Check the search of product kits with enabled fuzzy searching on the storefront
    And I proceed as the Buyer
    When I type "scenner" in "search"
    Then I should see an "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product Kit 1" inside "Search Autocomplete" element
    When I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 1
    And PSKU_KIT1 must be first record in "Product Frontend Grid"
    When I type "PSKUU1" in "search"
    Then I should see an "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product 1" inside "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product Kit 1" inside "Search Autocomplete" element
    When I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 2
    And PSKU1 must be first record in "Product Frontend Grid"
    And PSKU_KIT1 must be second record in "Product Frontend Grid"

  Scenario: Enable Search Synonyms functionality
    Given I proceed as the Admin
    When I go to System/ Configuration
    And follow "Commerce/Search/Search Synonyms" on configuration sidebar
    And uncheck "Use default" for "Enable Search Synonyms" field
    And check "Enable Search Synonyms"
    And save form
    Then I should see "Configuration saved" flash message
    When I go to Marketing/ Search/ Search Synonyms
    And I click "Create Search Synonym"
    And I fill form with:
      | Websites | Default                   |
      | Synonyms | scanner, optical scanning |
    And save and close form
    Then I should see "Search Synonym has been saved" flash message

  Scenario: Check the search of product kits with enabled synonyms searching on the storefront
    And I proceed as the Buyer
    When I type "optical scanning" in "search"
    Then I should see an "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product Kit 1" inside "Search Autocomplete" element
    When I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 1
    And PSKU_KIT1 must be first record in "Product Frontend Grid"
