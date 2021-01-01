import Vue from 'vue'
import Vuex from 'vuex'
import Axios from 'axios'
Vue.use(Vuex)
const state = {
    user:null,
    token:window.localStorage.getItem('token')
}
const actions = {
    login(context, data) {
        let url = '127.0.0.1:8000/api/login';
        let url2 = 'localhost:8000';
        let url3 = '/api/login';
        axios.post(url3, data).then((result)=> {
            context.commit("setUser", result.data.user);
            context.commit("setToken", result.data.token);
        }).catch(error => {
            console.log(`Error! HTTP Status: ${error}` );
        })
    },
    register(context, data) {
        let url3 = '/api/register';
        axios.post(url3, data).then((result) => {
            context.commit("setUser", result.data.user);
            context.commit("setToken", result.data.token);
        }).catch(error => {
            console.log(`Error! HTTP Status: ${error}`);
        });
    },
}
const mutations = {
    setUser(state, user) {
        state.user = user;
    },
    setToken(state, token) {
        window.localStorage.setItem('token', token);
    },
    logout(context) {
        axios.post(BASE_URL + 'api/logout', null, {
            headers: {
                Authrization: `Bearer ${state.token}`,
            }
        }).then((result) => {
            context.commit("setUser", null);
            context.commit("setToken", null);
        }).catch(error => {
            console.log(`Error! HTTP Status: ${error}`);
        });
    },
}
const store = new Vuex.Store({
    state: state,
    actions: actions,
    mutations: mutations,
});
export default store;

