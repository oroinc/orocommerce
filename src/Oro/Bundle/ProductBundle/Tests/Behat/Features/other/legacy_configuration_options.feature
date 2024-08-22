@regression
@ticket-BB-24264

Feature: Legacy configuration options
  Some system configuration options remain in the system configuration in order to support old storefront themes.
  These options are either not used in the new themes, or are configured elsewhere (most likely in the theme configurator).
  To help admins better understand what is going on, we explain why such options are preserved,
  and (if applicable) what other configuration should be used instead.

  Scenario: Check legacy configuration options in System Configuration
    Given I login as administrator
    And I go to System / Configuration

    When I follow "Commerce/Product/Featured Products" on configuration sidebar
    Then I should see "Featured Products Segment"
    And I click on warning tooltip for "Segment" config field
    Then I should see "This configuration applies to OroCommerce version 5.1 and below and is retained in the current version only for backward compatibility with legacy storefront themes. In the new themes starting with v6.0 and above, the homepage is a fully editable landing page, and the selection of widgets displayed on the page can be controlled directly in the landing page editor."

    When I follow "Commerce/Product/Promotions" on configuration sidebar
    Then I should see "New Arrivals"
    And I click on warning tooltip for "Product Segment" config field
    Then I should see "This configuration applies to OroCommerce version 5.1 and below and is retained in the current version only for backward compatibility with legacy storefront themes. In the new themes starting with v6.0 and above, the homepage is a fully editable landing page, and the selection of widgets displayed on the page can be controlled directly in the landing page editor."

    When I follow "Commerce/Catalog/Filters and Sorters" on configuration sidebar
    Then I should see "Display Settings"
    And I click on warning tooltip for "Filter Panel Position" config field
    Then I should see "This configuration applies to OroCommerce version 5.1 and below and is retained in the current version only for backward compatibility with legacy storefront themes. For newer themes starting with v6.0 and above, please configure this option under System > Theme Configuration."
