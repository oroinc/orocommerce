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

            return DigitalAssetHelper.getImageUrl(digitalAssetId);
        },

        /**
         * @param {string} twigTagString
         * @return {number|null}
         */
        getDigitalAssetIdFromTwigTag: function(twigTagString) {
            const regex = /{{\s*[\w_]+\s*\(\s*([0-9]+)\s*,.+?\)\s*}}/;
            const matches = regex.exec(twigTagString);

            return matches !== null ? matches[1] || null : null;
        },

        /**
         * @param {number} digitalAssetId
         * @returns {string}
         */
        getImageUrl: function(digitalAssetId) {
            return routing.generate('oro_cms_wysiwyg_digital_asset', {id: digitalAssetId, action: 'get'});
        }
    });
});
