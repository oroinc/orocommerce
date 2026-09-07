import $ from 'jquery';
import 'jasmine-jquery';
import 'jquery-ui/widgets/datepicker';
import {clearActiveFragment, setActiveFragment} from 'orostorefrontembedded/js/embedded/active-fragment';
import {
    installDatepickerEmbeddedExtend
} from 'orostorefrontembedded/js/embedded/patches/datepicker-embedded-extend';
import html from 'text-loader!../Fixture/two-fragments.html';

describe('orostorefrontembedded/js/embedded/patches/datepicker-embedded-extend', () => {
    let originalDatepicker;
    let originalShowDatepicker;
    let datepickerCalls;
    let fragment;
    let dateField;

    beforeEach(() => {
        window.setFixtures(html);

        originalDatepicker = $.fn.datepicker;
        originalShowDatepicker = $.datepicker._showDatepicker;
        datepickerCalls = [];
        $.fn.datepicker = function(...args) {
            datepickerCalls.push(args);

            return this;
        };
        $.datepicker._showDatepicker = () => {};
        installDatepickerEmbeddedExtend();

        fragment = {
            containerEl: document.querySelector('.first-fragment-container'),
            overlayRoot: document.querySelector('.first-fragment-overlay')
        };
        setActiveFragment(fragment);

        dateField = document.querySelector('.first-fragment-date-field');
    });

    afterEach(() => {
        $.fn.datepicker = originalDatepicker;
        $.datepicker._showDatepicker = originalShowDatepicker;
        clearActiveFragment(fragment);
        document.body.appendChild($.datepicker.dpDiv[0]);
    });

    it('appends the calendar to the overlay of the fragment holding the field', () => {
        $(dateField).datepicker({});

        expect($.datepicker.dpDiv[0].parentElement).toBe(fragment.overlayRoot);
    });

    it('moves the calendar to the overlay of the fragment opening it next', () => {
        const secondFragment = {
            containerEl: document.querySelector('.second-fragment-container'),
            overlayRoot: document.querySelector('.second-fragment-overlay')
        };
        const secondDateField = document.querySelector('.second-fragment-date-field');
        setActiveFragment(secondFragment);

        $(dateField).datepicker({});
        expect($.datepicker.dpDiv[0].parentElement).toBe(fragment.overlayRoot);

        $(secondDateField).datepicker({});
        expect($.datepicker.dpDiv[0].parentElement).toBe(secondFragment.overlayRoot);

        clearActiveFragment(secondFragment);
    });

    it('passes the widget options through unchanged', () => {
        $(dateField).datepicker({dateFormat: 'yy-mm-dd'});

        expect(datepickerCalls[0]).toEqual([{dateFormat: 'yy-mm-dd'}]);
    });

    it('does not move the calendar for a plugin method call', () => {
        $(dateField).datepicker({});
        document.body.appendChild($.datepicker.dpDiv[0]);

        $(dateField).datepicker('setDate', new Date(2026, 0, 1));

        expect($.datepicker.dpDiv[0].parentElement).toBe(document.body);
        expect(datepickerCalls[1][0]).toBe('setDate');
    });

    it('appends the calendar to the overlay when the widget shows it', () => {
        document.body.appendChild($.datepicker.dpDiv[0]);

        $.datepicker._showDatepicker(dateField);

        expect($.datepicker.dpDiv[0].parentElement).toBe(fragment.overlayRoot);
    });

    it('appends the calendar to the overlay for the field of an event', () => {
        document.body.appendChild($.datepicker.dpDiv[0]);

        $.datepicker._showDatepicker({target: dateField});

        expect($.datepicker.dpDiv[0].parentElement).toBe(fragment.overlayRoot);
    });
});
