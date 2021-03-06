define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const LineItemProductView = require('oroproduct/js/app/views/line-item-product-view');
    const ProductUnitComponent = require('oroproduct/js/app/components/product-unit-component');
    const TotalsListener = require('oropricing/js/app/listener/totals-listener');
    const NumberFormatter = require('orolocale/js/formatter/number');
    const mediator = require('oroui/js/mediator');
    const localeSettings = require('orolocale/js/locale-settings');

    /**
     * @export oroinvoice/js/app/views/line-item-view
     * @extends oroui.app.views.base.View
     * @class orob2invoice.app.views.LineItemView
     */
    const LineItemView = LineItemProductView.extend({
        options: {
            priceTypes: {
                BUNDLE: 20,
                UNIT: 10
            },
            selectors: {
                productSelector: '.invoice-line-item-type-product [data-name="field__product"]',
                quantitySelector: '.invoice-line-item-quantity input',
                unitSelector: '.invoice-line-item-quantity select',
                productSku: '.invoice-line-item-sku .invoice-line-item-type-product',
                productType: '.invoice-line-item-type-product',
                freeFormType: '.invoice-line-item-type-free-form',
                totalPrice: '.invoice-line-item-total-price'
            },
            currency: localeSettings.getCurrency()
        },

        pricesComponent: null,

        /**
         * @inheritDoc
         */
        constructor: function LineItemView(options) {
            LineItemView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this._removeRow = this._removeRow.bind(this);
            this._setTotalPrice = this._setTotalPrice.bind(this);

            this.$el.on('click', '.removeLineItem', this._removeRow);
            mediator.on('update:currency', this._updateCurrency, this);

            LineItemView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        handleLayoutInit: function(options) {
            this.$fields = this.$el.find(':input[name]');

            this.fieldsByName = {};
            this.$fields.each(_.bind(function(i, field) {
                this.fieldsByName[this._formFieldName(field)] = $(field);
            }, this));

            this.initSubtotalListener();
            this.initUnitLoader();
            this.initTypeSwitcher();
            this.initProduct();
            this.initItemTotal();
            mediator.trigger('invoice-line-item:created', this.$el);

            LineItemView.__super__.handleLayoutInit.call(this, options);
        },

        initSubtotalListener: function() {
            TotalsListener.listen([
                this.fieldsByName.product,
                this.fieldsByName.quantity,
                this.fieldsByName.productUnit,
                this.fieldsByName.priceValue,
                this.fieldsByName.priceType
            ]);
        },

        initUnitLoader: function(options) {
            const defaultOptions = {
                _sourceElement: this.$el,
                productSelector: this.options.selectors.productSelector,
                quantitySelector: this.options.selectors.quantitySelector,
                unitSelector: this.options.selectors.unitSelector,
                loadingMaskEnabled: false,
                dropQuantityOnLoad: false,
                defaultValues: this.options.freeFormUnits,
                model: this.model
            };

            this.subview('productUnitComponent', new ProductUnitComponent(_.extend({}, defaultOptions, options || {})));
        },

        initTypeSwitcher: function() {
            const $product = this.$el.find('div' + this.options.selectors.productType);
            const $freeForm = this.$el.find('div' + this.options.selectors.freeFormType);

            const showFreeFormType = function() {
                $product.hide();
                $freeForm.show();
            };

            const showProductType = function() {
                $freeForm.hide();
                $product.show();
            };

            $freeForm.find('a' + this.options.selectors.productType).click(_.bind(function() {
                showProductType();
                $freeForm.find(':input').val('').change();
            }, this));

            $product.find('a' + this.options.selectors.freeFormType).click(_.bind(function() {
                showFreeFormType();
                this.fieldsByName.product.inputWidget('val', '');
                this.fieldsByName.product.change();
            }, this));

            if (this.fieldsByName.freeFormProduct.val() !== '') {
                showFreeFormType();
            } else {
                showProductType();
            }
        },

        initProduct: function() {
            if (this.fieldsByName.product) {
                this.fieldsByName.product.change(_.bind(function() {
                    this._resetData();

                    const data = this.fieldsByName.product.inputWidget('data') || {};
                    this.$el.find(this.options.selectors.productSku).text(data.sku || null);
                }, this));
            }
        },

        initItemTotal: function() {
            this._setTotalPrice();

            this.fieldsByName.priceValue
                .add(this.fieldsByName.priceType)
                .add(this.fieldsByName.productUnit)
                .add(this.fieldsByName.quantity)
                .add(this.fieldsByName.priceCurrency)
                .on('change', this._setTotalPrice);
        },

        /**
         * @param {Object} field
         * @returns {String}
         */
        _formFieldName: function(field) {
            let name = '';
            const nameParts = field.name.replace(/.*\[[0-9]+\]/, '').replace(/[\[\]]/g, '_').split('_');
            let namePart;

            for (let i = 0, iMax = nameParts.length; i < iMax; i++) {
                namePart = nameParts[i];
                if (!namePart.length) {
                    continue;
                }
                if (name.length === 0) {
                    name += namePart;
                } else {
                    name += namePart[0].toUpperCase() + namePart.substr(1);
                }
            }
            return name;
        },

        _resetData: function() {
            if (this.fieldsByName.hasOwnProperty('quantity')) {
                this.fieldsByName.quantity.val(1);
            }
        },

        _updateCurrency: function(val) {
            this.options.currency = val;
            this.fieldsByName.priceCurrency.val(val);
            this.fieldsByName.priceCurrency.trigger('change');
        },

        _setTotalPrice: function() {
            let totalPrice;
            if (!this.fieldsByName.priceValue) {
                return;
            }

            totalPrice = +this.fieldsByName.priceValue.val();
            if (+this.fieldsByName.priceType.val() === this.options.priceTypes.UNIT) {
                totalPrice *= +this.fieldsByName.quantity.val();
            }

            this.$el.find(this.options.selectors.totalPrice)
                .text(NumberFormatter.formatCurrency(totalPrice, this.options.currency));
        },

        _removeRow: function() {
            this.$el.trigger('content:remove');
            this.remove();
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off('click', '.removeLineItem', this._removeRow);
            this.fieldsByName.priceValue.off('change', this._setTotalPrice);
            mediator.off(null, null, this);

            LineItemView.__super__.dispose.call(this);
        }
    });

    return LineItemView;
});
