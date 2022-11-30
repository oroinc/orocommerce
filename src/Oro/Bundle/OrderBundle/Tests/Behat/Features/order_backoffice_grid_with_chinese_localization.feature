@ticket-BB-21723
@fixture-OroOrderBundle:order.yml
@fixture-OroLocaleBundle:ChineseLocalization.yml

Feature: Order Backoffice grid with chinese localization
  As a back office user
  I want to see right formatted dates while using chinese localization

  Scenario: Change user`s localization to chinese
    Given I login as administrator
    When I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And fill form with:
      | Enabled Localizations | [Chinese (China), English (United States)] |
      | Default Localization  | Chinese (China)                            |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I click My Configuration in user menu
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use Organization" for "Default Localization" field
    And I fill form with:
      | Default Localization | Chinese (China) |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check order`s dates formatting
    Given I go to Sales/Orders
    And I should see following grid containing rows:
       | Order Number | DNSLT        |
       | SimpleOrder  | 2022年9月26日 |
