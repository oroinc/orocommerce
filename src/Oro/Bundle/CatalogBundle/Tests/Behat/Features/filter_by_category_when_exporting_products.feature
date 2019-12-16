@ticket-BB-17876
@regression
@fixture-OroCatalogBundle:category_tree_with_products.yml

Feature: Filter by category when exporting products
  In order to export products by category
  As a back office user
  I want category filtering in the side bar to apply to product export

  Scenario: Export unfiltered products
    Given I login as administrator
    And I go to Products/ Products
    And I click "Export Products"
    Then Email should contains the following "Export performed successfully. 4 products were exported. Download" text
    And take the link from email and download the file from this link
    Then the downloaded file from email contains at least the following data:
      | sku   |
      | PSKU1 |
      | PSKU2 |
      | PSKU3 |
      | PSKU4 |

  Scenario: Export products filtered by category
    Given I click "Retail Supplies"
    And I click "Export Products"
    Then Email should contains the following "Export performed successfully. 1 products were exported. Download" text
    And take the link from email and download the file from this link
    Then the downloaded file from email contains at least the following data:
      | sku   |
      | PSKU2 |

  Scenario: Export products filtered by category including subcategories
    Given I check "Include SubCategories" element
    And I click "Export Products"
    Then Email should contains the following "Export performed successfully. 2 products were exported. Download" text
    And take the link from email and download the file from this link
    Then the downloaded file from email contains at least the following data:
      | sku   |
      | PSKU2 |
      | PSKU3 |

  Scenario: Export products filtered by category including subcategories including not categorized
    Given I check "Include Not Categorized Products" element
    And I click "Export Products"
    Then Email should contains the following "Export performed successfully. 3 products were exported. Download" text
    And take the link from email and download the file from this link
    Then the downloaded file from email contains at least the following data:
      | sku   |
      | PSKU2 |
      | PSKU3 |
      | PSKU4 |

  Scenario: Export products with grid filter
    # To reset all filters on product page
    Given I go to System/ Configuration
    And I go to Products/ Products
    And I filter Name as contains "Product"
    And I click "Export Products"
    Then Email should contains the following "Export performed successfully. 4 products were exported. Download" text
    And take the link from email and download the file from this link
    Then the downloaded file from email contains at least the following data:
      | sku   |
      | PSKU1 |
      | PSKU2 |
      | PSKU3 |
      | PSKU4 |

  Scenario: Export products filtered by category with grid filter
    Given I click "Retail Supplies"
    And I click "Export Products"
    Then Email should contains the following "Export performed successfully. 1 products were exported. Download" text
    And take the link from email and download the file from this link
    Then the downloaded file from email contains at least the following data:
      | sku   |
      | PSKU2 |

  Scenario: Export products filtered by category including subcategories with grid filter
    Given I check "Include SubCategories" element
    And I click "Export Products"
    Then Email should contains the following "Export performed successfully. 2 products were exported. Download" text
    And take the link from email and download the file from this link
    Then the downloaded file from email contains at least the following data:
      | sku   |
      | PSKU2 |
      | PSKU3 |

  Scenario: Export products filtered by category including subcategories including not categorized with grid filter
    Given I check "Include Not Categorized Products" element
    And I click "Export Products"
    Then Email should contains the following "Export performed successfully. 3 products were exported. Download" text
    And take the link from email and download the file from this link
    Then the downloaded file from email contains at least the following data:
      | sku   |
      | PSKU2 |
      | PSKU3 |
      | PSKU4 |

  Scenario: Export products filtered by category including subcategories including not categorized with grid filter for one product
    # To reset all filters on product page
    Given I go to System/ Configuration
    And I go to Products/ Products
    Given I check "Include SubCategories" element
    Given I check "Include Not Categorized Products" element
    And I expand "Retail Supplies" in tree
    And I click "Printers"
    And I filter SKU as contains "PSKU3"
    And I click on "ExportButtonDropdown"
    And I click "Export Filtered Products"
    Then Email should contains the following "Export performed successfully. 1 products were exported. Download" text
    And take the link from email and download the file from this link
    Then the downloaded file from email contains at least the following data:
      | sku   |
      | PSKU3 |
