<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\CMSBundle\Attribute\Type\WYSIWYGAttributeType;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type\AttributeTypeTestCase;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

class WYSIWYGAttributeTypeTest extends AttributeTypeTestCase
{
    /** @var HtmlTagHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $htmlTagHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $this->htmlTagHelper->expects($this->any())
            ->method('stripTags')
            ->willReturnCallback(
                function ($value) {
                    return $value . ' stripped';
                }
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function getAttributeType()
    {
        return new WYSIWYGAttributeType($this->htmlTagHelper);
    }

    public function testGetType()
    {
        $this->assertEquals('wysiwyg', $this->getAttributeType()->getType());
    }

    /**
     * {@inheritdoc}
     */
    public function configurationMethodsDataProvider()
    {
        yield [
            'isSearchable' => true,
            'isFilterable' => true,
            'isSortable' => false
        ];
    }

    public function testGetSearchableValue()
    {
        $this->assertSame(
            'text stripped',
            $this->getAttributeType()->getSearchableValue($this->attribute, 'text', $this->localization)
        );
    }

    public function testGetFilterableValue()
    {
        $this->assertSame(
            'text stripped',
            $this->getAttributeType()->getFilterableValue($this->attribute, 'text', $this->localization)
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not supported
     */
    public function testGetSortableValue()
    {
        $this->getAttributeType()->getSortableValue($this->attribute, 'text', $this->localization);
    }
}
