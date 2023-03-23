import 'jasmine-jquery';
import GrapesjsEditorView from 'orocms/js/app/grapesjs/grapesjs-editor-view';
import html from 'text-loader!../fixtures/grapesjs-editor-view-fixture.html';
import '../fixtures/load-plugin-modules';

describe('orocms/js/app/grapesjs/plugins/grapesjs-content-parser', () => {
    let grapesjsEditorView;
    let htmlParser;
    const textBlockOptions = {
        draggable: 0,
        droppable: 0,
        editable: 0,
        highlightable: 0,
        hoverable: 0,
        layerable: 0,
        selectable: 0
    };

    beforeEach(done => {
        window.setFixtures(html);
        grapesjsEditorView = new GrapesjsEditorView({
            el: '#grapesjs-view',
            themes: [{
                label: 'Test',
                stylesheet: '',
                active: true
            }],
            disableDeviceManager: true
        });
        const context = grapesjsEditorView.builder.Parser.parserHtml;
        htmlParser = context.parse.bind(context);
        grapesjsEditorView.builder.on('editor:rendered', () => done());
    });

    afterEach(() => {
        grapesjsEditorView.dispose();
    });

    describe('feature "GrapesjsContentParser"', () => {
        it('Simple div node', () => {
            const str = '<div></div>';
            const result = [
                {
                    tagName: 'div'
                }
            ];
            expect(htmlParser(str).html).toEqual(result);
        });

        it('Simple article node', () => {
            const str = '<article></article>';
            const result = [
                {
                    tagName: 'article'
                }
            ];
            expect(htmlParser(str).html).toEqual(result);
        });

        it('Node with attributes', () => {
            const str =
                '<div id="test1" class="test2 test3" data-one="test4" strange="test5"></div>';
            const result = [
                {
                    tagName: 'div',
                    classes: ['test2', 'test3'],
                    attributes: {
                        'data-one': 'test4',
                        'id': 'test1',
                        'strange': 'test5'
                    }
                }
            ];
            expect(htmlParser(str).html).toEqual(result);
        });

        it('Style attribute is isolated', () => {
            const str =
                '<div id="test1" style="color:black; width:100px; test:value;"></div>';
            const result = [
                {
                    tagName: 'div',
                    attributes: {
                        id: 'test1'
                    },
                    style: {
                        color: 'black',
                        width: '100px',
                        test: 'value'
                    }
                }
            ];
            expect(htmlParser(str).html).toEqual(result);
        });

        it('Class attribute is isolated', () => {
            const str = '<div id="test1" class="test2 test3 test4"></div>';
            const result = [
                {
                    tagName: 'div',
                    attributes: {
                        id: 'test1'
                    },
                    classes: ['test2', 'test3', 'test4']
                }
            ];
            expect(htmlParser(str).html).toEqual(result);
        });

        it('Parse images nodes', () => {
            const str = '<img id="test1" src="./index.html"/>';
            const result = [
                {
                    tagName: 'img',
                    type: 'image',
                    attributes: {
                        id: 'test1',
                        src: './index.html'
                    }
                }
            ];

            expect(htmlParser(str).html).toEqual(result);
        });

        it('Parse text nodes', () => {
            const str = '<div id="test1">test2 </div>';
            const result = [
                {
                    tagName: 'div',
                    attributes: {
                        id: 'test1'
                    },
                    type: 'text',
                    components: [
                        {
                            type: 'textnode',
                            tagName: '',
                            content: 'test2 '
                        }
                    ]
                }
            ];

            expect(htmlParser(str).html).toEqual(result);
        });

        it('Parse blockquote component', () => {
            const str =
                `<blockquote class="quote">
                    <i>Lorem ipsum dolor sit amet, consectetur adipiscing</i>
                </blockquote>`;

            const result = [{
                tagName: 'blockquote',
                attributes: {},
                classes: ['quote'],
                type: 'quote',
                textComponent: true,
                components: [
                    {
                        tagName: 'i',
                        type: 'text',
                        ...textBlockOptions,
                        components: [
                            {
                                type: 'textnode',
                                content: 'Lorem ipsum dolor sit amet, consectetur adipiscing',
                                tagName: ''
                            }
                        ]
                    }
                ]
            }];

            expect(htmlParser(str).html).toEqual(result);
        });

        it('Parse text with few text tags', () => {
            const str =
                '<div id="test1"><br/> test2 <br/> a b <b>b</b> <i>i</i> <u>u</u> test </div>';
            const result = [
                {
                    tagName: 'div',
                    attributes: {
                        id: 'test1'
                    },
                    type: 'text',
                    components: [
                        {
                            tagName: 'br'
                        },
                        {
                            content: ' test2 ',
                            type: 'textnode',
                            tagName: ''
                        },
                        {
                            tagName: 'br'
                        },
                        {
                            content: ' a b ',
                            type: 'textnode',
                            tagName: ''
                        },
                        {
                            components: [
                                {
                                    type: 'textnode',
                                    content: 'b',
                                    tagName: ''
                                }
                            ],
                            type: 'text',
                            tagName: 'b',
                            ...textBlockOptions
                        },
                        {
                            content: ' ',
                            type: 'textnode',
                            tagName: ''
                        },
                        {
                            components: [
                                {
                                    type: 'textnode',
                                    content: 'i',
                                    tagName: ''
                                }
                            ],
                            tagName: 'i',
                            type: 'text',
                            ...textBlockOptions
                        },
                        {
                            content: ' ',
                            type: 'textnode',
                            tagName: ''
                        },
                        {
                            components: [
                                {
                                    type: 'textnode',
                                    content: 'u',
                                    tagName: ''
                                }
                            ],
                            tagName: 'u',
                            type: 'text',
                            ...textBlockOptions
                        },
                        {
                            content: ' test ',
                            type: 'textnode',
                            tagName: ''
                        }
                    ]
                }
            ];

            expect(htmlParser(str).html).toEqual(result);
        });

        it('Parse text with few text tags and nested node', () => {
            const str =
                '<div id="test1">a b <b>b</b> <i>i</i>c <div>ABC</div> <i>i</i> <u>u</u> test </div>';
            const result = [
                {
                    tagName: 'div',
                    attributes: {
                        id: 'test1'
                    },
                    type: 'text',
                    components: [
                        {
                            content: 'a b ',
                            type: 'textnode',
                            tagName: ''
                        },
                        {
                            components: [
                                {
                                    type: 'textnode',
                                    tagName: '',
                                    content: 'b'
                                }
                            ],
                            tagName: 'b',
                            ...textBlockOptions,
                            type: 'text'
                        },
                        {
                            content: ' ',
                            type: 'textnode',
                            tagName: ''
                        },
                        {
                            components: [
                                {
                                    type: 'textnode',
                                    tagName: '',
                                    content: 'i'
                                }
                            ],
                            tagName: 'i',
                            type: 'text',
                            ...textBlockOptions
                        },
                        {
                            content: 'c ',
                            type: 'textnode',
                            tagName: ''
                        },
                        {
                            tagName: 'div',
                            type: 'text',
                            components: [
                                {
                                    type: 'textnode',
                                    tagName: '',
                                    content: 'ABC'
                                }
                            ],
                            ...textBlockOptions
                        },
                        {
                            content: ' ',
                            type: 'textnode',
                            tagName: ''
                        },
                        {
                            components: [
                                {
                                    type: 'textnode',
                                    tagName: '',
                                    content: 'i'
                                }
                            ],
                            tagName: 'i',
                            type: 'text',
                            ...textBlockOptions
                        },
                        {
                            content: ' ',
                            type: 'textnode',
                            tagName: ''
                        },
                        {
                            components: [
                                {
                                    type: 'textnode',
                                    tagName: '',
                                    content: 'u'
                                }
                            ],
                            tagName: 'u',
                            type: 'text',
                            ...textBlockOptions
                        },
                        {
                            content: ' test ',
                            type: 'textnode',
                            tagName: ''
                        }
                    ]
                }
            ];

            expect(htmlParser(str).html).toEqual(result);
        });

        it('Parse nested text component with video', () => {
            const str =
                // eslint-disable-next-line
                `<h3><span>Lorem</span></h3><div class="frontpage-news-container"><div class="frontpage-news-left"><iframe src="https://www.youtube-nocookie.com/embed/test?&controls=0&showinfo=0&rel=0" allowfullscreen="allowfullscreen"></iframe></div><div class="frontpage-news-right"><div><h1>Lorem ipsum</h1><div><span>Lorem ipsum dolor sit amet, consectetur adipiscing elit</span></div><div>Lorem ipsum dolor sit amet, consectetur adipiscing elit</div></div></div></div>`;

            const result = [{
                tagName: 'h3',
                type: 'text',
                components: [{
                    tagName: 'span',
                    type: 'text',
                    ...textBlockOptions,
                    components: [{
                        content: 'Lorem',
                        tagName: '',
                        type: 'textnode'
                    }]
                }]
            }, {
                tagName: 'div',
                attributes: {},
                classes: ['frontpage-news-container'],
                components: [{
                    tagName: 'div',
                    attributes: {},
                    classes: ['frontpage-news-left'],
                    components: [{
                        type: 'video',
                        tagName: 'iframe',
                        provider: 'ytnc',
                        initial: true,
                        src: 'https://www.youtube-nocookie.com/embed/test?&controls=0&showinfo=0&rel=0',
                        attributes: {
                            allowfullscreen: 'allowfullscreen',
                            src: 'https://www.youtube-nocookie.com/embed/test?&controls=0&showinfo=0&rel=0'
                        }
                    }]
                }, {
                    tagName: 'div',
                    attributes: {},
                    classes: ['frontpage-news-right'],
                    type: 'text',
                    components: [{
                        tagName: 'div',
                        type: 'text',
                        ...textBlockOptions,
                        components: [{
                            tagName: 'h1',
                            type: 'text',
                            ...textBlockOptions,
                            components: [{
                                content: 'Lorem ipsum',
                                tagName: '',
                                type: 'textnode'
                            }]
                        }, {
                            tagName: 'div',
                            type: 'text',
                            ...textBlockOptions,
                            components: [{
                                tagName: 'span',
                                type: 'text',
                                ...textBlockOptions,
                                components: [{
                                    content: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit',
                                    tagName: '',
                                    type: 'textnode'
                                }]
                            }]
                        }, {
                            tagName: 'div',
                            type: 'text',
                            ...textBlockOptions,
                            components: [{
                                content: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit',
                                tagName: '',
                                type: 'textnode'
                            }]
                        }]
                    }]
                }]
            }];

            expect(htmlParser(str).html).toEqual(result);
        });

        it('Parse nested nodes', () => {
            const str =
                // eslint-disable-next-line
                '<article id="test1">   <div></div> <footer id="test2"></footer>  Text mid <div id="last"></div></article>';
            const result = [
                {
                    tagName: 'article',
                    type: 'text',
                    attributes: {
                        id: 'test1'
                    },
                    components: [
                        {
                            tagName: 'div'
                        },
                        {
                            content: ' ',
                            tagName: '',
                            type: 'textnode'
                        },
                        {
                            tagName: 'footer',
                            attributes: {
                                id: 'test2'
                            }
                        },
                        {
                            content: '  Text mid ',
                            tagName: '',
                            type: 'textnode'
                        },
                        {
                            tagName: 'div',
                            attributes: {
                                id: 'last'
                            }
                        }
                    ]
                }
            ];

            expect(htmlParser(str).html).toEqual(result);
        });

        it('Parse nested text nodes', () => {
            const str = '<div>content1 <div>nested</div> content2</div>';
            const result = [
                {
                    tagName: 'div',
                    type: 'text',
                    components: [
                        {
                            tagName: '',
                            type: 'textnode',
                            content: 'content1 '
                        },
                        {
                            tagName: 'div',
                            type: 'text',
                            ...textBlockOptions,
                            components: [{
                                type: 'textnode',
                                content: 'nested',
                                tagName: ''
                            }]
                        },
                        {
                            tagName: '',
                            type: 'textnode',
                            content: ' content2'
                        }
                    ]
                }
            ];

            expect(htmlParser(str).html).toEqual(result);
        });

        it('Parse nested span text styling', () => {
            const str = '<div>content1 <div><span data-type="text-style">nested</span></div> content2</div>';
            const result = [
                {
                    tagName: 'div',
                    type: 'text',
                    components: [
                        {
                            tagName: '',
                            type: 'textnode',
                            content: 'content1 '
                        },
                        {
                            tagName: 'div',
                            type: 'text',
                            ...textBlockOptions,
                            components: [
                                {
                                    tagName: 'span',
                                    type: 'text-style',
                                    attributes: {
                                        'data-type': 'text-style'
                                    },
                                    textComponent: true,
                                    components: [
                                        {
                                            type: 'textnode',
                                            content: 'nested',
                                            tagName: ''
                                        }
                                    ]
                                }
                            ]
                        },
                        {
                            tagName: '',
                            type: 'textnode',
                            content: ' content2'
                        }
                    ]
                }
            ];

            expect(htmlParser(str).html).toEqual(result);
        });

        it('Parse nested span text nodes', () => {
            const str = '<div>content1 <div><span>nested</span></div> content2</div>';
            const result = [
                {
                    tagName: 'div',
                    type: 'text',
                    components: [
                        {
                            tagName: '',
                            type: 'textnode',
                            content: 'content1 '
                        },
                        {
                            tagName: 'div',
                            type: 'text',
                            ...textBlockOptions,
                            components: [
                                {
                                    tagName: 'span',
                                    type: 'text',
                                    ...textBlockOptions,
                                    components: [
                                        {
                                            type: 'textnode',
                                            content: 'nested',
                                            tagName: ''
                                        }
                                    ]
                                }
                            ]
                        },
                        {
                            tagName: '',
                            type: 'textnode',
                            content: ' content2'
                        }
                    ]
                }
            ];

            expect(htmlParser(str).html).toEqual(result);
        });

        it('Parse multiple nodes', () => {
            const str = '<div></div><div></div>';
            const result = [
                {
                    tagName: 'div'
                },
                {
                    tagName: 'div'
                }
            ];
            expect(htmlParser(str).html).toEqual(result);
        });

        xit('Isolate styles', () => {
            const str =
                '<div><style>.a{color: red}</style></div><div></div><style>.b{color: blue}</style>';
            const resHtml = [
                {
                    tagName: 'div',
                    components: []
                },
                {
                    tagName: 'div'
                }
            ];
            const resCss = [
                {
                    selectors: ['a'],
                    style: {
                        color: 'red'
                    }
                },
                {
                    selectors: ['b'],
                    style: {
                        color: 'blue'
                    }
                }
            ];
            const res = htmlParser(str);
            expect(res.html).toEqual(resHtml);
            expect(res.css).toEqual(resCss);
        });

        it('Parse nested div with text and spaces', () => {
            const str = '<div> <p>TestText</p> </div>';
            const result = [
                {
                    tagName: 'div',
                    type: 'text',
                    components: [
                        {
                            type: 'textnode',
                            tagName: '',
                            content: ' '
                        },
                        {
                            tagName: 'p',
                            ...textBlockOptions,
                            type: 'text',
                            components: [
                                {
                                    type: 'textnode',
                                    tagName: '',
                                    content: 'TestText'
                                }
                            ]
                        },
                        {
                            type: 'textnode',
                            tagName: '',
                            content: ' '
                        }
                    ]
                }
            ];

            expect(htmlParser(str).html).toEqual(result);
        });

        it('Parse node with model attributes to fetch', () => {
            const str =
                '<div id="test1" data-test="test-value" ' +
                'data-gjs-draggable=".myselector" ' +
                'data-gjs-stuff="test">test2 </div>';
            const result = [
                {
                    tagName: 'div',
                    draggable: '.myselector',
                    stuff: 'test',
                    attributes: {
                        'id': 'test1',
                        'data-test': 'test-value'
                    },
                    type: 'text',
                    components: [
                        {
                            type: 'textnode',
                            tagName: '',
                            content: 'test2 '
                        }
                    ]
                }
            ];
            expect(htmlParser(str).html).toEqual(result);
        });

        it('Parse model attributes with true and false', () => {
            const str =
                '<div id="test1" data-test="test-value" ' +
                'data-gjs-draggable="true" ' +
                'data-gjs-stuff="false">test2 </div>';
            const result = [
                {
                    tagName: 'div',
                    draggable: true,
                    stuff: false,
                    attributes: {
                        'id': 'test1',
                        'data-test': 'test-value'
                    },
                    type: 'text',
                    components: [{
                        type: 'textnode',
                        tagName: '',
                        content: 'test2 '
                    }]
                }
            ];
            expect(htmlParser(str).html).toEqual(result);
        });

        it('Parse attributes with object inside', () => {
            const str = `<div data-gjs-test='{ "prop1": "value1", "prop2": 10, "prop3": true}'>test2 </div>`;
            const result = [
                {
                    tagName: 'div',
                    attributes: {},
                    type: 'text',
                    test: {
                        prop1: 'value1',
                        prop2: 10,
                        prop3: true
                    },
                    components: [{
                        type: 'textnode',
                        tagName: '',
                        content: 'test2 '
                    }]
                }
            ];

            expect(htmlParser(str).html).toEqual(result);
        });

        it('Parse attributes with arrays inside', () => {
            const str = `<div data-gjs-test='["value1", "value2"]'>test2 </div>`;
            const result = [
                {
                    tagName: 'div',
                    attributes: {},
                    type: 'text',
                    test: ['value1', 'value2'],
                    components: [{
                        type: 'textnode',
                        tagName: '',
                        content: 'test2 '
                    }]
                }
            ];
            expect(htmlParser(str).html).toEqual(result);
        });

        it('SVG is properly parsed', () => {
            /* eslint-disable */
            const str = `<div>
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
        <path d="M13 12h7v1.5h-7m0-4h7V11h-7m0 3.5h7V16h-7m8-12H3c-1.1 0-2 .9-2 2v13c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2m0 15h-9V6h9"></path>
      </svg>
    </div>`;
            const result = [
                {
                    tagName: 'div',
                    components: [
                        {
                            type: 'svg',
                            tagName: 'svg',
                            attributes: {
                                xmlns: 'http://www.w3.org/2000/svg',
                                viewBox: '0 0 24 24'
                            },
                            components: [
                                {
                                    tagName: 'path',
                                    attributes: {
                                        d: 'M13 12h7v1.5h-7m0-4h7V11h-7m0 3.5h7V16h-7m8-12H3c-1.1 0-2 .9-2 2v13c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2m0 15h-9V6h9',
                                    },
                                    type: 'svg-in',
                                }
                            ]
                        }
                    ]
                }
            ];
            /* eslint-disable */
            expect(htmlParser(str).html).toEqual(result);
        });

        it('Tag section with text', () => {
            const str = `<section><h1>Insert title here</h1><p>Lorem ipsum dolor</p></section>`;
            const result = [{
                tagName: 'section',
                type: 'text',
                components: [
                    {
                        tagName: 'h1',
                        type: 'text',
                        ...textBlockOptions,
                        components: [{
                            tagName: '',
                            type: 'textnode',
                            content: 'Insert title here'
                        }]
                    },
                    {
                        tagName: 'p',
                        type: 'text',
                        ...textBlockOptions,
                        components: [{
                            tagName: '',
                            type: 'textnode',
                            content: 'Lorem ipsum dolor'
                        }]
                    }
                ]
            }];

            expect(htmlParser(str).html).toEqual(result);
        });
    });
});
