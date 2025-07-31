export default (BaseTypeModel, {editor}) => {
    const ImageTypeModel = BaseTypeModel.extend({
        editor,

        constructor: function ImageTypeModel(...args) {
            return ImageTypeModel.__super__.constructor.apply(this, args);
        },

        /**
         * Remove parent picture component if it exists
         */
        remove(...args) {
            const picture = this.closestType('picture');

            ImageTypeModel.__super__.remove.apply(this, args);

            picture && picture.remove();

            return this;
        }
    });

    Object.defineProperty(BaseTypeModel.prototype, 'defaults', {
        value: {
            ...BaseTypeModel.prototype.defaults,
            tagName: 'img',
            alt: '',
            previewMetadata: {}
        }
    });

    return ImageTypeModel;
};
