define(function(require) {
    'use strict';

    var _ = require('underscore');
    var routing = require('routing');

    var DigitalAssetHelper = {};

    return _.extend(DigitalAssetHelper, {
        /**
         * Returns image preview url from given twig tag (i.e. {{ wysiwyg_image(1, "UUID-HERE") }})
         *
         * @param {string} twigTagString
         * @returns {string}
         */
        getImageUrlFromTwigTag: function(twigTagString) {
            var digitalAssetId = DigitalAssetHelper.getDigitalAssetIdFromTwigTag(twigTagString);

            return DigitalAssetHelper.getImageUrl(digitalAssetId);
        },

        /**
         * @param {string} twigTagString
         * @return {number|null}
         */
        getDigitalAssetIdFromTwigTag: function(twigTagString) {
            var regex = /{{\s*[\w_]+\s*\(\s*([0-9]+)\s*,.+?\)\s*}}/;
            var matches = regex.exec(twigTagString);

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
