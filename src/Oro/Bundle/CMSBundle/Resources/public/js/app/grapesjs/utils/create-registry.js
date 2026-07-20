/**
 * Create a registry instance for storing and retrieving configuration objects.
 * Provides registration, retrieval, sorting, caching and detection logic.
 *
 * @param {Object} [itemDefaults] Default properties merged into every registered item
 * @returns {Object} Registry instance
 *
 * @example
 * import createRegistry from 'orocms/js/app/grapesjs/utils/create-registry';
 *
 * const registry = createRegistry({
 *     order: 100,
 *     classes: [],
 *     traits: []
 * });
 *
 * registry.register({
 *     id: 'custom',
 *     label: 'Custom',
 *     order: 10,
 *     detect(model) { return model.get('type') === 'custom'; }
 * });
 *
 * registry.get('custom');           // { id: 'custom', label: 'Custom', ... }
 * registry.getAll();                // sorted by order
 * registry.getSelectOptions();      // [{ id: 'custom', label: 'Custom' }]
 * registry.detectFromModel(model);  // 'custom' or null
 */
export default function createRegistry(itemDefaults = {}) {
    const items = {};

    return {
        /**
         * Register an item configuration.
         * @param {Object} config Must have an `id` property
         */
        register(config) {
            if (!config || !config.id) {
                throw new Error('Config must have an "id" property');
            }

            items[config.id] = {...itemDefaults, ...config};
        },

        /**
         * Get item by ID.
         * @param {string} id
         * @returns {Object|undefined}
         */
        get(id) {
            return items[id];
        },

        /**
         * Get all registered items sorted by order.
         * @returns {Object[]}
         */
        getAll() {
            return Object.values(items).sort((a, b) => a.order - b.order);
        },

        /**
         * Get options formatted for a select/radio trait.
         * @returns {Array<{id: string, label: string}>}
         */
        getSelectOptions() {
            return this.getAll().map(({id, label}) => ({id, label}));
        },

        /**
         * Detect item from a component model using registered detect functions.
         * Checks in reverse order so higher-order items take priority.
         * @param {Object} model GrapesJS component model
         * @returns {string|null} Item ID or null
         */
        detectFromModel(model) {
            const all = this.getAll().reverse();

            for (const item of all) {
                if (item.detect && item.detect(model)) {
                    return item.id;
                }
            }

            return null;
        },

        /**
         * Detect item from a DOM element using registered detect functions.
         * @param {HTMLElement} el
         * @returns {string|null} Item ID or null
         */
        detectFromElement(el) {
            const all = this.getAll().reverse();

            for (const item of all) {
                if (item.detect && item.detect(el)) {
                    return item.id;
                }
            }

            return null;
        },

        /**
         * Check if an item is registered.
         * @param {string} id
         * @returns {boolean}
         */
        has(id) {
            return id in items;
        }
    };
}
