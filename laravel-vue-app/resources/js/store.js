import Vue from 'vue'
import Vuex from 'vuex'
import Axios from 'axios'
Vue.use(Vuex)
const state = {
    user:null,
    token:window.localStorage.getItem('token'),
}
const actions = {
    login(context, data) {
        let url3 = '/api/login';
        axios.post(url3, data).then((result)=> {
            context.commit("setUser", result.data.user);
            context.commit("setToken", result.data.token);
        }).catch(error => {
            console.log(`Error! HTTP Status: ${error}`, error.response.data );
        })
    },
    register(context, data) {
        let url3 = '/api/register';
        axios.post(url3, data).then((result) => {
            context.commit("setUser", result.data.user);
            context.commit("setToken", result.data.token);
        }).catch(error => {
            console.log(`Error! HTTP Status: ${error}`, error.response.data);
        });
    },
    logout(context) {
        axios.post('/api/logout', state.user, {
            headers: {
                Authrization: `Bearer ${state.token}`,
            }
        }).then((result) => {
            context.commit("setUser", null);
            context.commit("setToken", null);
        }).catch(error => {
            console.log(`Error! HTTPsymotion-overwin-f2) Status: ${error}`, error.response.data);
        });
    },
    check(context){
        console.log("(state.token)" + state.token);
        console.log("(localStorage)" + window.localStorage.getItem('token'));
        console.log("(state.user)" + state.user);
    }
}
const mutations = {
   setUser(state, user) {
        state.user = user;
    },
   setToken(state, token) {
       state.token = token;
           window.localStorage.setItem('token', token);
    },
}
const store = new Vuex.Store({
    state: state,
    actions: actions,
    mutations: mutations,
});
export default store;

