import Vue from 'vue'
import VueRouter from 'vue-router'

// ページコンポーネントをインポートする
import Shift from './pages/Shift.vue'
import Login from './pages/Login.vue'

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
    path: '/login',
    component: Login,
    name: 'Login',
  }
]

// VueRouterインスタンスを作成する
const router = new VueRouter({
    mode: 'history',
    routes
})

// VueRouterインスタンスをエクスポートする
// app.jsでインポートするため
export default router
