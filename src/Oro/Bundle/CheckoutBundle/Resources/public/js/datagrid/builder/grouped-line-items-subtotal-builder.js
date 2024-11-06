const GroupedLineItemsSubtotalBuilder = {
    /**
     * Init() function is required
     */
    init: (deferred, options) => {
        if (GroupedLineItemsSubtotalBuilder.isApplicable(options)) {
            const container = document.querySelector(`[data-role="${options.gridName}subtotal"]`);

            if (document.contains(container)) {
                container.innerText = options.data.metadata.groupSubtotal;
            }
        }

        // Update grid toolbar ofter expand collapsed container
        options.gridPromise.done(grid => {
            if (grid.$el.closest('[data-role="collapse-body"]').length) {
                grid.$el.closest('[data-role="collapse-body"]')
                    .on(`shown.bs.collapse${grid.eventNamespace()}`, () => grid.callToolbar('noChildrenVisibility'));
            }

            grid.once('dispose', () => {
                if (grid.$el.closest('[data-role="collapse-body"]').length) {
                    grid.$el.closest('[data-role="collapse-body"]').off(grid.eventNamespace());
                }
            });
        });

        return deferred.resolve();
    },

    /**
     * @param {Object} options
     * @returns {boolean}
     */
    isApplicable(options) {
        return typeof options?.data?.metadata?.groupSubtotal === 'string';
    }
};

export default GroupedLineItemsSubtotalBuilder;
