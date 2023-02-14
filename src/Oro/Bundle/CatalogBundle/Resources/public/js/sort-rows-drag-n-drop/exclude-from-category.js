import __ from 'orotranslation/js/translator';

const excludeFromCategory = {
    title: __('oro.catalog.drop_zones.excludeFromCategory'),
    order: 40,
    dropCallback(e, ui, datagrid) {
        const models = datagrid.collection.filter('_selected');

        datagrid.collection.remove(models, {silent: true});
        datagrid.collection.sort();

        if (!datagrid.collection.length) {
            datagrid.renderNoDataBlock();
        }
        console.warn(models.map(model => model.toJSON()));
    }
};

export default excludeFromCategory;
