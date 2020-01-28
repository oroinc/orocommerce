export default `
    /* GrapesJS Canvas CSS */
    
    .gjs-comp-selected {
        outline: 3px solid #0c809e !important;
    }
    
    #wrapper {
        padding: 3px;
    }
    
    * ::-webkit-scrollbar {
        width: 5px
    }
    
    ::-webkit-scrollbar-track {
        background: #f3f3f3
    }
    
    ::-webkit-scrollbar-thumb {
        background: #e3e3e4
    }
    
    .content-placeholder {
        background-color: #f8f8f8;
        padding: 54px 15px;
        text-align: center;
        font-family: Arial, Helvetica, sans-serif;
    }
    
    .content-placeholder .content-placeholder-title {
        font-size: 14px;
        color: #565656;
        margin-bottom: 5px;
    }
    
    .content-placeholder p {
        font-size: 13px;
        color: #878789;
        line-height: 1.31;
        margin: 0;
    }
    
    span.content-widget {
        display: inline-block;
        background-color: #f8f8f8;
        font-size: 13px;
        color: #878789;
        line-height: 1.31;
        white-space: nowrap;
        padding: 1px 6px;
        margin: 0 4px;
    }
    
    span.content-widget span {
        font-weight: bold;
        color: #565656;
    }
    
    .row {
        margin-left: 0;
        margin-right: 0;
    }
    
    .gjs-dashed *[data-highlightable] {
        outline-offset: -1px;
    }
`;
