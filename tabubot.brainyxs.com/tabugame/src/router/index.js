import Vue from 'vue'
import VueRouter from 'vue-router'
import HomeView from '../views/HomeView.vue'
import AuthenticationView from "@/views/AuthenticationView.vue";
import GameView from '@/views/GameView.vue'

Vue.use(VueRouter)

const routes = [
    {
        path: '/',
        name: 'home',
        component: HomeView
    },
    {
        path: '/authenticated/:token',
        name: 'Authenticating...',
        component: AuthenticationView
    },
    {
        path: '/game/:id',
        name: 'Game',
        component: GameView
    }
]

const router = new VueRouter({
    mode: 'history',
    base: process.env.BASE_URL,
    routes
})

export default router
