import ContentIsolation, {
    escapeWrapper,
    stripRestrictedAttrs,
    getWrapperAttrs
} from 'orocms/js/app/grapesjs/plugins/components/content-isolation';
import CONSTANTS from 'orocms/js/app/grapesjs/constants';

const ISOLATION_SCOPE = `${CONSTANTS.ISOLATION_PREFIX}-`;
const TEST_ID = `${ISOLATION_SCOPE}UUID__`;

const ROOT_ATTR = `#${TEST_ID}[id*="isolation"]`;
const SCOPE_ATTR = `#${TEST_ID}[id*="scope"]`;

const cssLines = {
    '.test{color: red;}': `#${TEST_ID} .test{color: red;}`,
    '.test, .test2{color: red;}': `#${TEST_ID} .test, #${TEST_ID} .test2{color: red;}`,
    '#testID{color: red;}': `#${TEST_ID} #testID{color: red;}`,
    '[data-attr="test"], [role="button"]{color: red;}':
        `#${TEST_ID} [data-attr="test"], #${TEST_ID} [role="button"]{color: red;}`,
    'div + div{color: red;}': `#${TEST_ID} div + div{color: red;}`,
    '.div ~ div{color: red;}': `#${TEST_ID} .div ~ div{color: red;}`,
    // eslint-disable-next-line
    '.div > .div{color: red;} .cms-wrapper{color: red;}': `#${TEST_ID} .div > .div{color: red;} #${TEST_ID}.cms-wrapper{color: red;}`,
    'ul, ol{color: red;}': `#${TEST_ID} ul, #${TEST_ID} ol{color: red;}`,
    // eslint-disable-next-line
    '[foo^="bar"], div[foo|="fruit"], div[foo$="fruit"]{color: red;}': `#${TEST_ID} [foo^="bar"], #${TEST_ID} div[foo|="fruit"], #${TEST_ID} div[foo$="fruit"]{color: red;}`,
    'div:nth-child(n){color: red;}': `#${TEST_ID} div:nth-child(n){color: red;}`,
    '@media screen { body{display: none;}}': `@media screen { #${TEST_ID} body{display: none;}}`,
    '@media(max-width: 767px){ #testId{color:red;}}': `@media(max-width: 767px){ #${TEST_ID} #testId{color:red;}}`,
    // eslint-disable-next-line
    '    @media(max-width: 767px){ .div > .div{color:red;} .test, .test2{color:red;}}': `    @media(max-width: 767px){ #${TEST_ID} .div > .div{color:red;} #${TEST_ID} .test, #${TEST_ID} .test2{color:red;}}`,
    ':scope, *{display: none;}': `${SCOPE_ATTR}, #${TEST_ID} *{display: none;}`,
    ':root, *{display: none;}': `${ROOT_ATTR}, #${TEST_ID} *{display: none;}`,
    ':root ~ *{display: none;}': `${ROOT_ATTR} ~ *{display: none;}`,
    // eslint-disable-next-line
    ':root ~ *, :root + *, :scope ~ *{display:none;}': `${ROOT_ATTR} ~ *, ${ROOT_ATTR} + *, ${SCOPE_ATTR} ~ *{display:none;}`,
    'div, span, p{color: red;}': `#${TEST_ID} div, #${TEST_ID} span, #${TEST_ID} p{color: red;}`,
    // eslint-disable-next-line
    'div, .test, #testID, [attr="test"]{color: red;}': `#${TEST_ID} div, #${TEST_ID} .test, #${TEST_ID} #testID, #${TEST_ID} [attr="test"]{color: red;}`,
    // eslint-disable-next-line
    'div:before, span:after, p:after{color: red;}': `#${TEST_ID} div:before, #${TEST_ID} span:after, #${TEST_ID} p:after{color: red;}`,
    // eslint-disable-next-line
    '::before, ::after, :first-child{color: red;}': `#${TEST_ID}::before, #${TEST_ID}::after, #${TEST_ID}:first-child{color: red;}`,
    // eslint-disable-next-line
    '.cms-wrapper{color: red;}': `#${TEST_ID}.cms-wrapper{color: red;}`
};

describe('orocms/js/app/grapesjs/plugins/components/content-isolation', () => {
    const contentIsolation = new ContentIsolation({
        scopeId: 'UUID__'
    });

    it('check generate scoped id', () => {
        expect(contentIsolation.scopeId).toEqual(TEST_ID);
    });

    it('check before isolation hook', () => {
        expect(contentIsolation.beforeIsolateHook('@media screen {body {display: none;}}'))
            .toEqual(`@media screen \n{\nbody \n{\ndisplay: none;\n}\n\n}\n`);
    });

    it('check after isolation hook', () => {
        expect(contentIsolation.afterIsolateHook('@media screen \n{\nbody \n{\ndisplay: none;\n}\n\n}\n'))
            .toEqual(`@media screen {body {display: none;}}`);
    });

    it('check html isolation wrapper', () => {
        expect(contentIsolation.isolateHtml('<div>Test content</div>'))
            .toEqual(`<div id="${TEST_ID}"><div>Test content</div></div>`);
    });

    it('check html isolation wrapper with wrapper classes', () => {
        expect(contentIsolation.isolateHtml('<div>Test content</div>', 'wrapper-class'))
            .toEqual(`<div id="${TEST_ID}" class="wrapper-class"><div>Test content</div></div>`);
    });

    it('check "escapeWrapper" function', () => {
        expect(escapeWrapper(`<div id="${TEST_ID}" class="wrapper-class"><div>Test content</div></div>`))
            .toEqual(`<div>Test content</div>`);
    });

    it('check "getWrapperAttrs" function', () => {
        expect(
            getWrapperAttrs(`<div id="${TEST_ID}" data-gjs-type="test" 
                    draggable="true" class="wrapper-class" data-test="Lorem">
                <div>Test content</div>
            </div>`)
        ).toEqual({
            'class': 'wrapper-class',
            'data-test': 'Lorem'
        });
    });

    it('check "stripRestrictedAttrs" function for strip forbidden attributes', () => {
        expect(
            stripRestrictedAttrs(`<div id="${TEST_ID}" draggable="true" data-gjs-test="text" class="wrapper-class">
                <div>Test content</div>
            </div>`)
        ).toEqual(`<div id="${TEST_ID}" class="wrapper-class">
                <div>Test content</div>
            </div>`);
    });

    for (const [original, isolated] of Object.entries(cssLines)) {
        it(`get isolated styles from original: ${original}`, () => {
            expect(contentIsolation.isolateCss(original.replace(/\n/gm, ''))).toEqual(isolated);
        });

        it(`escape origin styles from isolation: ${original}`, () => {
            expect(contentIsolation.escapeCssIsolation(isolated.replace(/\n/gm, ''))).toEqual(original);
        });
    }
});
