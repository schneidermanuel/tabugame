import Vue from 'vue'
import App from './App.vue'
import router from './router'
import vuetify from './plugins/vuetify'
import store from './store'

Vue.config.productionTip = false

import {library} from '@fortawesome/fontawesome-svg-core'

/* import font awesome icon component */
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome'

/* import specific icons */
import {faDiscord} from '@fortawesome/free-brands-svg-icons'
import {faPlay, faCheck, faTrash} from "@fortawesome/free-solid-svg-icons";

library.add(faDiscord);
library.add(faPlay);
library.add(faCheck);
library.add(faTrash);

/* add font awesome icon component */
Vue.component('font-awesome-icon', FontAwesomeIcon)

Vue.config.productionTip = false
Vue.use(require('vue-cookies'))
new Vue({
    router,
    store,
    vuetify,
    render: function (h) {
        return h(App)
    }
}).$mount('#app')
