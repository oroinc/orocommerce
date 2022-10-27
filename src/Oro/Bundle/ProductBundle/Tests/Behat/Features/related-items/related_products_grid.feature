@regression
@fixture-OroProductBundle:related_products.yml
@ticket-BAP-17648

Feature: Related Products Grid
  In order to ensure backoffice related products grid works correctly
  As an administrator
  I check filters are working, sorting is working and columns config is working as designed.

  Scenario: Feature Background
    Given I login as administrator
    And I go to Products / Products
    And I click View PSKU2 in grid
    And I click "Related Items"

  Scenario: Check SKU filter
    Given records in "RelatedProductsViewGrid" should be 4
    When I filter SKU as Contains "PSKU3" in "RelatedProductsViewGrid"
    Then I should see following "RelatedProductsViewGrid" grid:
      | SKU   |
      | PSKU3 |
    And records in "RelatedProductsViewGrid" should be 1
    And I reset "SKU" filter in "RelatedProductsViewGrid"

  Scenario: Check Name filter
    Given records in "RelatedProductsViewGrid" should be 4
    When I filter Name as Contains "Product 1" in "RelatedProductsViewGrid"
    Then I should see following "RelatedProductsViewGrid" grid:
      | SKU   |
      | PSKU1 |
    And records in "RelatedProductsViewGrid" should be 1
    And I reset "Name" filter in "RelatedProductsViewGrid"

  Scenario: Check Inventory Status filter
    Given records in "RelatedProductsViewGrid" should be 4
    When I choose filter for Inventory Status as Is Any Of "Out of Stock" in "RelatedProductsViewGrid"
    Then there is no records in "RelatedProductsViewGrid"
    And I reset "Inventory Status" filter in "RelatedProductsViewGrid"

  Scenario: Check Status filter
    Given records in "RelatedProductsViewGrid" should be 4
    When I check "Enabled" in "Status: All" filter in "RelatedProductsViewGrid" strictly
    Then I should not see "PSKU5"
    And I reset "Status: Enabled" filter in "RelatedProductsViewGrid"

  Scenario: Check Type filter
    Given records in "RelatedProductsViewGrid" should be 4
    When I check "Configurable" in "Type: All" filter in "RelatedProductsViewGrid" strictly
    Then there is no records in "RelatedProductsViewGrid"
    And I reset "Type: Configurable" filter in "RelatedProductsViewGrid"

  Scenario: Sort by SKU
    Given I should see following "RelatedProductsViewGrid" grid:
      | SKU   |
      | PSKU5 |
      | PSKU4 |
      | PSKU3 |
      | PSKU1 |
    When I sort "RelatedProductsViewGrid" by "SKU"
    Then I should see following "RelatedProductsViewGrid" grid:
      | SKU   |
      | PSKU1 |
      | PSKU3 |
      | PSKU4 |
      | PSKU5 |
    When I sort "RelatedProductsViewGrid" by "SKU" again
    Then I should see following "RelatedProductsViewGrid" grid:
      | SKU   |
      | PSKU5 |
      | PSKU4 |
      | PSKU3 |
      | PSKU1 |
    And I reset "RelatedProductsViewGrid" grid

  Scenario: Sort by Name
    Given I should see following "RelatedProductsViewGrid" grid:
      | SKU   |
      | PSKU5 |
      | PSKU4 |
      | PSKU3 |
      | PSKU1 |
    When I sort "RelatedProductsViewGrid" by "Name"
    Then I should see following "RelatedProductsViewGrid" grid:
      | SKU   |
      | PSKU1 |
      | PSKU3 |
      | PSKU4 |
      | PSKU5 |
    When I sort "RelatedProductsViewGrid" by "Name" again
    Then I should see following "RelatedProductsViewGrid" grid:
      | SKU   |
      | PSKU5 |
      | PSKU4 |
      | PSKU3 |
      | PSKU1 |
    And I reset "RelatedProductsViewGrid" grid

  Scenario: Sort by Status
    Given I should see following "RelatedProductsViewGrid" grid:
      | SKU   |
      | PSKU5 |
      | PSKU4 |
      | PSKU3 |
      | PSKU1 |
    When I sort "RelatedProductsViewGrid" by "Status"
    Then I should see following "RelatedProductsViewGrid" grid:
      | SKU   |
      | PSKU5 |
      | PSKU1 |
      | PSKU3 |
      | PSKU4 |
    When I sort "RelatedProductsViewGrid" by "Status" again
    Then I should see following "RelatedProductsViewGrid" grid:
      | SKU   |
      | PSKU4 |
      | PSKU3 |
      | PSKU1 |
      | PSKU5 |
    And I reset "RelatedProductsViewGrid" grid

  Scenario: Sort by Updated At
    Given I should see following "RelatedProductsViewGrid" grid:
      | SKU   |
      | PSKU5 |
      | PSKU4 |
      | PSKU3 |
      | PSKU1 |
    When I sort "RelatedProductsViewGrid" by "Updated At"
    Then I should see following "RelatedProductsViewGrid" grid:
      | SKU   |
      | PSKU1 |
      | PSKU3 |
      | PSKU4 |
      | PSKU5 |
    When I sort "RelatedProductsViewGrid" by "Updated At" again
    Then I should see following "RelatedProductsViewGrid" grid:
      | SKU   |
      | PSKU5 |
      | PSKU4 |
      | PSKU3 |
      | PSKU1 |
    And I reset "RelatedProductsViewGrid" grid
