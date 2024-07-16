import template from './sw-cms-el-config-hblawtext.html.twig';

Shopware.Component.register('sw-cms-el-config-hblawtext', {
    template,

    mixins: [
        'cms-element'
    ],

    computed: {
        siteId: {
            get() {
                return this.element.config.siteId.value;
            },

            set(value) {
                this.element.config.siteId.value = value;
            }
        }
    },

    data() {
        return {
            siteIdOptions: [
                {
                    label: this.$tc('AGB'),
                    value: 'agb',
                },
                {
                    label: this.$tc('Widerruf'),
                    value: 'widerruf',
                },
                {
                    label: this.$tc('Widerruf download'),
                    value: 'widerruf_download',
                },
                {
                    label: this.$tc('Widerruf Reperatur'),
                    value: 'widerruf_reperatur',
                },
                {
                    label: this.$tc('Zahlungundversand'),
                    value: 'zahlungundversand',
                },
                {
                    label: this.$tc('Datenschutz'),
                    value: 'datenschutz',
                },
                {
                    label: this.$tc('Impressum'),
                    value: 'impressum',
                },
                {
                    label: this.$tc('Batterie'),
                    value: 'batterie',
                },
            ],
        };
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('hb-lawtext-site');
        },

        onElementUpdate(value) {
            this.element.config.siteId.value = value;

            this.$emit('element-update', this.element);
        }
    }
});