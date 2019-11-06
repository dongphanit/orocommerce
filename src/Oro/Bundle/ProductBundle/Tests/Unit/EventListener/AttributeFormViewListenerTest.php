<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\AttributeGroupStub;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\AttributeFormViewListener;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class AttributeFormViewListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var Environment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $environment;

    /**
     * @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $attributeManager;

    /**
     * @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityConfigProvider;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translator;

    /**
     * @var AttributeFormViewListener
     */
    private $listener;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->environment = $this->createMock(Environment::class);
        $this->attributeManager = $this->createMock(AttributeManager::class);

        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with(Product::class, 'wysiwyg')
            ->willReturn(new Config(
                new FieldConfigId('attachment', Product::class, 'wysiwyg', WYSIWYGType::TYPE),
                ['label' => 'wysiwyg field label']
            ));

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->with('wysiwyg field label')
            ->willReturn('translated wysiwyg field label');

        $this->listener = new AttributeFormViewListener(
            $this->attributeManager,
            $this->entityConfigProvider,
            $this->translator
        );
    }

    /**
     * @dataProvider viewListDataProvider
     * @param array $groupsData
     * @param array $scrollData
     * @param string $templateHtml
     * @param array $expectedData
     */
    public function testViewList(
        array $groupsData,
        array $scrollData,
        $templateHtml,
        array $expectedData
    ) {
        $entity = $this->getEntity(TestActivityTarget::class, [
            'attributeFamily' => $this->getEntity(AttributeFamily::class),
        ]);

        $this->environment
            ->expects($templateHtml ? $this->once() : $this->never())
            ->method('render')
            ->willReturn($templateHtml);

        $this->attributeManager
            ->expects($this->once())
            ->method('getGroupsWithAttributes')
            ->willReturn($groupsData);

        $scrollData = new ScrollData($scrollData);
        $listEvent = new BeforeListRenderEvent($this->environment, $scrollData, $entity);
        $this->listener->onViewList($listEvent);

        $this->assertEquals($expectedData, $listEvent->getScrollData()->getData());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function viewListDataProvider()
    {
        $label = $this->getEntity(LocalizedFallbackValue::class, ['string' => 'Group1Title']);
        $group1 = $this->getEntity(AttributeGroupStub::class, ['code' => 'group1', 'label' => $label]);

        $attributeVisible = $this->getEntity(
            FieldConfigModel::class,
            [
                'id' => 1,
                'fieldName' => 'someField',
                'data' => [
                    'view' => ['is_displayable' => true],
                    'form' => ['is_enabled' => true]
                ]
            ]
        );

        $inventoryStatus = $this->getEntity(FieldConfigModel::class, ['id' => 1, 'fieldName' => 'inventory_status']);
        $images = $this->getEntity(FieldConfigModel::class, ['id' => 1, 'fieldName' => 'images']);
        $productPriceAttributesPrices =
            $this->getEntity(FieldConfigModel::class, ['id' => 1, 'fieldName' => 'productPriceAttributesPrices']);
        $shortDescription = $this->getEntity(FieldConfigModel::class, ['id' => 1, 'fieldName' => 'shortDescriptions']);
        $descriptions = $this->getEntity(FieldConfigModel::class, ['id' => 1, 'fieldName' => 'descriptions']);
        $wysiwyg = $this->getEntity(
            FieldConfigModel::class,
            ['id' => 1, 'fieldName' => 'wysiwyg', 'type' => WYSIWYGType::TYPE, 'data' => [
                'view' => ['is_displayable' => true],
                'form' => ['is_enabled' => true]
            ]]
        );

        return [
            'move attribute field to other group not allowed (inventory_status)' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$inventoryStatus]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'inventory_status' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'templateHtml' => false,
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'inventory_status' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'move attribute field to other group not allowed (images)' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$images]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'images' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'templateHtml' => false,
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'images' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'move attribute field to other group not allowed (productPriceAttributesPrices)' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$productPriceAttributesPrices]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'productPriceAttributesPrices' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'templateHtml' => false,
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'productPriceAttributesPrices' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'move attribute field to other group not allowed (shortDescriptions)' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$shortDescription]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'shortDescriptions' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'templateHtml' => false,
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'shortDescriptions' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'move attribute field to other group not allowed (descriptions)' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$descriptions]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'descriptions' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'templateHtml' => false,
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'descriptions' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'move attribute field to other group' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$attributeVisible]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'templateHtml' => false,
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                        'group1' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => ['someField' => 'field template'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'move wysiwyg attribute field to own group' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$wysiwyg]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'templateHtml' => 'expected template data',
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template'
                                    ],
                                ],
                            ],
                        ],
                        'wysiwyg' => [
                            'title' => 'translated wysiwyg field label',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'wysiwyg' => 'expected template data'
                                    ],
                                ],
                            ],
                            'priority' => 501,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider editDataProvider
     *
     * @param array $groupsData
     * @param array $scrollData
     * @param string $fieldName
     * @param string $templateHtml
     * @param array $expectedData
     */
    public function testOnEdit(
        array $groupsData,
        array $scrollData,
        string $fieldName,
        $templateHtml,
        array $expectedData
    ) {
        $entity = $this->getEntity(TestActivityTarget::class, [
            'attributeFamily' => $this->getEntity(AttributeFamily::class),
        ]);

        $this->environment
            ->expects($templateHtml ? $this->once() : $this->never())
            ->method('render')
            ->willReturn($templateHtml);

        $this->attributeManager
            ->expects($this->once())
            ->method('getGroupsWithAttributes')
            ->willReturn($groupsData);

        $scrollData = new ScrollData($scrollData);

        $attributeView = new FormView();
        if (!$templateHtml) {
            $attributeView->setRendered();
        }

        $formView = new FormView();
        $formView->children[$fieldName] = $attributeView;

        $listEvent = new BeforeListRenderEvent($this->environment, $scrollData, $entity, $formView);
        $this->listener->onEdit($listEvent);

        $this->assertEquals($expectedData, $listEvent->getScrollData()->getData());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function editDataProvider()
    {
        $label = $this->getEntity(LocalizedFallbackValue::class, ['string' => 'Group1Title']);
        $group1 = $this->getEntity(AttributeGroupStub::class, ['code' => 'group1', 'label' => $label]);

        $attributeVisible = $this->getEntity(
            FieldConfigModel::class,
            [
                'id' => 1,
                'fieldName' => 'someField',
                'data' => [
                    'view' => ['is_displayable' => true],
                    'form' => ['is_enabled' => true]
                ]
            ]
        );

        $inventoryStatus = $this->getEntity(FieldConfigModel::class, ['id' => 1, 'fieldName' => 'inventory_status']);
        $images = $this->getEntity(FieldConfigModel::class, ['id' => 1, 'fieldName' => 'images']);
        $productPriceAttributesPrices =
            $this->getEntity(FieldConfigModel::class, ['id' => 1, 'fieldName' => 'productPriceAttributesPrices']);
        $wysiwyg = $this->getEntity(
            FieldConfigModel::class,
            ['id' => 1, 'fieldName' => 'wysiwyg', 'type' => WYSIWYGType::TYPE, 'data' => [
                'view' => ['is_displayable' => true],
                'form' => ['is_enabled' => true]
            ]]
        );

        return [
            'move attribute field to other group not allowed (inventory_status)' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$inventoryStatus]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'inventory_status' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'fieldName' => 'inventory_status',
                'templateHtml' => false,
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'inventory_status' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'move attribute field to other group not allowed (images)' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$images]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'images' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'fieldName' => 'images',
                'templateHtml' => false,
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'images' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'move attribute field to other group not allowed (productPriceAttributesPrices)' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$productPriceAttributesPrices]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'productPriceAttributesPrices' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'fieldName' => 'productPriceAttributesPrices',
                'templateHtml' => false,
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'productPriceAttributesPrices' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'move attribute field to other group' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$attributeVisible]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'fieldName' => 'someField',
                'templateHtml' => false,
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                        'group1' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template'
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'move wysiwyg attribute field to own group' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$wysiwyg]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'fieldName' => 'wysiwyg',
                'templateHtml' => 'expected template data',
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template'
                                    ],
                                ],
                            ],
                        ],
                        'wysiwyg' => [
                            'title' => 'translated wysiwyg field label',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'wysiwyg' => 'expected template data'
                                    ],
                                ],
                            ],
                            'priority' => 501,
                        ],
                    ],
                ],
            ],
        ];
    }
}
