@regression
@elasticsearch
@fixture-OroProductBundle:Products_view_page_templates.yml
Feature: Mass Product Actions for not enabled products
  In order to be able to add only enabled products with help of mass actions
  As a Buyer
  I should receive proper notifications when trying to add disabled product

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator

  Scenario: Not enabled products can not be added to a newly created Shopping List with mass actions
    Given I proceed as the Buyer
    Given I login as AmandaRCole@example.org buyer
    And I go to homepage
    And I type "rtsh_m" in "search"
    And I click "Search Button"
    Then I should see "rtsh_m"
    And I check rtsh_m record in "Product Frontend Grid" grid

    When I proceed as the Admin
    And I go to Products/ Products
    And I click edit rtsh_m in grid
    And I fill "ProductForm" with:
      | Status | Disabled |
    And I save form
    Then I should see "Product has been saved" flash message

    When I proceed as the Buyer
    Then I click "Create New Shopping List" in "ProductFrontendMassPanelInBottomSticky" element
    And I click "Create and Add"
    Then I should see "No products were added"
    And I reload the page
    And there is no records in "Product Frontend Grid"

  Scenario: Not enabled products can not be added with mass actions
    Given I proceed as the Buyer
    And I go to homepage
    And I type "gtsh_l" in "search"
    And I click "Search Button"
    Then I should see "gtsh_l"
    And I check gtsh_l record in "Product Frontend Grid" grid

    When I proceed as the Admin
    And I go to Products/ Products
    And I click edit gtsh_l in grid
    And I fill "ProductForm" with:
      | Status | Disabled |
    And I save form
    Then I should see "Product has been saved" flash message

    When I proceed as the Buyer
    And I click "Add to Shopping List" in "ProductFrontendMassPanelInBottomSticky" element
    Then I should see "No products were added"
    And I reload the page
    And there is no records in "Product Frontend Grid"
