Testing
=======

* [WebsiteSearchExtensionTrait](websitesearchextensiontrait)

### WebsiteSearchExtensionTrait

Trait [Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait](../../Tests/Functional/WebsiteSearchExtensionTrait.php)

This trait contains methods which help reindex data in test if required.

Example of usage:

```php
/**
 * @dbIsolationPerTest
 */
class ReindexRequiredTest extends FrontendWebTestCase
{
    use WebsiteSearchExtensionTrait;

     /** {@inheritdoc} */
        protected function setUp(): void
        {
            ...

            $this->reindexProductData(); // if we need re-index product data in every test
        }

        public function testExampleReindexData()
        {
            $this->reindexProductData(); // if we need re-index product data in specific test
            ...
        }
}


```
