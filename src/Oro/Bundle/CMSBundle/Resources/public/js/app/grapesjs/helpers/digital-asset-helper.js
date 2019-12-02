define(function(require) {
    'use strict';

    const _ = require('underscore');
    const routing = require('routing');

    const DigitalAssetHelper = {};

    return _.extend(DigitalAssetHelper, {
        /**
         * Returns image preview url from given twig tag (i.e. {{ wysiwyg_image(1, "UUID-HERE") }})
         *
         * @param {string} twigTagString
         * @returns {string}
         */
        getImageUrlFromTwigTag: function(twigTagString) {
            const digitalAssetId = DigitalAssetHelper.getDigitalAssetIdFromTwigTag(twigTagString);

            if (digitalAssetId) {
                return DigitalAssetHelper.getImageUrl(digitalAssetId);
            }

            return '';
        },

        /**
         * @param {string} twigTagString
         * @return {number|null}
         */
        getDigitalAssetIdFromTwigTag: function(twigTagString = '') {
            const regex = /(?![wysiwyg_image\(\'])[\d]+(?=\'\,)/g;
            const matches = twigTagString.match(regex);

            return matches && matches.length ? matches : null;
        },

        /**
         * @param {number} digitalAssetId
         * @returns {string}
         */
        getImageUrl: function(digitalAssetId) {
            if (_.isArray(digitalAssetId)) {
                digitalAssetId = digitalAssetId[0];
            }
            return routing.generate('oro_cms_wysiwyg_digital_asset', {id: digitalAssetId, action: 'get'});
        }
    });
});
