<template>
<div>
    <h1>ログイン</h1>
    <form @submit.prevent="login">
        <label for="email">Email</label>
        <input type="text" id="email" v-model="loginForm.email" />
        <label form="password">Password</label>
        <input type="password" id="password" v-model="loginForm.password" />
        <button type="submit">login</button>
    </form>
</div>
</template>
<script>
export default {
    name: "login",
    components: {},
    data() {
        return {
            loginForm: {
                email: "",
                password: ""
            }
        };
    },
    methods: {
        login() {
            this.$store.dispatch("login", this.loginForm).then(() => {
                this.$router.push({ name: "shift"});
            });
        }
    }
}
</script>