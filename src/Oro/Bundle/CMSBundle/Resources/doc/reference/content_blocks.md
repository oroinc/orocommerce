Content Blocks
-----

In order to modify some predefined marketing content on the store frontend
an Administrator can edit the defined content blocks.

`ContentBlock` entity fields:
- `alias`, a unique identifier that can be used in the layout to [render block](#render-content-block-in-the-layout).
- `scopes` a collection of scopes that define in what situations this content block should be displayed 
([ScopeBundle documentation](https://github.com/orocrm/platform/blob/master/src/Oro/Bundle/ScopeBundle/README.md)).
- `titles` localized block title that can be rendered with scope
- `contentVariants` a collection of `TextContentVariant` entities. Each of Content Variant can have scopes that define 
when it should be rendered. Only one content variant with the most suitable scope will be rendered at the same time. 
If there is no suitable content variants the default one will be rendered.

 
### Manage content blocks

In **Marketing>Content Blocks** Administrator can edit the defined content blocks.

### Create content block 

Developer can create content blocks with collection of predefined content variants using data migrations:

```php
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

class LoadHomePageSlider extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $slider = new ContentBlock();
        $slider->setAlias('marketing-block');

        $title = new LocalizedFallbackValue();
        $title->setString('Block title');
        $slider->addTitle($title);

        $variant = new TextContentVariant();
        $variant->setDefault(true);
        $variant->setContent('<p>Block content</p>');
        $slider->addContentVariant($variant);

        $manager->persist($slider);
        $manager->flush($slider);
    }
}
```

### Render content block in the layout

Content blocks can be rendered by unique `alias` using `content_block` block type:
 
```yaml
layout:
    actions:
        - @add:
            id: marketing_block # unique layout block id
            parentId: page_content
            blockType: content_block
            options:
                alias: marketing-block # unique content block id
```
**Notice**
Administrator can rename or delete defined content blocks, so if there is no content block with defined alias (typo in block name or block doesn't exist) nothing will be rendered, no errors will be displayed, `notice` level message will be written to log.

If you rendered content block to layout but nothing displayed check that:
 - content block is enabled
 - content block have at least one suitable scope or doesn't have scopes at all (that means block should be rendered without any restriction).
