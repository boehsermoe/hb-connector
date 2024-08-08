import template from './request-button.html.twig';

const { Component } = Shopware;

Component.register('hblawtext-request-button', {
    template,

    init() {
        this._client = new HttpClient();
    },

    inject: [
        'hblawtextApiService',
    ],

    data() {
        return {
            context: Shopware.Context.api,
            loading: false,
        }
    },

    props: {
        value: {
            required: true,
        },
    },

    methods: {

        async onClick() {
            this.loading = true;

            try {
                const result = await this.hblawtextApiService.sync(this.context);
                console.log(result);

                if (result.data.status === 'success') {
                    console.log('Success')
                } else {
                    console.log('Failed')
                }

            } catch (e) {
                console.log(e);
            } finally {
                this.loading = false;
            }
        },

        handleData(response) {
            console.log(response);
        }
    },
});