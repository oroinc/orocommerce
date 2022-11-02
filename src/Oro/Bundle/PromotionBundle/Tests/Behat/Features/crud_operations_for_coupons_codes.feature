@ticket-BB-15172
@fixture-OroPromotionBundle:promotions_for_coupons.yml
Feature: CRUD operations for Coupons codes
  As an Administrator
  I want to be able to CRUD Coupons codes via Management Console UI,
  So we need to add Management Console UI for CRUD operations with coupon codes

  Scenario: Create coupon
    Given I login as administrator
    And go to Marketing/Promotions/Coupons
    And I click "Coupons Actions"
    And I click "Create Coupon"
    And I open select entity popup for field "Promotion"
    And I should see "line Item Discount Promotion"
    And I should see "order Discount Promotion"
    And I should not see "shipping Discount Promotion"
    And I click on order Discount Promotion in grid
    When I fill "Coupon Form" with:
      |Coupon Code          | 12345                            |
      |Enabled              | true                             |
      |Uses per Coupon      | 1                                |
      |Valid From           | <DateTime:Jul 1, 2017, 12:00 AM> |
      |Valid Until          | <DateTime:Jul 1, 2018, 12:00 AM> |
    And I type "1" in "Uses per Person"
    And I save and close form
    Then I should see "Coupon has been saved" flash message
    And I should see "12345" in grid with following data:
      |Coupon Code          | 12345                    |
      |Promotion            | order Discount Promotion |
      |Enabled              | Yes                      |
      |Uses per Coupon      | 1                        |
      |Uses per Person      | 1                        |
      |Valid From           | Jul 1, 2017, 12:00 AM    |
      |Valid Until          | Jul 1, 2018, 12:00 AM    |

  Scenario: Save coupon with validFrom greater than validUntil
    Given I go to Marketing/Promotions/Coupons
    And I click "Coupons Actions"
    And I click "Create Coupon"
    When I fill "Coupon Form" with:
      |Coupon Code          | 55555                            |
      |Uses per Coupon      | 1                                |
      |Valid From           | <DateTime:Jul 1, 2018, 12:00 AM> |
      |Valid Until          | <DateTime:Jul 1, 2017, 12:00 AM> |
    And I open select entity popup for field "Promotion"
    And I click on order Discount Promotion in grid
    When I save and close form
    Then I should see "Valid Until date should follow after Valid From."

  Scenario: View existing coupon
    Given I go to Marketing/Promotions/Coupons
    And I click view "12345" in grid
    Then I should see coupon with:
      |Coupon Code       | 12345                    |
      |Promotion         | order Discount Promotion |
      |Uses per Coupon   | 1                        |
      |Uses per Person   | 1                        |
      |Valid Until       | Jul 1, 2018, 12:00 AM    |
    And I should see "Active"

  Scenario: Edit existing coupon
    Given I go to Marketing/Promotions/Coupons
    And click edit "12345" in grid
    And fill form with:
      |Coupon Code       | 12345    |
      |Enabled           | false    |
      |Uses per Coupon   | 10       |
    And I type "1" in "Uses per Person"
    And I clear "Promotion" field
    When I save and close form
    Then I should see "Coupon has been saved" flash message
    And I should see "12345" in grid with following data:
      |Coupon Code       | 12345                 |
      |Promotion         |                       |
      |Enabled           | No                    |
      |Uses per Coupon   | 10                    |
      |Uses per Person   | 1                     |
      |Valid Until       | Jul 1, 2018, 12:00 AM |

  Scenario: View edited coupon
    Given click view "12345" in grid
    Then I should see coupon with:
      |Code              | 12345                 |
      |Promotion         | N/A                   |
      |Uses per Coupon   | 10                    |
      |Uses per Person   | 1                     |
      |Valid Until       | Jul 1, 2018, 12:00 AM |
    And I should see "Inactive"

  Scenario: Create second coupon
    Given I go to Marketing/Promotions/Coupons
    And I click "Coupons Actions"
    And I click "Create Coupon"
    And I fill "Coupon Form" with:
      |Code              | 54321    |
      |Uses per Coupon   | 10       |
    And I type "10" in "Uses per Person"
    When I save and close form
    Then I should see "Coupon has been saved" flash message
    And I should see "54321" in grid with following data:
      |Coupon Code       | 54321    |
      |Promotion         |          |
      |Uses per Coupon   | 10       |
      |Uses per Person   | 10       |
      |Valid Until       |          |
    And I should see "12345" in grid with following data:
      |Coupon Code       | 12345                 |
      |Promotion         |                       |
      |Uses per Coupon   | 10                    |
      |Uses per Person   | 1                     |
      |Valid Until       | Jul 1, 2018, 12:00 AM |

  Scenario: Edit existing coupon and select promotion from the promotions grid
    Given I go to Marketing/Promotions/Coupons
    And click edit "12345" in grid
    And I open select entity popup for field "Promotion"
    And I click on line Item Discount Promotion in grid
    When I save and close form
    Then I should see "Coupon has been saved" flash message
    And I should see "12345" in grid with following data:
      |Coupon Code       | 12345                        |
      |Promotion         | line Item Discount Promotion |
      |Uses per Coupon   | 10                           |
      |Uses per Person   | 1                            |
      |Valid Until       | Jul 1, 2018, 12:00 AM        |

  Scenario: Delete existing coupon
    Given I click delete "12345" in grid
    And I click "Yes, Delete" in modal window
    Then I should see "Coupon deleted" flash message
    And I should see "54321" in grid with following data:
      |Coupon Code       | 54321    |
      |Promotion         |          |
      |Uses per Coupon   | 10       |
      |Uses per Person   | 10       |
      |Valid Until       |          |
    And I should not see "12345"

  Scenario: Create coupon with existing code
    Given I go to Marketing/Promotions/Coupons
    And I click "Coupons Actions"
    And I click "Create Coupon"
    And I fill "Coupon Form" with:
      |Code               | 54321    |
    When I save and close form
    Then I should see validation errors:
      |Coupon Code |This value is already used. |

  Scenario: Create coupon with empty 'uses per' fields
    Given I go to Marketing/Promotions/Coupons
    And I click "Coupons Actions"
    And I click "Create Coupon"
    And I fill "Coupon Form" with:
      |Coupon Code       | 22222   |
      |Uses per Coupon   |         |
    And I type "" in "Uses per Person"
    When I save and close form
    Then I should see "Coupon has been saved" flash message
    And I should see "22222" in grid with following data:
      |Uses per Coupon   |         |
      |Uses per Person   |         |

  Scenario: Save coupon without required data
    Given I go to Marketing/Promotions/Coupons
    And I click "Coupons Actions"
    And I click "Create Coupon"
    When I save and close form
    Then I should see validation errors:
      |Coupon Code |This value should not be blank. |

  Scenario: Search coupons by coupon code
    When I click "Search"
    And type "22222" in "search"
    Then I should see 1 search suggestions
    When I click "Search Submit"
    Then I should be on Search Result page
    And I should see following search entity types:
      | Type        | N | isSelected |
      | All         | 1 | yes        |
      | Coupons     | 1 |            |
    And number of records should be 1
    And I should see following search results:
      | Title  | Type   |
      | 22222  | Coupon |
