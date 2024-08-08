import ApiService from '../service/api.service.js';

const { Application } = Shopware;

Application.addServiceProvider('hblawtextApiService', container => {
    const initContainer = Shopware.Application.getContainer('init');
    return new ApiService(initContainer.httpClient);
});
