define(function(require) {
    'use strict';

    var LevelQuantityBuilder = require('oroinventory/js/datagrid/builder/level-quantity-builder');
    var BaseView = require('oroui/js/app/views/base/view');
    var PageableCollection = require('orodatagrid/js/pageable-collection');
    var $ = require('jquery');

    var createCollection = function() {
        var models = [
            {order: 0, renderable: true, quantity: 123}
        ];

        var collection = new PageableCollection(models);
        collection.fullCollection = collection;

        return collection;
    };

    var createGrid = function(collection) {
        var grid = new BaseView({
            collection: collection
        });
        grid.refreshAction = {execute: function() {}};
        grid.body = {rows: []};

        return grid;
    };

    var createBuilder = function(grid, callback) {
        var deferred = $.Deferred();
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
                var model = this.collection.models[0];
                expect(model.get('quantity')).toEqual(123);

                model.set('quantity', 123456.789);
                expect(model.get('quantity')).toEqual(123456.789);

                model.set('quantity', '123456.78');
                expect(model.get('quantity')).toEqual(123456.78);
            });
        });
    });
});
