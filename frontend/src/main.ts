import Vue = require('vue');
import VueRouter = require('vue-router');
import {App} from './app';
import {configureRouter} from './route-config';
/**
 * Application Main Entry Point here!!
 *
 * vue init, router init and config
 * routing start!!
 */


// Router -----------------------------

Vue.use(VueRouter);
const router = new VueRouter<App>();
configureRouter(router);

router.start(App, '#app');
export default router; // エクスポートしたrouter これを使うことで任意にルーティングできる

//--------------------------------------

