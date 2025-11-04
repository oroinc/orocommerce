import {escape} from 'underscore';

export default editor => {
    const {model: BaseRadioTypeModel} = editor.StyleManager.getType('radio');

    const RadioTypeModel = BaseRadioTypeModel.extend({
        constructor: function RadioTypeModel(...args) {
            return RadioTypeModel.__super__.constructor.apply(this, args);
        },

        getOptions(...args) {
            const options = RadioTypeModel.__super__.getOptions.apply(this, args);

            return options.map(option => {
                if (option.title) {
                    option.title = escape(option.title);
                }

                if (option.label) {
                    option.label = escape(option.label);
                }

                return option;
            });
        }
    });

    editor.StyleManager.addType('radio', {
        model: RadioTypeModel
    });
};
