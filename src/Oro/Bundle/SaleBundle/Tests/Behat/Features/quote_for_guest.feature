@regression
@ticket-BB-15291
@fixture-OroSaleBundle:GuestLinkQuotesFixtures.yml
@fixture-OroUserBundle:user.yml
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml

Feature: Quote for guest
  In order to provide possibility create quotes for non authorized users
  As an Administrator
  I should be able to manage Quotes for Guests
  As an Guest User
  I want to be abble to accept Quote without registration o website and log in

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Guest | second_session |
      | Buyer | system_session |

  Scenario: If "Guest Quote" is turned off preselected email template for "Send to Customer" action is "quote_email_link"
    Given I proceed as the Admin
    And login as administrator
    When I go to Sales/Quotes
    And click "Send to Customer" on row "Quote_1" in grid
    Then "Send to Customer Form" must contains values:
      | Apply template | quote_email_link |
    And I should not see "quote_email_link_guest" for "Apply template" select
    And I click "Cancel"

  Scenario: Turn on Guest Quote
    When I go to System / Configuration
    And I follow "Commerce/Sales/Quotes" on configuration sidebar
    And uncheck "Use default" for "Enable Guest Quote" field
    And check "Enable Guest Quote"
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Guest Quote configuration is available on website level
    When I go to System / Websites
    And click Configuration Default in grid
    And follow "Commerce/Sales" on configuration sidebar
    Then I should see "Quotes" in the "Configuration Sidebar Content" element

  Scenario: Guest Quote configuration is available on organization level
    When I go to System / User Management / Organizations
    And I click Configuration Oro in grid
    And I follow "Commerce/Sales" on configuration sidebar
    Then I should see "Quotes" in the "Configuration Sidebar Content" element

  # Guest Link present in DB for all Quotes. If admin user sand this lik via email or give it guest user in any other way.
  # Guest Quote data should be unavailable until Quote change status to "Send to customer"
  Scenario: Quote in state not equal "Sent to Customer" is unavailable for Guest users by guest link
    Given I proceed as the Guest
    When I visit guest quote link for quote Quote_1
    Then I should see "404 Not Found"

  Scenario: Quote in state not equal "Sent to Customer" is unavailable for logged in users by guest link
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I visit guest quote link for quote Quote_1
    Then I should see "404 Not Found"

  Scenario: Send Quote to customer
    Given I proceed as the Admin
    When I go to Sales/Quotes
    And I click view Quote_1 in grid
    And I should see Quote with:
      | Internal Status | Draft   |
      | Customer Status | N/A     |
      | Website         | Default |
    And I should not see "Unique Guest link"

    When I click "Send to Customer"
    Then "Send to Customer Form" must contains values:
      | Apply template | quote_email_link_guest |

    When I fill "Send to Customer Form" with:
      | To | Charlie Sheen |
    And click "Send"
    Then Guest Quote "Quote_1" email has been sent to "charlie@example.com"
    And I should see "Quote_1 successfully sent to customer" flash message
    And I should see Quote with:
      | Internal Status | Sent to Customer |
      | Customer Status | N/A              |
      | Website         | Default          |
    And I should see "Unique Guest link"
    And I should see truncated to 31 symbols link for quote qid Quote_1

  Scenario: Copy guest link to clipboard
    When I click "Copy to Clipboard"
    Then I should see "Copied to clipboard" flash message

  Scenario: Turn off Guest Quote
    When I go to System / Configuration
    And follow "Commerce/Sales/Quotes" on configuration sidebar
    And uncheck "Enable Guest Quote"
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: If "Guest Quote" is turned off Quote is unavailable for Guest users by guest link
    Given I proceed as the Guest
    When I visit guest quote link for quote Quote_1
    Then I should see "404 Not Found"

  Scenario: Turn on Guest Quote
    Given I proceed as the Admin
    When I go to System / Configuration
    And I follow "Commerce/Sales/Quotes" on configuration sidebar
    And check "Enable Guest Quote"
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: If "Guest Checkout" is turned off available Guest Quote is not acceptable
    Given I proceed as the Guest
    When I visit guest quote link for quote Quote_1
    Then I should see "QUOTE #QUOTE_1"
    And I should not see an "Page Sidebar" element
    And I should not see an "Breadcrumbs" element
    And I should not see "Accept and Submit to Order"

  Scenario: Logged in user has not access to quote from Quotes grid in profile
    Given I proceed as the Buyer
    And I am on the homepage
    When I click "Quotes"
    Then I should see "There are no quotes"

  Scenario: Logged in user has access to quote from Guest Quote link
    When I visit guest quote link for quote Quote_1
    Then I should see "QUOTE #QUOTE_1"
    And I should see "Accept and Submit to Order"
    And I should see an "Page Sidebar" element
    And I should see an "Breadcrumbs" element

    When I click "Accept and Submit to Order"
    Then First Product Quantity on Quote field should has 10 value

    When I type "30" in "First Product Quantity on Quote"
    And I click on empty space
    Then I should see "Subtotal $3,000.00"
    And I should see "Total $3,000.00"

    When click "Submit"
    And I should see "Checkout"

  Scenario: Enable Guest Checkout
    Given I proceed as the Admin
    And I go to System/Configuration
    And I follow "Commerce/Sales/Checkout" on configuration sidebar
    And uncheck "Use default" for "Guest Checkout" field
    And I check "Guest Checkout"
    When I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Accept Guest Quote
    Given I proceed as the Guest
    When I visit guest quote link for quote Quote_1
    Then I should see "QUOTE #QUOTE_1"
    And I should see "Accept and Submit to Order"
    And I should not see an "Page Sidebar" element
    And I should not see an "Breadcrumbs" element

    When I click "Accept and Submit to Order"
    Then I should not see an "Page Sidebar" element
    And I should not see an "Breadcrumbs" element
    And First Product Quantity on Quote field should has 10 value

    When I type "20" in "First Product Quantity on Quote"
    And I click on empty space
    Then I should see "Subtotal $2,000.00"
    And I should see "Total $2,000.00"
    And I should see "Submit"

    When I click "Submit"
    And I should see "Checkout"

  Scenario: Accepted and submitted Guest Quote is still available for guest user
    When I visit guest quote link for quote Quote_1
    Then I should see "QUOTE #QUOTE_1"

  Scenario: Accepted and submitted Guest Quote is still available for logged in user
    Given I proceed as the Buyer
    When I visit guest quote link for quote Quote_1
    Then I should see "QUOTE #QUOTE_1"

  Scenario: Guest Quote link is clicable for administrator
    Given I proceed as the Admin
    When I go to Sales/Quotes
    And I click view Quote_1 in grid
    And I click truncated to 31 symbols Guest Quote Quote_1 link
    Then a new browser tab is opened and I switch to it
    And I should see "Sign in"
    And I should see "QUOTE #QUOTE_1"
    And I should see "Accept and Submit to Order"
