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
