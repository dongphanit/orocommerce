<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtension;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroCMSBundleInstaller implements
    Installation,
    AttachmentExtensionAwareInterface,
    ExtendExtensionAwareInterface,
    SlugExtensionAwareInterface
{
    use AttachmentExtensionAwareTrait;

    const CMS_LOGIN_PAGE_TABLE = 'oro_cms_login_page';
    const MAX_LOGO_IMAGE_SIZE_IN_MB = 10;
    const MAX_BACKGROUND_IMAGE_SIZE_IN_MB = 10;
    const MAX_IMAGE_SLIDE_MAIN_IMAGE_SIZE_IN_MB = 10;
    const MAX_IMAGE_SLIDE_MEDIUM_IMAGE_SIZE_IN_MB = 10;
    const MAX_IMAGE_SLIDE_SMALL_IMAGE_SIZE_IN_MB = 10;

    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

    /**
     * @var SlugExtension
     */
    protected $slugExtension;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_8';
    }

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setSlugExtension(SlugExtension $extension)
    {
        $this->slugExtension = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroCmsPageTable($schema);
        $this->createOroCmsPageSlugTable($schema);
        $this->createOroCmsPageSlugPrototypeTable($schema);
        $this->createOroCmsPageTitleTable($schema);
        $this->createOroCmsLoginPageTable($schema);
        $this->createOroCmsContentBlockTable($schema);
        $this->createOroCmsContentBlockTitleTable($schema);
        $this->createOroCmsContentBlockScopeTable($schema);
        $this->createOroCmsTextContentVariantTable($schema);
        $this->createOroCmsTextContentVariantScopeTable($schema);
        $this->createOroCmsContentWidgetTable($schema);
        $this->createOroCmsContentWidgetUsageTable($schema);
        $this->createOroCmsImageSlideTable($schema);

        /** Foreign keys generation **/
        $this->addOroCmsPageForeignKeys($schema);
        $this->addOroCmsPageTitleForeignKeys($schema);
        $this->addOroCmsContentBlockTitleForeignKeys($schema);
        $this->addOroCmsContentBlockScopeForeignKeys($schema);
        $this->addOrganizationForeignKeys($schema);
        $this->addOroCmsTextContentVariantForeignKeys($schema);
        $this->addOroCmsTextContentVariantScopeForeignKeys($schema);
        $this->addOroCmsContentWidgetForeignKeys($schema);
        $this->addOroCmsContentWidgetUsageForeignKeys($schema);
        $this->addOroCmsImageSlideForeignKeys($schema);

        /** Associations */
        $this->addOroCmsLoginPageImageAssociations($schema);

        $this->addContentVariantTypes($schema);
        $this->addLocalizedFallbackValueFields($schema);
    }

    /**
     * Create oro_cms_page table
     *
     * @param Schema $schema
     */
    protected function createOroCmsPageTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cms_page');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('content', 'wysiwyg', ['notnull' => false, 'comment' => '(DC2Type:wysiwyg)']);
        $table->addColumn('content_style', 'wysiwyg_style', ['notnull' => false]);
        $table->addColumn('content_properties', 'wysiwyg_properties', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_cms_page foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCmsPageForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_page');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Create oro_cms_login_page table
     *
     * @param Schema $schema
     */
    protected function createOroCmsLoginPageTable(Schema $schema)
    {
        $table = $schema->createTable(self::CMS_LOGIN_PAGE_TABLE);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('top_content', 'text', ['notnull' => false]);
        $table->addColumn('bottom_content', 'text', ['notnull' => false]);
        $table->addColumn('css', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroCmsLoginPageImageAssociations(Schema $schema)
    {
        $options['attachment']['acl_protected'] = false;

        $this->attachmentExtension->addImageRelation(
            $schema,
            self::CMS_LOGIN_PAGE_TABLE,
            'logoImage',
            $options,
            self::MAX_LOGO_IMAGE_SIZE_IN_MB
        );

        $this->attachmentExtension->addImageRelation(
            $schema,
            self::CMS_LOGIN_PAGE_TABLE,
            'backgroundImage',
            $options,
            self::MAX_BACKGROUND_IMAGE_SIZE_IN_MB
        );
    }

    /**
     * Create oro_cms_page_slug table
     *
     * @param Schema $schema
     */
    protected function createOroCmsPageSlugTable(Schema $schema)
    {
        $this->slugExtension->addSlugs(
            $schema,
            'oro_cms_page_to_slug',
            'oro_cms_page',
            'page_id'
        );
    }

    /**
     * Create oro_cms_page_slug_prototype table
     *
     * @param Schema $schema
     */
    protected function createOroCmsPageSlugPrototypeTable(Schema $schema)
    {
        $this->slugExtension->addLocalizedSlugPrototypes(
            $schema,
            'oro_cms_page_slug_prototype',
            'oro_cms_page',
            'page_id'
        );
    }

    /**
     * Create oro_cms_page_title table
     *
     * @param Schema $schema
     */
    protected function createOroCmsPageTitleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cms_page_title');
        $table->addColumn('page_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['page_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Add oro_cms_page_title foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCmsPageTitleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_page_title');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_page'),
            ['page_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    public function addContentVariantTypes(Schema $schema)
    {
        if ($schema->hasTable('oro_web_catalog_variant')) {
            $table = $schema->getTable('oro_web_catalog_variant');

            $this->extendExtension->addManyToOneRelation(
                $schema,
                $table,
                'cms_page',
                'oro_cms_page',
                'id',
                [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                    'entity' => ['label' => 'oro.cms.page.entity_label'],
                    'extend' => [
                        'is_extend' => true,
                        'owner' => ExtendScope::OWNER_CUSTOM,
                        'cascade' => ['persist'],
                        'on_delete' => 'CASCADE',
                    ],
                    'datagrid' => [
                        'is_visible' => false
                    ],
                    'form' => [
                        'is_enabled' => false
                    ],
                    'view' => ['is_displayable' => false],
                    'merge' => ['display' => false],
                    'dataaudit' => ['auditable' => true]
                ]
            );
        }
    }

    /**
     * Create `oro_cms_content_block` table
     *
     * @param Schema $schema
     */
    public function createOroCmsContentBlockTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cms_content_block');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('alias', 'string', ['notnull' => true, 'length' => 100]);
        $table->addColumn('enabled', 'boolean', ['default' => true]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['alias']);
    }

    /**
     * Create `oro_cms_content_block_title` table
     *
     * @param Schema $schema
     */
    protected function createOroCmsContentBlockTitleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cms_content_block_title');
        $table->addColumn('content_block_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['content_block_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Create `oro_cms_content_block_scope` table
     *
     * @param Schema $schema
     */
    protected function createOroCmsContentBlockScopeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cms_content_block_scope');
        $table->addColumn('content_block_id', 'integer', []);
        $table->addColumn('scope_id', 'integer', []);
        $table->setPrimaryKey(['content_block_id', 'scope_id']);
    }

    /**
     * Create oro_cms_content_widget table
     *
     * @param Schema $schema
     */
    private function createOroCmsContentWidgetTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_content_widget');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('widget_type', 'string', ['length' => 255]);
        $table->addColumn('layout', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('settings', 'array');
        $table->addUniqueIndex(['organization_id', 'name'], 'uidx_oro_cms_content_widget');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_cms_content_widget_usage table
     *
     * @param Schema $schema
     */
    private function createOroCmsContentWidgetUsageTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_content_widget_usage');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('content_widget_id', 'integer');
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('entity_id', 'integer');
        $table->addColumn('entity_field', 'string', ['notnull' => false, 'length' => 50]);
        $table->addUniqueIndex(
            ['entity_class', 'entity_id', 'entity_field', 'content_widget_id'],
            'uidx_oro_cms_content_widget_usage'
        );
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_cms_image_slide table
     *
     * @param Schema $schema
     */
    private function createOroCmsImageSlideTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_image_slide');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('content_widget_id', 'integer');
        $table->addColumn('slide_order', 'integer', ['default' => 0]);
        $table->addColumn('url', 'string', ['length' => 255]);
        $table->addColumn('display_in_same_window', 'boolean', ['default' => true]);
        $table->addColumn('title', 'string', ['length' => 255]);
        $table->addColumn('text', 'text', ['notnull' => false]);
        $table->addColumn('text_alignment', 'string', ['length' => 20, 'default' => ImageSlide::TEXT_ALIGNMENT_CENTER]);

        $this->attachmentExtension->addImageRelation(
            $schema,
            'oro_cms_image_slide',
            'mainImage',
            ['attachment' => ['acl_protected' => false, 'use_dam' => true]],
            self::MAX_IMAGE_SLIDE_MAIN_IMAGE_SIZE_IN_MB
        );
        $this->attachmentExtension->addImageRelation(
            $schema,
            'oro_cms_image_slide',
            'mediumImage',
            ['attachment' => ['acl_protected' => false, 'use_dam' => true]],
            self::MAX_IMAGE_SLIDE_MEDIUM_IMAGE_SIZE_IN_MB
        );
        $this->attachmentExtension->addImageRelation(
            $schema,
            'oro_cms_image_slide',
            'smallImage',
            ['attachment' => ['acl_protected' => false, 'use_dam' => true]],
            self::MAX_IMAGE_SLIDE_SMALL_IMAGE_SIZE_IN_MB
        );

        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_cms_content_widget foreign keys.
     *
     * @param Schema $schema
     */
    private function addOroCmsContentWidgetForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_content_widget');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }

    /**
     * Add oro_cms_content_widget_usage foreign keys.
     *
     * @param Schema $schema
     */
    private function addOroCmsContentWidgetUsageForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_content_widget_usage');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_content_widget'),
            ['content_widget_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_cms_image_slide foreign keys.
     *
     * @param Schema $schema
     */
    private function addOroCmsImageSlideForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_image_slide');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_content_widget'),
            ['content_widget_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }

    /**
     * Add `oro_cms_content_block_title` foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCmsContentBlockTitleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_content_block_title');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_content_block'),
            ['content_block_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add `oro_cms_content_block_scope` foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCmsContentBlockScopeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_content_block_scope');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_content_block'),
            ['content_block_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_cms_content_block foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrganizationForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_content_block');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }

    /**
     * Create oro_cms_text_content_variant table
     *
     * @param Schema $schema
     */
    protected function createOroCmsTextContentVariantTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cms_text_content_variant');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('content_block_id', 'integer', ['notnull' => false]);
        $table->addColumn('content', 'wysiwyg', ['notnull' => false, 'comment' => '(DC2Type:wysiwyg)']);
        $table->addColumn('content_style', 'wysiwyg_style', ['notnull' => false]);
        $table->addColumn('content_properties', 'wysiwyg_properties', ['notnull' => false]);
        $table->addColumn('is_default', 'boolean', ['default' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_cms_txt_cont_variant_scope table
     *
     * @param Schema $schema
     */
    protected function createOroCmsTextContentVariantScopeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cms_txt_cont_variant_scope');
        $table->addColumn('variant_id', 'integer', []);
        $table->addColumn('scope_id', 'integer', []);
        $table->setPrimaryKey(['variant_id', 'scope_id']);
    }

    /**
     * Add oro_cms_text_content_variant foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCmsTextContentVariantForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_text_content_variant');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_content_block'),
            ['content_block_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_cms_txt_cont_variant_scope foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCmsTextContentVariantScopeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_txt_cont_variant_scope');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_text_content_variant'),
            ['variant_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    private function addLocalizedFallbackValueFields(Schema $schema): void
    {
        $table = $schema->getTable('oro_fallback_localization_val');
        $table->addColumn(
            'wysiwyg',
            'wysiwyg',
            [
                'notnull' => false,
                'comment' => '(DC2Type:wysiwyg)',
                OroOptions::KEY => [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                    'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM],
                    'dataaudit' => ['auditable' => false],
                    'importexport' => ['excluded' => false],
                ],
            ]
        );
        $table->addColumn(
            'wysiwyg_style',
            'wysiwyg_style',
            [
                'notnull' => false,
                OroOptions::KEY => [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                    'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM],
                    'dataaudit' => ['auditable' => false],
                    'importexport' => ['excluded' => false],
                ],
            ]
        );
        $table->addColumn(
            'wysiwyg_properties',
            'wysiwyg_properties',
            [
                'notnull' => false,
                OroOptions::KEY => [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                    'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM],
                    'dataaudit' => ['auditable' => false],
                    'importexport' => ['excluded' => false],
                ],
            ]
        );
    }
}
