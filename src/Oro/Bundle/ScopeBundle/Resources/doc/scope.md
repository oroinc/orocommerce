Scopes
------
Scopes is a functionality that allows to restrict data to application is working with.
The goal is to easily find an object for current request, and don't pay a lot attention on  what fields it should be filtered.
Break relations between target entity and entities that are using to search by.
Any Bundle can affect on any scope. To extend scope should be created instance of AbstractScopeCriteriaProvider and registered with tag oro_scope.provider.
One CriteriaScopeProvider can be used in many scope types.

* [Scope Manager](#scope-manager)
* [Criteria](#criteria)
* [Form based on Scope](#form)
* [Example with related scopes](#example-related-scopes)
* [Example with criteria](#example-criteria)

ScopeManager call each ScopeCriteriaProvider that was registered on given scope type to get part of Criteria.
ScopeProvider's calls according to priority, this will allow to fetch the most detailed Scope.
Scope entity can be extended by any Bundle to provide new scope type. 

Scope Manager:
--------
ScopeManager is a service that helps to find correct Scope or create a new one.
Also ScopeCriteria can be created by this manager.

####Example of provider
It says that for current Scope we will use current Account.
In case if we need to get Scope by context it will looking up for data from key\field 'account'.
```
class ScopeAccountCriteriaProvider extends AbstractScopeCriteriaProvider
{
    ...
    /**
     * @return array
     */
    public function getCriteriaForCurrentScope()
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return [];
        }
        $loggedUser = $token->getUser();
        if (null !== $loggedUser && $loggedUser instanceof AccountUser) {
            return [self::ACCOUNT => $loggedUser->getAccount()];
        }

        return [];
    } 
      
    /**
     * @return string
     */
    public function getCriteriaField()
    {
        return static::ACCOUNT;
    }
}
```
service.yml:
```
oro_customer.account_scope_criteria_provider:
    class: 'Oro\Bundle\CustomerBundle\Provider\ScopeAccountCriteriaProvider'
    tags:
        - { name: oro_scope.provider, scopeType: test_scope_type, priority: 30 }
```
####Context
Context can be array or object, ScopeCriteriaProvider can get data by defined key\field name, to create criteria.
In case when Context is null, should be used current data, for example: current User, Website etc

Find Scope by context or current Scope
```
$scopeManager->find($scopeType, $context = null)         
```

Find Scope or create if it's not exists
```
$scopeManager->findOrCreate($scopeType, $context = null) 
```

Returns Scope with all empty fields
```
$scopeManager->findDefaultScope() 
```

Get iterator of scopes that were found by given context. Context can contains not all required data, in this case scope will be filtered by given parameters.

Criteria
--------
Criteria can help you to make correct join on scope and apply conditions according context.

Example with related scopes
-------------------------------------------------------------------------------------------------------------------------------------------------------------

For example there are 2 providers registered on scope type "test_scope_type"

* ScopeAccountCriteriaProvider
* ScopeWebsiteCriteriaProvider

Scope has tree fields:
```
class Scope 
{
    protected $account;
    protected $accountGroup;
    protected $website;
    ...
}
```
with data:

|id|account_id|accountGroup|website_id|
|---|---|---|---|
|1|1||1|
|2|2||1|
|3|1||2|
|4|1|||
|5||1||

In order to fetch all scopes by account findRelatedScopes should be called. 
At this moment we don't know what other fields\provider are participating in scope type. 
```
$context = ['account' => 1];
$scopeManager->findRelatedScopes('test_scope_type', $context) 
```
In this case query will looks like: 
```
WHERE account_id = 1 AND website_id IS NOT NULL AND accountGroup_id IS NULL;
```

* **account_id** - given from Context
* **website_id** - not given but required field for our scope type
* **accountGroup_id** - doesn't participate in our scope type

In this case result will be:

|id|account_id|accountGroup|website_id|
|---|---|---|---|
|1|1||1|
|3|1||2|

