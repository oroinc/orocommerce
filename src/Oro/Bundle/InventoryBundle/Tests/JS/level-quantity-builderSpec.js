define(function(require) {
    'use strict';

    const LevelQuantityBuilder = require('oroinventory/js/datagrid/builder/level-quantity-builder');
    const BaseView = require('oroui/js/app/views/base/view');
    const PageableCollection = require('orodatagrid/js/pageable-collection');
    const $ = require('jquery');

    const createCollection = function() {
        const models = [
            {order: 0, renderable: true, quantity: 123}
        ];

        const collection = new PageableCollection(models);
        collection.fullCollection = collection;

        return collection;
    };

    const createGrid = function(collection) {
        const grid = new BaseView({
            collection: collection
        });
        grid.refreshAction = {execute: function() {}};
        grid.body = {rows: []};

        return grid;
    };

    const createBuilder = function(grid, callback) {
        const deferred = $.Deferred();
        deferred.done(callback);

        grid._deferredRender();

        LevelQuantityBuilder.init(deferred, {
            gridPromise: grid.deferredRender,
            metadata: {
                options: {
                    cellSelection: {
                        selector: ''
                    }
                }
            }
        });

        grid._resolveDeferredRender();
    };

    describe('oroinventory/js/datagrid/builder/level-quantity-builder', function() {
        beforeEach(function(done) {
            this.collection = createCollection();

            this.grid = createGrid(this.collection);

            createBuilder(this.grid, (function(builder) {
                this.levelQuantityBuilder = builder;
                done();
            }).bind(this));
        });

        describe('check quantity formatter', function() {
            it('quantity should be valid number', function() {
                const model = this.collection.models[0];
                expect(model.get('quantity')).toEqual(123);

                model.set('quantity', 123456.789);
                expect(model.get('quantity')).toEqual(123456.789);

                model.set('quantity', '123456.78');
                expect(model.get('quantity')).toEqual(123456.78);
            });
        });
    });
});
