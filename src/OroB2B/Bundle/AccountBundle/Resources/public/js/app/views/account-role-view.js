define([
    'jquery',
    'oroui/js/mediator',
    'orouser/js/views/role-view'
], function($, mediator, RoleView) {
    'use strict';

    var AccountRoleView;

    /**
     * @export orob2baccount/js/app/views/account-role-view
     */
    AccountRoleView = RoleView.extend({
        options: {
            accountSelector: ''
        },

        $account: null,

        /**
         * Initialize
         *
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$account = $(this.options.accountSelector);
            AccountRoleView.__super__.initialize.apply(this, arguments);
        },

        /**
         * onSubmit event listener
         */
        onSubmit: function(event) {
            event.preventDefault();
            if (this.$label.hasClass('error')) {
                return;
            }
            var $form = this.$form;
            if ($form.data('nohash') && !$form.data('sent')) {
                $form.data('sent', true);
                return;
            }
            if ($form.data('sent')) {
                return;
            }

            $form.data('sent', true);

            var action = $form.attr('action');
            var method = $form.attr('method');
            var url = (typeof action === 'string') ? $.trim(action) : '';
            url = url || window.location.href || '';
            if (url) {
                // clean url (don't include hash value)
                url = (url.match(/^([^#]+)/) || [])[1];
            }

            var data = {};
            data[this.options.formName + '[label]'] = this.$label.val();
            data[this.options.formName + '[account]'] = this.$account.select2('val');
            data[this.options.formName + '[privileges]'] = JSON.stringify(this.privileges);
            data[this.options.formName + '[appendUsers]'] = this.$appendUsers.val();
            data[this.options.formName + '[removeUsers]'] = this.$removeUsers.val();
            data[this.options.formName + '[_token]'] = this.$token.val();
            var options = {
                url: url,
                type: method || 'GET',
                data: $.param(data)
            };
            mediator.execute('submitPage', options);
            this.dispose();
        }
    });

    return AccountRoleView;
});
