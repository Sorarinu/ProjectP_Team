import router from '../../main';
import {Component} from '../../vue-typed/vue-typed';

/**
 * IndexPage Component
 */
require('./css/index.scss');
require('./css/layout_top.scss');
@Component({
    template: require('./index.html'),
    components: {
        alert: require('vue-strap').alert,
    },
})
export class Index {
    goAbout() {
        router.go('main');
    }

    goSignIn() {

        router.go('signin');

    }

    goSignUp() {
        router.go('signup');
    }

    data() {
        return {
            movie: require('./video/movie_pc.mp4'),
        };
    }
}
