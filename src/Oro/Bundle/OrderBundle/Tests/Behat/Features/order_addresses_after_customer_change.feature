@regression
@feature-BB-25388

Feature: Order addresses after customer change
  In order to ensure address consistency in orders
  As an Administrator
  I should be able to change the customer of a customer user
  without affecting addresses in existing orders

  Scenario: Admin login
    Given I login as administrator

  Scenario Outline: Create new customer
    Given I go to Customers/Customers
    When I click "Create Customer"
    And fill "Customer Form" with:
      | Name | <Name> |
    And save and close form
    Then I should see "Customer has been saved" flash message

    Examples:
      | Name          |
      | ORO Customer  |
      | ACME Customer |

  Scenario Outline: Register new customer user
    Given I go to Customers/Customer Users
    When I click "Create Customer User"
    And fill form with:
      | First Name    | <First Name>    |
      | Last Name     | <Last Name>     |
      | Email Address | <Email Address> |
    And I focus on "Birthday" field
    And click "Today"
    And fill form with:
      | Password           | Password123 |
      | Confirm Password   | Password123 |
      | Customer           | <Customer>  |
      | Buyer (Predefined) | true        |
    And fill "Customer User Addresses Form" with:
      | Primary          | true             |
      | First Name Add   | <First Name>     |
      | Last Name Add    | <Last Name>      |
      | Organization     | ORO              |
      | Country          | United States    |
      | Street           | <Address Street> |
      | City             | San Francisco    |
      | State            | California       |
      | Zip/Postal Code  | 90001            |
      | Billing          | true             |
      | Shipping         | true             |
      | Default Billing  | true             |
      | Default Shipping | true             |
    And save and close form
    Then I should see "Customer User has been saved" flash message

    Examples:
      | First Name | Last Name     | Email Address    | Customer      | Address Street |
      | ORO        | Customer User | oro@example.com  | ORO Customer  | Market St. 12  |
      | ACME       | Customer User | acme@example.com | ACME Customer | Market St. 13  |

  Scenario: Create Order
    Given I go to Sales/Orders
    When I click "Create Order"
    And click "Add Product"
    And click on "Free Form Entry 0"
    And fill "Order Form" with:
      | Customer         | ACME Customer       |
      | Customer User    | ACME Customer User  |
      | FreeProductSku   | ORO_PRODUCT_SKU     |
      | FreeProduct0     | ORO_PRODUCT_0       |
      | Quantity0        | 1                   |
      | Price0           | 100                 |
      | Shipping Address | Enter other address |
      | Billing Address  | Enter other address |
    And save and close form
    Then I should see "Review Shipping Cost"
    When I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Change customer of customer user
    Given I go to Customers/Customer Users
    When I click Edit ACME Customer User in grid
    And fill form with:
      | Customer | ORO Customer |
    And save and close form
    Then I should see "Customer User has been saved" flash message

  Scenario: Check order addresses after change customer user
    Given I go to Sales/Orders
    And there is one record in grid
    When I click "Edit" on first row in grid
    And fill "Order Form" with:
      | Customer      | ORO Customer      |
      | Customer User | ORO Customer User |
    And save and close form
    Then I should see "Review Shipping Cost"
    When I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    And should see Order with:
      | Customer         | ORO Customer                                                   |
      | Customer User    | ORO Customer User                                              |
      | Billing Address  | ACME Customer User ORO Market St. 13 SAN FRANCISCO CA US 90001 |
      | Shipping Address | ACME Customer User ORO Market St. 13 SAN FRANCISCO CA US 90001 |
