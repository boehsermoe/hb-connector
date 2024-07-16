import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'hb-lawtext',
    category: 'text',
    label: 'sw-cms.elements.hb-lawtext.label',
    component: 'sw-cms-block-hb-lawtext',
    previewComponent: 'sw-cms-preview-hb-lawtext',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed',
    },
    slots: {
        siteId: 'hb-lawtext-site',
    }
});