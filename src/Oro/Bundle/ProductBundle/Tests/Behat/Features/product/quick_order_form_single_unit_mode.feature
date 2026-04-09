@ticket-BB-25955
@fixture-OroProductBundle:product_with_price.yml
@pricing-storage-combined
@regression

Feature: Quick Order Form Single Unit Mode
  In order to ensure consistent unit display in the storefront
  As a Buyer
  I need to see only single product unit in the Quick Order Form when single unit mode is enabled

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: All sell units are available in the Quick Order Form without single unit mode
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I click "Quick Order"
    And I fill "Quick Order Form" with:
      | SKU1 | PSKU1 |
    And I wait for products to load
    Then "Quick Order Form" must contains values:
      | UNIT1     | item   |
      | SUBTOTAL1 | $10.00 |
    And I should see that the "Quick Order Form Line Item 1 Unit" element has available units:
      | item |
      | set  |

  Scenario: Enable Single Unit mode
    Given I proceed as the Admin
    And I login as administrator
    When I go to System/ Configuration
    And I follow "Commerce/Product/Product Unit" on configuration sidebar
    And uncheck "Use default" for "Single Unit" field
    And I check "Single Unit"
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario: Only primary unit is shown in the Quick Order Form when single unit mode is enabled
    Given I proceed as the Buyer
    And I reload the page
    When I fill "Quick Order Form" with:
      | SKU1 | PSKU1 |
    And I wait for products to load
    Then "Quick Order Form" must contains values:
      | UNIT1     | item   |
      | SUBTOTAL1 | $10.00 |
    And I should see that the "Quick Order Form Line Item 1 Unit" element has available units:
      | item |

  Scenario: Only primary unit is shown after using copy-paste
    When I reload the page
    And I fill "Quick Add Copy Paste Form" with:
      | Paste your order | PSKU1 1 |
    And I click "Verify Order"
    Then "Quick Order Form" must contains values:
      | UNIT1     | item   |
      | SUBTOTAL1 | $10.00 |
    And I should see that the "Quick Order Form Line Item 1 Unit" element has available units:
      | item |
