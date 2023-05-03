import __ from 'orotranslation/js/translator';

const excludeFromCollection = {
    title: __('oro.product.drop_zones.excludeFromCollection'),
    order: 40,
    dropHandler(e, ui, datagrid) {
        const models = datagrid.collection.filter('_selected');
        datagrid.collection.remove(models, {alreadySynced: true});
    }
};

export default excludeFromCollection;
