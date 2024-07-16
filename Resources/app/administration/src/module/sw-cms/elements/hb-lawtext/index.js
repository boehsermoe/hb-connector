import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'hb-lawtext-site',
    label: 'sw-cms.elements.hb-lawtext.label',
    component: 'sw-cms-el-hblawtext',
    configComponent: 'sw-cms-el-config-hblawtext',
    previewComponent: 'sw-cms-el-preview-hblawtext',
    defaultConfig: {
        siteId: {
            source: 'static',
            value: ''
        },
    }
});