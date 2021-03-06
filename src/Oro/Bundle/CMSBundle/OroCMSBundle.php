<?php

namespace Oro\Bundle\CMSBundle;

use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\AttributeBlockTypeMapperPass;
use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\EntityExtendFieldTypePass;
use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\ExtendFieldValidationLoaderPass;
use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\LayoutManagerPass;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * OroCMS bundle class.
 */
class OroCMSBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container
            ->addCompilerPass(new EntityExtendFieldTypePass())
            ->addCompilerPass(new ExtendFieldValidationLoaderPass())
            ->addCompilerPass(new AttributeBlockTypeMapperPass())
            ->addCompilerPass(new LayoutManagerPass())
            ->addCompilerPass(new DefaultFallbackExtensionPass([
                Page::class => [
                    'slugPrototype' => 'slugPrototypes',
                    'title' => 'titles'
                ],
                ContentBlock::class => [
                    'title' => 'titles'
                ]
            ]));
    }
}
