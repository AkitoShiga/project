import Vue from 'vue'
import VueRouter from 'vue-router'

// ページコンポーネントをインポートする
import Shift from './pages/Shift.vue'
import Login from './pages/Login.vue'
import Register from './pages/Register.vue'

// VueRouterプラグインを使用する
// これによって<RouterView />コンポーネントなどを使うことができる
Vue.use(VueRouter)

// パスとコンポーネントのマッピング
const routes = [
  {
    path: '/',
    component: Shift,
    name: 'Shift',
  },
  {
    path: '/Login',
    component: Login,
    name: 'Login',
  },
  {
    path: '/Resister',
    component: Register,
    name: 'Register',
  },
]

// VueRouterインスタンスを作成する
const router = new VueRouter({
    mode: 'history',
    routes
})

// VueRouterインスタンスをエクスポートする
// app.jsでインポートするため
export default router
