import $ from 'jquery';
import 'jasmine-jquery';
import DomRelocationView from 'orofrontend/js/app/views/dom-relocation-view';
import {RELOCATION_TARGET_ATTRIBUTE} from 'orostorefrontembedded/js/embedded/config';
import {
    installDomRelocationEmbeddedExtend
} from 'orostorefrontembedded/js/embedded/patches/dom-relocation-embedded-extend';
import html from 'text-loader!./Fixture/two-fragments-with-relocation-target.html';

describe('orostorefrontembedded/js/embedded/patches/dom-relocation-embedded-extend', () => {
    const proto = DomRelocationView.prototype;
    const MOVE_TO = '.relocation-target';

    let originalMoveToTarget;
    let foundTargets;

    const relocate = ($el, moveTo = MOVE_TO) => proto.moveToTarget.call({}, $el, {moveTo});
    const markedTargetCount = () => $(`[${RELOCATION_TARGET_ATTRIBUTE}]`).length;

    beforeEach(() => {
        window.setFixtures(html);

        originalMoveToTarget = proto.moveToTarget;
        foundTargets = [];
        proto.moveToTarget = function($el, targetOptions) {
            foundTargets.push($(targetOptions.moveTo).get());
        };
        installDomRelocationEmbeddedExtend();
    });

    afterEach(() => {
        proto.moveToTarget = originalMoveToTarget;
    });

    it('relocates an element to the target inside its own fragment', () => {
        relocate($('.first-fragment .relocated-element'));

        expect(foundTargets[0]).toEqual($('.first-fragment .relocation-target').get());
    });

    it('relocates an element of the second fragment to the target of that fragment', () => {
        relocate($('.second-fragment .relocated-element'));

        expect(foundTargets[0]).toEqual($('.second-fragment .relocation-target').get());
    });

    it('removes the marker attribute after the relocation', () => {
        relocate($('.first-fragment .relocated-element'));

        expect(markedTargetCount()).toBe(0);
    });

    it('removes the marker attribute when the relocation throws', () => {
        proto.moveToTarget = function() {
            throw new Error('cannot move');
        };
        installDomRelocationEmbeddedExtend();

        expect(() => relocate($('.first-fragment .relocated-element'))).toThrow();
        expect(markedTargetCount()).toBe(0);
    });

    it('relocates an element outside a fragment to the host target', () => {
        relocate($('.host-relocated-element'), '.host-relocation-target');

        expect(foundTargets[0]).toEqual($('.host-relocation-target').get());
    });
});
