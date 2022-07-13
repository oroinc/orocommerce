@regression
@ticket-BB-17111
@fixture-OroVisibilityBundle:category_tree_with_hidden_subcategory_and_visible_product_in_it.yml

Feature: Breadcrumbs with hidden subcategory on product view page
  In order to manage product and category visibilities
  As a Buyer
  I want to be able to see breadcrumbs on the product view page

  Scenario: Check breadcrumbs
    When I am on the homepage
    And I type "PSKU3" in "search"
    And click "Search Button"
    And I click "View Details" for "PSKU3" product
    Then I should see "All Products / Printers / Product 3"
    And I should not see "All Products / Retail Supplies / Printers / Product 3"
    And I should not see "All Products / Product 3"
