import axios from 'axios';
import { getAppUrl } from './utils';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const appUrl = getAppUrl();
if (appUrl) {
    window.axios.defaults.baseURL = appUrl;
}
