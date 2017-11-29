@skip
#TODO: unskip after BB-13064

Feature: Rule Editor with Autocomplete in Price List

  Scenario: Checking of autocomplete for Product Assignment Rule
    Given I login as administrator
    And I go to Sales/ Price Lists
    And click "Create Price List"
    And I fill form with:
      | Name       | TestPriceList |
      | Currencies | US Dollar ($) |
      | Active     | true          |

    When I click on "Product Assignment Rule"
    Then I should see "product…" in typeahead suggestions for "Product Assignment Rule"
    When I select "product…" from typeahead suggestions for "Product Assignment Rule"
    And I should see "featured" in typeahead suggestions for "Product Assignment Rule"
    And I should see 20 typeahead suggestions for "Product Assignment Rule"
    When I type "product.featured " in "Product Assignment Rule"
    Then I should see 16 typeahead suggestions for "Product Assignment Rule"
    When type "product.featured + " in "Product Assignment Rule"
    Then I should see "product…" in typeahead suggestions for "Product Assignment Rule"
    And I should see "pricelist…" in typeahead suggestions for "Product Assignment Rule"
    When type "product.featured == true" in "Product Assignment Rule"
    And I save and close form
    Then I should see "Price List has been saved" flash message

  Scenario: Checking of autocomplete validation for Product Assignment Rule
    And I go to Sales/ Price Lists
    And click "Create Price List"
    And I fill form with:
      | Name       | TestPriceList |
      | Currencies | US Dollar ($) |
      | Active     | true          |

    When I click on "Product Assignment Rule"
    Then I should see "pricelist…" in typeahead suggestions for "Product Assignment Rule"
    When I type "pricelist[1]" in "Product Assignment Rule"
    Then I should see "Default Price List"
    When I click on "Price List Select Clear Button"
    Then Product Assignment Rule field should has pricelist[] value
    When I click on "Price List Select Grid Button"
    And I click on Default Price List in grid
    Then Product Assignment Rule field should has pricelist[1] value
    When I type "pricelist[1].acti" in "Product Assignment Rule"
    And I select "active" from typeahead suggestions for "Product Assignment Rule"
    Then Product Assignment Rule field should has pricelist[1].active  value
    When I save and close form
    Then I should see validation errors:
      | Rule | Invalid logical expression |
    When type "pricelist[1].active == true" in "Product Assignment Rule"
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
    And I click on "Price Calculation Unit Expression"
    And I select "pricelist…" from typeahead suggestions for "Price Calculation Unit Expression"
    Then Price Calculation Unit Expression field should has pricelist[]. value
    When I click on "Price List Select Grid Button"
    And I click on Default Price List in grid
    And I select "prices…" from typeahead suggestions for "Price Calculation Unit Expression"
    And I select "unit" from typeahead suggestions for "Price Calculation Unit Expression"
    Then Price Calculation Unit Expression field should has pricelist[1].prices.unit  value

    When I click "Price Calculation Currency Expression Button"
    And I click on "Price Calculation Currency Expression"
    And I select "pricelist…" from typeahead suggestions for "Price Calculation Currency Expression"
    Then Price Calculation Currency Expression field should has pricelist[]. value
    When I click on "Price List Select Grid Button"
    And I click on Default Price List in grid
    And I select "prices…" from typeahead suggestions for "Price Calculation Currency Expression"
    And I select "currency" from typeahead suggestions for "Price Calculation Currency Expression"
    Then Price Calculation Currency Expression field should has pricelist[1].prices.currency  value

    When I click on "Price Calculation Calculate As"
    And I select "pricelist…" from typeahead suggestions for "Price Calculation Calculate As"
    And I click on "Price List Select Grid Button"
    And I click on Default Price List in grid
    And I select "prices" from typeahead suggestions for "Price Calculation Calculate As"
    And I select "quantity" from typeahead suggestions for "Price Calculation Calculate As"
    And I select "+" from typeahead suggestions for "Price Calculation Calculate As"
    Then Price Calculation Calculate As field should has pricelist[1].prices.quantity +  value
    And I type "pricelist[1].prices.quantity + 1" in "Price Calculation Calculate As"

    And I click on "Price Calculation Condition"
    And I should see "product…" in typeahead suggestions for "Price Calculation Condition"
    And I type "pricelist[2].prices.unit == product.primaryUnitPrecision.unit" in "Price Calculation Condition"

    When I save and close form
    Then I should see "Price List has been saved" flash message

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
    And I click "Price Calculation Currency Expression Button"
    And I fill form with:
      | Product Unit | pricelist[1].     |
      | Currency     | pricelist[12].    |
      | Calculate As | pricelist[1].pri  |
      | Condition    | pricelist[12].pri |
    And I save form
    Then I should see "Expected name around position 14."
    And I should not see "Expected name around position 14.; Expected name around position 14."
    And I should see "Expected name around position 15."
    And I should not see "Expected name around position 15.; Expected name around position 15."
    And I should see "Expected name around position 21."
    And I should not see "Expected name around position 21.; Expected name around position 21."
    And I should see "Expected name around position 22."
    And I should not see "Expected name around position 22.; Expected name around position 22."
