@regression
@ticket-BB-14602
@fixture-OroUserBundle:manager.yml
@fixture-OroPricingBundle:PriceListsWithPrices.yml

Feature: Product prices ACL
  In order to have the ability to manage access to product prices
  As an Administrator
  I want to manage ACL by role for entity "Product Price"

  Scenario: Feature Background
    Given sessions active:
      | admin    |first_session |
      | manager  |second_session|

  Scenario: Check product prices at view page of Price List when VIEW forbidden
    Given I proceed as the manager
    And I login as "ethan" user
    And I go to Sales/ Price Lists

    And I click view First Price List in grid
    Then I should see following "Price list Product prices Grid" grid:
      | Product SKU | Product name | Quantity | Unit  | Value   | Currency | Type   |
      | PSKU1       | Product 1    | 5        | item  | 15.00 | USD      | Manual |
      | PSKU2       | Product 2    | 10       | piece | 30.00 | USD      | Manual |

    When I proceed as the admin
    And I login as administrator
    And I go to System/ User Management/ Roles
    And click edit "Sales Manager" in grid
    And I select following permissions:
      | Product Price | View:None   |
    # set default ACL on Product Entity for Sales Manager
    And I select following permissions:
      | Product | Edit:Global | Create:Global |
    And I save form

    And I proceed as the manager
    And I reload the page
    Then I should not see an "Price list Product prices Grid" element
    And I should see following buttons:
      | Add Product Price |

  Scenario: Check product prices at the grid, view and edit product page when VIEW forbidden
    Given I go to Products/ Products
    Then I shouldn't see "Price (USD)" column in grid
    When click view "PSKU1" in grid
    Then I should not see an "ProductPricesGrid" element
    When I click "Edit"
    Then I should not see an "ProductPriceForm" element

    When I proceed as the admin
    And I select following permissions:
      | Product Price | View:Global |
    And I save form

    And I proceed as the manager
    And I go to Products/ Products
    Then I should see "Price (USD)" column in grid
    When I click view "PSKU1" in grid
    Then I should see an "ProductPricesGrid" element
    When I click "Edit"
    Then I should see an "ProductPriceForm" element

  Scenario: Check product prices at the grid, view and edit product page when EDIT forbidden
    Given I proceed as the admin
    And I select following permissions:
      | Product Price | Edit:None |
    And I save form

    And I proceed as the manager
    And I go to Products/ Products
    Then I should see "Price (USD)" column in grid
    When I click view "PSKU1" in grid
    Then I should see an "ProductPricesGrid" element
    When I click "Edit"
    Then I should not see an "ProductPriceForm" element

  Scenario: Check product prices at view page of Price List when EDIT forbidden
    Given I go to Sales/ Price Lists
    And I click view First Price List in grid
    Then I should see an "Price list Product prices Grid" element
    And I should see following buttons:
      | Add Product Price |
    And I should see following actions for PSKU1 in "Price list Product prices Grid":
      | View Product |
      | Delete       |
    And I should not see following actions for SKU1 in "Price list Product prices Grid":
      | Edit   |

    When I proceed as the admin
    And I select following permissions:
      | Product Price | Edit:Global |
    And I save form

    And I proceed as the manager
    And I reload the page
    Then I should see following actions for PSKU1 in "Price list Product prices Grid":
      | View Product |
      | Delete       |
      | Edit         |

  Scenario: Check product prices at view page of Price List when CREATE forbidden
    Given I should see following buttons:
      | Add Product Price |

    And I proceed as the admin
    And I select following permissions:
      | Product Price | Create:None |
    And I save form

    And I proceed as the manager
    And I reload the page
    Then I should see following actions for PSKU1 in "Price list Product prices Grid":
      | View Product |
      | Delete       |
      | Edit         |
    And I should not see following buttons:
      | Add Product Price |

  Scenario: Check product prices at the grid, view and edit product page when CREATE forbidden
    Given I go to Products/ Products
    And click edit "PSKU1" in grid
    Then I should see an "ProductPriceForm" element
    And I should not see an "Add Product Price" element

    When I go to Products/ Products
    And I click "Create Product"
    And I click "Continue"
    Then I should not see an "Add Product Price" element

    When I proceed as the admin
    And I select following permissions:
      | Product Price | Create:Global |
    And I save form

    And I proceed as the manager
    And I go to Products/ Products
    And click edit "PSKU1" in grid
    And I should see an "Add Product Price" element

    And I go to Products/ Products
    And I click "Create Product"
    And I click "Continue"
    Then I should see an "Add Product Price" element

  Scenario: Check product prices at the grid, view and edit product page when DELETE forbidden
    Given I go to Products/ Products
    And click edit "PSKU1" in grid
    Then I should see an "ProductPriceForm" element
    And I should see an "Remove Product Price Button" element

    When I proceed as the admin
    And I select following permissions:
      | Product Price | Delete:None |
    And I save form

    And I proceed as the manager
    And I go to Products/ Products
    And click edit "PSKU1" in grid
    Then I should not see an "ProductPriceForm" element
    And I should not see an "Remove Product Price Button" element

  Scenario: Check product prices at the grid, view and edit Price List when DELETE forbidden
    Given I go to Sales/ Price Lists
    And I click view First Price List in grid
    Then I should see following actions for PSKU1 in "Price list Product prices Grid":
      | View Product |
      | Edit         |
    And I should not see following actions for SKU1 in "Price list Product prices Grid":
      | Delete |
    And I should see following buttons:
      | Add Product Price |

    When I proceed as the admin
    And I select following permissions:
      | Product Price | Delete:Global |
    And I save form

    And I proceed as the manager
    And I reload the page
    Then I should see following actions for PSKU1 in "Price list Product prices Grid":
      | View Product |
      | Delete       |
      | Edit         |
    And I should see following buttons:
      | Add Product Price |

  Scenario: Check ACL for entity "ProductPrice" not manageable for BUYER role
    Given I proceed as the admin
    And I go to Customer/ Customer User Roles
    And I click edit Buyer in grid
    Then I should not see "Product Price" in "CustomerUserRoleGrid" table
