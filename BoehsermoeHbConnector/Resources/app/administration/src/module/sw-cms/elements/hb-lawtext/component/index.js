import template from './sw-cms-el-hblawtext.html.twig';
import './sw-cms-el-hblawtext.scss';

Shopware.Component.register('sw-cms-el-hblawtext', {
    template,

    mixins: [
        'cms-element'
    ],

    computed: {
        siteId() {
            return this.element.config.siteId.value;
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('hb-lawtext-site');
        }
    }
});

