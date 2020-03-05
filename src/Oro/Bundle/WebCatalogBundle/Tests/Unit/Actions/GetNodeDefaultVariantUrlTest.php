<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Acl\Actions;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\WebCatalogBundle\Actions\GetNodeDefaultVariantUrl;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Exception\ActionException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class GetNodeDefaultVariantUrlTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ContextAccessor
     */
    private $contextAccessor;

    /**
     * @var CanonicalUrlGenerator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $canonicalUrlGenerator;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventDispatcher;

    /**
     * @var GetNodeDefaultVariantUrl
     */
    private $action;

    protected function setUp(): void
    {
        $this->contextAccessor = new ContextAccessor();
        $this->canonicalUrlGenerator = $this->createMock(CanonicalUrlGenerator::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->action = new GetNodeDefaultVariantUrl($this->contextAccessor, $this->canonicalUrlGenerator);

        $this->action->setDispatcher($this->eventDispatcher);
    }

    public function testExecuteException()
    {
        $context = new ActionData([
            'content_node' => null
        ]);

        $this->expectException(ActionException::class);
        $this->expectExceptionMessage('Content node is empty');

        $this->action->initialize([
            'content_node' =>  new PropertyPath('content_node'),
            'attribute' => new PropertyPath('attribute')
        ]);

        $this->action->execute($context);
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param string $slugUrl
     * @param string $absoluteUrl
     * @param array $contentVariants
     */
    public function testExecute(
        string $slugUrl,
        string $absoluteUrl,
        array $contentVariants
    ): void {
        /** @var ContentNode $contentNode */
        $contentNode = $this->getEntity(ContentNode::class, ['id' => 123]);
        foreach ($contentVariants as $contentVariant) {
            $contentNode->addContentVariant($contentVariant);
        }

        $this->canonicalUrlGenerator->expects($this->once())
            ->method('getAbsoluteUrl')
            ->with($slugUrl)
            ->willReturn($absoluteUrl);

        $context = new ActionData([
            'content_node' => $contentNode
        ]);

        $this->action->initialize([
            'content_node' =>  new PropertyPath('content_node'),
            'attribute' => new PropertyPath('attribute')
        ]);
        $this->action->execute($context);

        $this->assertSame($absoluteUrl, $context->get('attribute'));
    }

    public function executeDataProvider()
    {
        $slugUrl = '/test-url';
        $absoluteUrl = 'http://test.com/test-url';

        $localizedSlug = new Slug();
        $localizedSlug->setUrl($slugUrl);

        return [
            'without default variant slug' => [
                'slugUrl' => $slugUrl,
                'absoluteUrl' => $absoluteUrl,
                'contentVariants' => [
                    (new ContentVariant())->setType('system_page')
                        ->setSystemPageRoute('some_route')
                        ->setDefault(true),
                    (new ContentVariant())->setType('system_page')
                        ->setSystemPageRoute('some_route')
                        ->addSlug($localizedSlug)
                        ->setDefault(false)
                ]
            ],
            'with default variant slug' => [
                'slugUrl' => $slugUrl,
                'absoluteUrl' => $absoluteUrl,
                'contentVariants' => [
                    (new ContentVariant())->setType('system_page')
                        ->setSystemPageRoute('some_route')
                        ->addSlug($localizedSlug)
                        ->setDefault(true)
                ]
            ],
        ];
    }

    public function testInitializeException(): void
    {
        $this->expectExceptionMessage('The required options "attribute", "content_node" are missing.');
        $this->action->initialize([]);
    }

    public function testInitialize(): void
    {
        $options = [
            'content_node' =>  new PropertyPath('content_node'),
            'attribute' => new PropertyPath('attribute')
        ];

        $this->assertInstanceOf(ActionInterface::class, $this->action->initialize($options));
    }
}
