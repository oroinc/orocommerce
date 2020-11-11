import Backbone from 'backbone';
import mediator from 'oroui/js/mediator';

const shoppingListRFPConfirmationBuilder = {
    init(deferred, options) {
        const observer = Object.create(Backbone.Events);
        let {hasEmptyMatrix} = options.metadata;

        mediator.setHandler('shoppinglist:hasEmptyMatrix', () => hasEmptyMatrix, observer);

        options.gridPromise.done(grid => {
            observer.listenTo(mediator, 'datagrid:metadata-loaded', someGrid => {
                if (someGrid === grid) {
                    hasEmptyMatrix = grid.metadataModel.get('hasEmptyMatrix');
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

export default shoppingListRFPConfirmationBuilder;
