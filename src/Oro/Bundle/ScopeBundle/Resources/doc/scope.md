Scopes
------
Scopes is a functionality that allows to restrict data.
The goal is to easily find an object for current request, and don't pay a lot attention on  what fields it should be filtered.
Break relations between target entity and entities that are using to search by.
Any Bundle can affect on any scope. To extend scope should be created instance of AbstractScopeCriteriaProvider and registered with tag oro_scope.provider.
One CriteriaScopeProvider can be used in many scope types.

* [Scope Manager](#scope-manager)
* [Criteria](#criteria)
* [Example with related scopes](#example-with-related-scopes)
* [Example with criteria](#example-with-criteria)

ScopeManager call each ScopeCriteriaProvider that was registered on given scope type to get part of Criteria.
ScopeProvider's calls according to priority, this will allow to fetch the most detailed Scope.
Scope entity can be extended by any Bundle to provide new scope type. 

Scope Manager:
--------
ScopeManager is a service that helps to find correct Scope or create a new one.
Also ScopeCriteria can be created by this manager.

####Example of provider
It says that for current Scope we will use current Account.
In case if we need to get Scope by context it will get data from key\field account. 
This is default behavior of AbstractScopeCriteriaProvider
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
        - { name: oro_scope.provider, scopeType: web_content, priority: 30 }
```
####Context
Context can be `array or object`, ScopeCriteriaProvider can get data by defined `key\field name`, to create criteria.
In case when `Context is null`, should be used current data, for example: `current User, Website etc`

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
```
$scopeManager->findRelatedScopes($scopeType, $context = null);
```

Criteria
--------
Criteria can help to make correct join on scope and apply conditions according context.

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
|5||1|1|
|6||1||

In order to fetch all scopes by account findRelatedScopes should be called. 
At this moment we don't know what other fields\provider are participating in scope type. 
```
$context = ['account' => 1];
$scopeManager->findRelatedScopes('web_content', $context) 
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

Example with criteria
---------------------
Goal: find entity(Slug) related to most prioritized scope
Example data: `Slug` has `ManyToMany` relation with `Scope`
Example of service.yml:
```
oro_customer.account_scope_criteria_provider:
    class: 'Oro\Bundle\CustomerBundle\Provider\ScopeAccountCriteriaProvider'
    tags:
        - { name: oro_scope.provider, scopeType: web_content, priority: 300 }
        
oro_customer.account_group_scope_criteria_provider:
    class: 'Oro\Bundle\CustomerBundle\Provider\ScopeAccountGroupCriteriaProvider'
    tags:
        - { name: oro_scope.provider, scopeType: web_content, priority: 200 }
        
```
Code example:
```
$qb->select('slug')
    ->from(Slug::class, 'slug')
    ->join('slug.scopes', 'scopes', Join::WITH)
    ->where($qb->expr()->eq('slug.url', ':url'))
    ->setParameter('url', $slugUrl)
    ->setMaxResults(1);

$scopeCriteria = $this->scopeManager->getCriteria('web_content');
$scopeCriteria->applyToJoinWithPriority($qb, 'scopes'); 
```
Because of we don't use context, all values are set for current scope.
Let's say we are logged in under Account(1). The Account(1) has AccountGroup(1).
In this case scope priority will be calculated as:

|id|account_id|accountGroup|
|---|---|---|
|4|1||
|6||1|

Query that will be executed:
```
SELECT slug.*
FROM oro_redirect_slug slug
INNER JOIN oro_slug_scope slug_to_scope ON slug.id = slug_to_scope.slug_id
INNER JOIN oro_scope scope ON scope.id = slug_to_scope.scope_id 
    AND (
        (scope.account_id = 1 OR scope.account_id IS NULL) 
        AND (scope.accountGroup_id = 1 OR scope.accountGroup_id IS NULL) 
        AND (scope.website_id IS NULL)
    )
WHERE slug.url = :url
ORDER BY scope.account_id DESC, scope.accountGroup_id DESC
LIMIT 1;
```

If we will add `WebsiteBundle`, and register `new scope provider` to scope type `web_content`
```
oro_website.website_scope_criteria_provider:
    class: 'Oro\Bundle\WebsiteBundle\Provider\ScopeCriteriaProvider'
    tags:
        - { name: oro_scope.provider, scopeType: web_content, priority: 100 }
```

And request will be made at Website(1)
In this case scope priority will be calculated as:

|id|account_id|accountGroup|website_id|
|---|---|---|---|
|1|1||1|
|4|1|||
|5||1|1|
|6||1||

Query that will be executed:
```
SELECT slug.*
FROM oro_redirect_slug slug
INNER JOIN oro_slug_scope slug_to_scope ON slug.id = slug_to_scope.slug_id
INNER JOIN oro_scope scope ON scope.id = slug_to_scope.scope_id 
    AND (
        (scope.account_id = 1 OR scope.account_id IS NULL) 
        AND (scope.accountGroup_id = 1 OR scope.accountGroup_id IS NULL) 
        AND (scope.website_id 1 OR scope.website_id IS NULL)
    )
WHERE slug.url = :url 
ORDER BY scope.account_id DESC, scope.accountGroup_id DESC, scope.website_id DESC
LIMIT 1;'
```
