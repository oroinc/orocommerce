@regression
Feature: Rule Editor with Autocomplete in Price List
  In order to simplify editing of the rules
  As admin
  I need to have autocomplete and validation in rule editors

  Scenario: Checking of autocomplete for Product Assignment Rule
    Given I login as administrator
    And I go to Sales/ Price Lists
    And click "Create Price List"
    And I fill form with:
      | Name       | TestPriceList |
      | Currencies | US Dollar ($) |
      | Active     | true          |

    When I focus on "Product Assignment Rule"
    Then I should see "product" in typeahead suggestions for "Product Assignment Rule"
    When I select "product…" from typeahead suggestions for "Product Assignment Rule"
    And I should see "featured" in typeahead suggestions for "Product Assignment Rule"
    And I should see 20 typeahead suggestions for "Product Assignment Rule"
    When I type "featured " in "Product Assignment Rule Editor"
    Then I should see 17 typeahead suggestions for "Product Assignment Rule"
    When type "== " in "Product Assignment Rule Editor"
    Then I should see "product…" in typeahead suggestions for "Product Assignment Rule"
    And I should see "pricelist…" in typeahead suggestions for "Product Assignment Rule"
    When type "true" in "Product Assignment Rule Editor"
    And I save and close form
    Then I should see "Price List has been saved" flash message

  Scenario: Checking of autocomplete validation for Product Assignment Rule
    And I go to Sales/ Price Lists
    And click "Create Price List"
    And I fill form with:
      | Name       | TestPriceList |
      | Currencies | US Dollar ($) |
      | Active     | true          |

    When I focus on "Product Assignment Rule"
    Then I should see "pricelist…" in typeahead suggestions for "Product Assignment Rule"
    When I type "pricelist[1]" in "Product Assignment Rule Editor"
    Then I should see "Default Price List"
    When I click on "Price List Select Clear Button"
    Then Product Assignment Rule field should has pricelist[] value
    When I click on "Price List Select Grid Button"
    And I click on Default Price List in grid
    Then Product Assignment Rule field should has pricelist[1] value
    When I type ".acti" in "Product Assignment Rule Editor"
    And I select "active" from typeahead suggestions for "Product Assignment Rule"
    Then Product Assignment Rule field should has pricelist[1].active  value
    When I save and close form
    Then I should see validation errors:
      | Rule | Invalid expression; Invalid logical expression |
    When type " == true" in "Product Assignment Rule Editor"
    And I save and close form
    Then I should see "Price List has been saved" flash message

  Scenario: Checking of autocomplete for Price Calculation Rules
    And I go to Sales/ Price Lists
    And click "Create Price List"
    And click "Price Calculation Add"
    And I fill form with:
      | Name       | TestPriceList |
      | Currencies | US Dollar ($) |
      | Active     | true          |
      | Priority   | 1 |
    And I type "1" in "Price Calculation Quantity"

    When I click "Price Calculation Unit Expression Button"
    And I click on "Price Calculation Unit Expression Editor"
    And I select "pricelist…" from typeahead suggestions for "Price Calculation Unit Expression Editor"
    Then Price Calculation Unit Expression field should has pricelist[]. value
    When I click on "Price List Select Grid Button"
    And I click on Default Price List in grid
    Then I click "Price Calculation Unit Expression Editor"
    And I select "prices…" from typeahead suggestions for "Price Calculation Unit Expression Editor"
    And I select "unit" from typeahead suggestions for "Price Calculation Unit Expression Editor"
    Then Price Calculation Unit Expression field should has pricelist[1].prices.unit  value

    When I click "Price Calculation Currency Expression Button"
    And I click on "Price Calculation Currency Expression Editor"
    And I select "pricelist…" from typeahead suggestions for "Price Calculation Currency Expression Editor"
    Then Price Calculation Currency Expression field should has pricelist[]. value
    When I click on "Price List Select Grid Button"
    And I click on Default Price List in grid
    Then I click "Price Calculation Currency Expression Editor"
    And I select "prices…" from typeahead suggestions for "Price Calculation Currency Expression Editor"
    And I select "currency" from typeahead suggestions for "Price Calculation Currency Expression Editor"
    Then Price Calculation Currency Expression field should has pricelist[1].prices.currency  value

    When I click on "Price Calculation Calculate As Editor"
    And I select "pricelist…" from typeahead suggestions for "Price Calculation Calculate As Editor"
    And I click on "Price List Select Grid Button"
    And I click on Default Price List in grid
    Then I click on "Price Calculation Calculate As Editor Content Line"
    And I select "prices" from typeahead suggestions for "Price Calculation Calculate As Editor"
    And I select "quantity" from typeahead suggestions for "Price Calculation Calculate As Editor"
    And I select "+" from typeahead suggestions for "Price Calculation Calculate As Editor"
    Then Price Calculation Calculate As field should has pricelist[1].prices.quantity +  value
    And I type "1" in "Price Calculation Calculate As Editor Content"

    And I focus on "Price Calculation Condition"
    And I should see "product…" in typeahead suggestions for "Price Calculation Condition"
    And I type "pricelist[2].prices.unit == product.primaryUnitPrecision.unit" in "Price Calculation Condition Editor Content"

    When I save and close form
    Then I should see "Price List has been saved" flash message

    And I go to Sales/ Price Lists
    And click "Create Price List"
    And click "Price Calculation Add"
    And I fill form with:
     | Name       | TestPriceList2 |
     | Currencies | US Dollar ($)  |
     | Active     | true           |
     | Priority   | 10             |
    And I type "1" in "Price Calculation Quantity"

    And I click "Price Calculation Unit Expression Button"
    And I type "pricelist[1].prices.unit" in "Price Calculation Unit Expression Editor Content"
    And I click on empty space
    And I click "Price Calculation Currency Expression Button"
    And I type "pricelist[1].prices.currency" in "Price Calculation Currency Expression Editor Content"
    When I click on "Price Calculation Calculate As Editor"
    And I select "product…" from typeahead suggestions for "Price Calculation Calculate As Editor"
    Then I should not see "attributeFamily…" in typeahead suggestions for "Price Calculation Calculate As Editor"
    And I should see "category…" in typeahead suggestions for "Price Calculation Calculate As Editor"
    And I click "Cancel"

  Scenario: Checking backend validation for Price Calculation Rules
    And I go to Sales/ Price Lists
    And click "Create Price List"
    And click "Price Calculation Add"
    And I fill form with:
      | Name       | TestPriceList |
      | Currencies | US Dollar ($) |
      | Active     | true          |
      | Priority   | 1             |
    And I type "1" in "Price Calculation Quantity"

    And I click "Price Calculation Unit Expression Button"
    And I click on empty space
    And I click "Price Calculation Currency Expression Button"
    And I fill form with:
      | Calculate As | pricelist[1].prices.  |
      | Condition    | pricelist[12].prices. |
    And I type "pricelist[12]." in "Price Calculation Currency Expression Editor Content"
    And I type "pricelist[1]." in "Price Calculation Unit Expression Editor Content"
    And I save form
    Then I should see "Unexpected end of expression around position 14 for expression `pricelist[1].`."
    And I should not see "Unexpected end of expression around position 14 for expression `pricelist[1].`.; Unexpected end of expression around position 14 for expression `pricelist[1].`."
    And I should see "Unexpected end of expression around position 15 for expression `pricelist[12].`."
    And I should not see "Unexpected end of expression around position 15 for expression `pricelist[12].`.; Unexpected end of expression around position 15 for expression `pricelist[12].`."
    And I should see "Unexpected end of expression around position 21 for expression `pricelist[1].prices.`."
    And I should not see "Unexpected end of expression around position 21 for expression `pricelist[1].prices.`.; Unexpected end of expression around position 21 for expression `pricelist[1].prices.`."
    And I should see "Unexpected end of expression around position 22 for expression `pricelist[12].prices.`."
    And I should not see "Unexpected end of expression around position 22 for expression `pricelist[12].prices.`.; Unexpected end of expression around position 22 for expression `pricelist[12].prices.`."
