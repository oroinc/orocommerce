import __ from 'orotranslation/js/translator';

const excludeFromCategory = {
    title: __('oro.catalog.drop_zones.excludeFromCategory'),
    order: 40,
    dropHandler(e, ui, datagrid) {
        const models = datagrid.collection.filter('_selected');
        datagrid.collection.remove(models, {alreadySynced: true});
    }
};

export default excludeFromCategory;
