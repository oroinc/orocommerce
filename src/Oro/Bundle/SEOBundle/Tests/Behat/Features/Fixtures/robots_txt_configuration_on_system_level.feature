@gridfs-skip
@ticket-BB-22555

Feature: Robots txt Configuration on System Level

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Trigger the robots.txt generation
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Configuration
    And I follow "System Configuration/Websites/Sitemap" on configuration sidebar
    When I uncheck "Use default" for "Robots.txt Template" field
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Check the default robots.txt
    Given I proceed as the Buyer
    When I open the robots.txt url
    Then I should see the following lines
      """
      User-agent: *

      Disallow: /*/massFrontAction/
      Disallow: /*?grid[*
      Disallow: /.well-known/
      Disallow: /actionwidget/
      Disallow: /admin/
      Disallow: /ajax/
      Disallow: /api/
      Disallow: /attachment/
      Disallow: /autocomplete/search
      Disallow: /contact-us/create
      Disallow: /cookies-accepted
      Disallow: /customer/
      Disallow: /datagrid/
      Disallow: /dictionary/
      Disallow: /entity-pagination/
      Disallow: /entitytotals/
      Disallow: /export/
      Disallow: /impersonate_user/
      Disallow: /localization/
      Disallow: /oauth2-token
      Disallow: /payment/callback/
      Disallow: /preview/
      Disallow: /product-unit/
      Disallow: /product-variant/
      Disallow: /product/images-by-id/
      Disallow: /product/names-by-skus
      Disallow: /product/search/autocomplete
      Disallow: /product/search?search=*
      Disallow: /product/set-product-filters-sidebar-state
      Disallow: /productprice/
      Disallow: /promotion/coupon/ajax/
      Disallow: /seller-registration-*
      Disallow: /shoppinglist/matrix-grid-order/
      Disallow: /stripe/
      Disallow: /view-switcher/
      Disallow: /workflow/
      Disallow: /workflowwidget/
      """

  Scenario: Update robots.txt on system level
    Given I proceed as the Admin
    And I go to System/Configuration
    And I follow "System Configuration/Websites/Sitemap" on configuration sidebar
    When I fill form with:
      | Robots.txt Template | Disallow: /custom-system |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Check the custom robots.txt on system level
    Given I proceed as the Buyer
    When I open the robots.txt url
    Then I should see the following lines
      """
      Disallow: /custom-system
      """
