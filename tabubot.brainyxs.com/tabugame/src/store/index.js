import Vue from 'vue'
import Vuex from 'vuex'

Vue.use(Vuex)

export default new Vuex.Store({
    state: {
        snackbar: {
            show: false,
            timeout: 2000,
            color: 'green',
            message: ''
        },
        loading: false,
        inited: false,
        user: {
            username: null,
            pburl: null,
            token: null,
        }
    },
    getters: {
    },
    mutations: {
    },
    actions: {
    },
    modules: {
    }
})