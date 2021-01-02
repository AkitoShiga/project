<template>
  <div>
    <h1>login</h1>
    <form @submit.prevent="login">
      <label for="email">Email</label>
      <input type="text" id="email" v-model="loginForm.email" />
      <label for="password">Password</label>
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
        password: "",
        device_name: ""
      }
    };
  },
  methods: {
    login() {
        let data = this.loginForm;
        data.device_name = navigator.userAgent;
      this.$store.dispatch("login", data).then(() => {
        this.$router.push({ name: "dashboard" });
      });
    }
  }
};
</script>