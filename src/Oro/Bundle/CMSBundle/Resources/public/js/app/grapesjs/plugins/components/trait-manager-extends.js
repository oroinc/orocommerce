export default editor => {
    const TraitText = editor.TraitManager.getType('text');

    TraitText.prototype.templateLabel = function() {
        const {ppfx, em} = this;
        const {name} = this.model.attributes;
        const label = this.getLabel();
        let title = em.t(`traitManager.traits.title.${name}`);

        if (title === void 0) {
            title = label;
        }

        return `<div class="${ppfx}label" title="${title}">${label}</div>`;
    };
};
