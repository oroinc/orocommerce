define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');

    const StyleIsolationModule = function(options) {
        _.extend(this, _.pick(options, ['view', 'builder']));

        this.componentCssIdRegexp = /(\[id="component-id-view([\d]*)"\])/g;
        this.componentHtmlIdRegexp = /(<div id="component-id-view([\d]*))/g;
        this.cssSelectorRegexp = /([(.|#)?\w-*]*?(\s)?{)/g;

        this.init();
    };

    StyleIsolationModule.prototype = {
        init: function() {
            this.wrapMethods();

            _.extend(this.view, _.pick(this, ['removeCSSContainerId']));
        },

        wrapMethods: function() {
            const uniqId = 'id="component-id-' + _.uniqueId('view') + '"';

            this.builder.getHtml = _.wrap(this.builder.getHtml, _.bind(function(func) {
                var html = this.removeHTMLContainer(func());

                html = !html ? html : '<div ' + uniqId + '>' + html + '</div>';
                return html;
            }, this));

            this.builder.getCss = _.wrap(this.builder.getCss, _.bind(function(func) {
                let css = this.removeCSSContainerId(func());
                css = css.replace(this.cssSelectorRegexp, '[' + uniqId + '] $&');
                return css;
            }, this));
        },

        removeCSSContainerId: function(cssText) {
            return cssText.replace(this.componentCssIdRegexp, '');
        },

        removeHTMLContainer: function(html) {
            if (this.componentHtmlIdRegexp.test(html)) {
                html = $(html).html();
                html = this.removeHTMLContainer(html);
            }

            return html;
        }
    };

    return StyleIsolationModule;
});
