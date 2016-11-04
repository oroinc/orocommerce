Scopes
------

Scope in Oro applications arms you with additional abstraction layer and helps you get the missing information about the execution context in a standard and controllable way. With a scope approach your bundle can launch an alternative behavior and modify displayed data based on the information from the scope that indirectly matches current execution context.

For working example of using scopes in Oro application, please check out the *VisibilityBundle* and *AccountBundle* code.

* [How Scopes work](#how-scopes-work)
    * [Scope Manager](#scope-manager)
    * [Scope Repository](#scope-repository)
    * [Scope Criteria Providers](#scope-criteria-providers)
    * [Scope Type](#scope-type)
    * [Scope Model](#scope-model)
* [Configuring Scope Criteria Providers](#configuring-scope-criteria-providers)
* [Using Context](#using-context)
* [Scope Operations](#scope-operations)
* [Example: Using related scopes](#example-using-related-scopes)
* [Example: Using criteria](#example-with-criteria)

How Scopes work
---------------
Sometimes in a bundle activities, you need to alter behavior or data based on the set of criteria that the bundle is not able to evaluate. Scope Manager gets you the missing details by polling dedicated Scope Criteria Providers. In the scope-consuming bundle, you can request information using one of the `Scope operations`_. As a first parameter, you usually pass the scope type (e.g. web_content in the following examples). Scope type helps Scope Manager find the scope-provider bundles who can deliver the information your bundle is missing. As a second parameter, you usually pass the context - information available to your bundle that is used as a scope filtering criteria. **Note:** Scope Manager evaluates the priority of the Scope Criteria Providers who are registered to deliver information for the requested scope type and scope criteria, and polls the provider with the highest priority. 

Scope Manager
-------------
Scope Manager is a service that provides an interface for collecting the scope items in Oro application. It is in charge of the following functions:
* Expose scope-related operations (find, findOrCreate, findDefaultScope, findRelatedScopes) to the scope-aware bundles and deliver requested scope(s) as a result. See [Scope Operations](#scope-operations) for more information.
* Create a collected scope in response to the findOrCreate operation (if the scope is not found).
* Provide a getScope() feature for the scope-aware bundles. **(need more infrmation here)**
* Call Scope Criteria Provider's getCriteriaForCurrentScope() method to get a portion of the scope information.

Scope Repository
----------------
Scope Repository stores the scope instances created in Scope Manager using *findOrCreate* method. 

Scope Criteria Providers
------------------------
Scope Criteria Provider is a service that calculates the value for the scope criteria based on the provided context. Scope criteria helps to model a relationship between the scope and the scope-consuming context. In any bundle, you can create a [Scope Criteria Provider](#configuring-scope-criteria-providers) service and register it as scope provider for the specific scope type. This service shall deliver the scope criteria value to the Scope Manager, who, in turn, use the scope criteria to filter the scope instances or find the one matching to the provided context.

Scope Type
----------
Scope Type is a tag that groups scope criteria that are used by particular scope consumers. One scope type may be reused by multiple scope consumers. It may happen, that a particular scope criteria provider, like the one for Account Group, is not involved in the scope construction because it serves the scope-consumers with the different scope type (e.g. web_content). In this case, Scope Manager looks for the scope(s) that do(es) not prompt to evaluate this criteria. 

Scope Model
-----------
Scope model is a data structure for storing scope items. Every scope item has fields for every scope criteria registered by the scope criteria provider services. When the scope criteria is not involved in the scope (based on the scope type), the value of the field is NULL.

|scope id|scope criteria 1 (account id)|scope criteria 2 (website_id)| ... | scope criteria N (locale_id)|
|---|---|---|---|---|
|1|1||1|1|
|1|2||2|1|

Add Scope Criteria
------------------
To add a criteria to the scope, run the following sql query that adds a new column to the ... table. Replace *Criteria* with a unique criteria name:
```
```

Configuring Scope Criteria Providers
------------------------------------
To extend a scope with criteria that is provided by your bundle:
1. Create a **Scope<your bundle>CriteriaProvider** class and implement getCriteriaForCurrentScope() and getCriteriaField() methods, as shown in the following examples. Return an array of key/value structures in getCriteriaForCurrentScope(). Return a criteria id in getCriteriaField(). 

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
2. In <your bundle>/Resources/config/service.yml, registered the newly created *<bundle>_scope_criteria_provider* with *oro_scope.provider* tag, like in the following example:

```
oro_customer.account_scope_criteria_provider:
    class: 'Oro\Bundle\CustomerBundle\Provider\ScopeAccountCriteriaProvider'
    tags:
        - { name: oro_scope.provider, scopeType: web_content, priority: 30 }
```
**Note:** One CriteriaScopeProvider can be used in many scope types.

Using Context
-------------

**TODO**
Context can be `array or object`, ScopeCriteriaProvider can get data by defined `key\field name`, to create criteria.

Scope Operations
----------------
Scope Manager exposes the following operations for the scope-consuming bundles:

Find scope by context (when the context is provided), or
find Scope by current data (when context is NULL)
```
$scopeManager->find($scopeType, $context = null)         
```

Find scope or create a new one if it is not found
```
$scopeManager->findOrCreate($scopeType, $context = null) 
```

Get the default scope (returns a scope with empty scope criteria)
```
$scopeManager->findDefaultScope() 
```

Get all scopes that match given context. When some scope criteria are not provided in context, the scopes are filtered by the available criteria.
```
$scopeManager->findRelatedScopes($scopeType, $context = null);
```

Example: Using related scopes
-----------------------------
For example, let's create the following scope criteria providers and register them for the *web_content* scope type. 

* ScopeAccountCriteriaProvider

* ScopeWebsiteCriteriaProvider

**Note:** The third ScopeAccountGroupCriteriaProvider is NOT involved in the scope type, so the scope will be filtered to have no AccountGroup criteria defined. 

The scope model has tree fields:
```
class Scope 
{
    protected $account;
    protected $accountGroup;
    protected $website;
    ...
}
```
and the scopes created in Scope Repository are as follows:

|id|account_id|accountGroup|website_id|
|---|---|---|---|
|1|1||1|
|2|2||1|
|3|1||2|
|4|1|||
|5||1|1|
|6||1||

In order to fetch all scopes that match account with id equal to 1, you can use findRelatedScopes and pass *web_content* and 'account'=>1 in the parameters.
```
$context = ['account' => 1];
$scopeManager->findRelatedScopes('web_content', $context) 
```
We may or may not know what are other scope criteria are available with this scope type, but the Scope Manager fills in the blanks and adds *criteria IS NOT NULL* condition for any scope criteria we do not have in context. For our example, the Scope Manager's query looks like: 
```
WHERE account_id = 1 AND website_id IS NOT NULL AND accountGroup_id IS NULL;
```
where:
* **account_id** - is given in the context parameter,
* **website_id** - is not given, but is required based on the scope type, and
* **accountGroup_id** - should be missing (NULL) in the scope, as it does not participate in the scope type.

The resulting scopes delivered by Scope Manager are:

|id|account_id|accountGroup|website_id|
|---|---|---|---|
|1|1||1|
|3|1||2|

Example: Using criteria
-----------------------

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

The following query is executed:
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
 **Note:** ScopeProviders calls according to priority, this will allow to fetch the most detailed Scope.*
Scope-aware Bundle(searches for scopes (using search criteria), requests scope creation, alters behaviour or displayed data based on the obtained scope values)

Build a scope model in the scope repository using information about the scope types registered by scope providers of the application bundles (see [Scope Criteria Providers](#scope-criteria-providers)). Note: Basically, bundles that register new scope type extend the core Scope entity implementation.


