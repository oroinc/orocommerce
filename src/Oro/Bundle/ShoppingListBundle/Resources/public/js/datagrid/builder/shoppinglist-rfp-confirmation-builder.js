import Backbone from 'backbone';
import mediator from 'oroui/js/mediator';

const shoppinglistRFPConfirmationBuilder = {
    init: function(deferred, options) {
        const observer = Object.create(Backbone.Events);
        let {hasEmptyMatrix} = options.metadata; // @todo, has to contain `hasEmptyMatrix`

        mediator.setHandler('shoppinglist:hasEmptyMatrix', () => hasEmptyMatrix, observer);

        options.gridPromise.done(grid => {
            observer.listenTo(mediator, 'datagrid:metadata-loaded', someGrid => {
                if (someGrid === grid) {
                    hasEmptyMatrix = grid.metadataModel.get('hasEmptyMatrix'); // @todo, has to contain `hasEmptyMatrix`
                }
            });

            observer.listenToOnce(grid, 'dispose', function() {
                observer.stopListening();
                mediator.removeHandlers(observer);
            });

            deferred.resolve();
        }).fail(() => {
            mediator.removeHandlers(observer);
            deferred.reject();
        });
    }
};

export default shoppinglistRFPConfirmationBuilder;
