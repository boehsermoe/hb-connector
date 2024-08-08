/**
 * @class
 * @property {AxiosInstance} httpClient
 */
export default class ApiService {
    /**
     * @constructor
     * @param {AxiosInstance} httpClient
     */
    constructor(httpClient) {
        this.httpClient = httpClient;
    }

    /**
     * @returns {Promise}
     */
    sync(context) {
        return this.httpClient
            .get('/hblawtext/sync', {
                headers: {
                    Authorization: `Bearer ${context.authToken.access}`,
                    'Content-Type': 'application/json',
                }
            });
    }
}
