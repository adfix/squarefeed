/**
 * Set swatch options using default values from config json data
 * json data contains product options and default values from url
 *
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */
require([
    'jquery',
    'jquery/ui',
    'jquery/jquery.parsequery'
], function ($) {
    'use strict';

    var options = {
            swatchSelector: '.swatch-opt',
            swatchParent: 'swatch-attribute',
            swatchWidgetName: 'mageSwatchRenderer',
            mediaGallerySelector: '[data-gallery-role=gallery-placeholder]',
            needSetDefaultValues: false
        },
        swatchElement = $(options.swatchSelector),

        /**
         * Get default options values settings with either URL query parameters
         * @private
         */
        getSelectedAttributes = function () {
            var attr = {},
                params = $.parseQuery();

            $.each(params, function (sfKey, value) {
                var key = sfKey.split('sf_')[1];
                if (typeof key != 'undefined') {
                    attr[key] = value;
                }
            });

            return attr;
        },

        /**
         * Prepare attribute values by label
         *
         * @param defaultValues
         * @param swatchWidget
         * @returns {{}}
         */
        prepareSelectedAttributes = function (defaultValues, swatchWidget) {
            var preparedValues = {};
            $.each(defaultValues, function (key, value) {
                var option = swatchElement.find('.' + swatchWidget.options.classes.attributeClass +
                    '[attribute-code="' + key + '"] [option-label="' + value + '"]');

                if (typeof option != 'undefined') {
                    /**
                     * hook for some magento 2.1.X versions which contain update price error in swatch class
                     */
                    var optionId = option.attr('option-id');
                    option.parents('.' + options.swatchParent).attr('option-selected', optionId);

                    preparedValues[key] = optionId;
                }

            });


            return preparedValues;
        },

        /**
         * Set default values for swatch
         *
         * @private
         */
        setDefaultValues = function (defaultValues, forceSetDefault) {
            if (options.needSetDefaultValues === false && forceSetDefault === false) {
                options.needSetDefaultValues = true;
                return;
            }

            var oldMagentoVer = false,
                swatchWidget = swatchElement.data(options.swatchWidgetName);
            if (!swatchWidget || !swatchWidget._EmulateSelected) {
                /**
                 * some magento 2.0.X versions contain different swatch widget object's name
                 */
                swatchWidget = swatchElement.data('customSwatchRenderer');
                if (!swatchWidget || !swatchWidget._EmulateSelected) {
                    return;
                }
                oldMagentoVer = true;
            }

            defaultValues = prepareSelectedAttributes(defaultValues, swatchWidget);
            if (oldMagentoVer){
                $.each(defaultValues, function (key, value) {
                    swatchElement.find('.' + swatchWidget.options.classes.attributeClass
                        + '[attribute-code="' + key + '"] [option-id="' + value + '"]').trigger('click');
                });
            }else{
                swatchWidget._EmulateSelected(defaultValues);
            }
        };

    var defaultValues = getSelectedAttributes(),
        mediaElement = $(options.mediaGallerySelector);

    swatchElement.on('swatch.initialized', function () {
        setDefaultValues(defaultValues, false);
    });

    mediaElement.on('gallery:loaded', function () {
        /**
         * swatch.initialized event is absent in magento < 2.1.5
         * need to check if swatch element is loaded
         */
        var forceSetDefault = false;
        if (typeof swatchElement[0] != 'undefined' && swatchElement[0].children.length > 0) {
            forceSetDefault = true;
        }
        setDefaultValues(defaultValues, forceSetDefault);
    });

});
