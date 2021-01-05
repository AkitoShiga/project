import Vue from "vue";
import VueRouter from "vue-router";

Vue.use(VueRouter);

import login from "./components/login.vue";
import shift from "./components/shift.vue";
import master from "./components/master.vue";

const router = new VueRouter({
    mode: "history",
    routes: [
        {
            path: "/login",
            name: "login",
            component: login,
            meta: { guestOnly: true }
        },
        {
            path: "/shift",
            name: "shift",
            component: shift,
            meta: { authOnly: true }
        },
        {
            path: "/master",
            name: "master",
            component: master,
            meta: { authOnly: true }
        }
    ]
});

function isLoggedIn() {
    return localStorage.getItem("auth");
}

router.beforeEach((to, from, next) => {
    if (to.matched.some(record => record.meta.authOnly)) {
        if (!isLoggedIn()) {
            next("/login");
        } else {
            next();
        }
    } else if (to.matched.some(record => record.meta.guestOnly)) {
        if (isLoggedIn()) {
            next("/shift");
        } else {
            next();
        }
    } else {
        next();
    }
});

export default router;