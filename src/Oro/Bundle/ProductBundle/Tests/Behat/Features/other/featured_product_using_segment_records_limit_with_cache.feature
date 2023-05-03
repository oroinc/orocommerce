@ticket-BAP-18680
@fixture-OroProductBundle:featured_products.yml
@regression
Feature: Featured product using segment records limit with cache

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Guest | second_session |

  Scenario: Check that featured products exist
    Given I proceed as the Guest
    And I wait for 3 seconds
    When I go to homepage
    And I scroll to "Featured Products Block"
    Then I should see the following products in the "Featured Products Block":
      | SKU  |
      | SKU1 |
      | SKU2 |
      | SKU3 |

  Scenario: Change records limit for featured products segment
    Given I proceed as the Admin
    And I login as administrator
    And I go to Reports & Segments / Manage Segments
    And I click edit "Featured Products" in grid
    And I fill "Segment Form" with:
      | Records Limit | 1 |
    When I save form
    Then I should see "Segment saved" flash message

    # Change Featured Products order for prevent incorrect sorting
    And I go to Reports & Segments / Manage Segments
    And I click edit "Featured Products" in grid
    And I click "Edit First Segment Column"
    And I fill "Segment Form" with:
      | Sorting | Desc |
    And I click "Save Column Button"
    And I click "Edit Second Segment Column"
    And I fill "Segment Form" with:
      | Sorting | None |
    And I click "Save Column Button"
    And I save and close form

    Given I proceed as the Guest
    When I go to homepage
    Then I should see the following products in the "Featured Products Block":
      | SKU  |
      | SKU3 |
    And I should not see the following products in the "Featured Products Block":
      | SKU  |
      | SKU1 |
      | SKU2 |
    When I reload the page
    Then I should see the following products in the "Featured Products Block":
      | SKU  |
      | SKU3 |
    And I should not see the following products in the "Featured Products Block":
      | SKU  |
      | SKU1 |
      | SKU2 |
