<template>
  <div>
    <main>
      <div class="container">
          <router-view></router-view>
        <router-link :to="{ name: 'Shift'}">Shift</router-link>
        <router-link :to="{ name: 'Login'}">Login</router-link>
        <router-link :to="{ name: 'Register'}">Register</router-link>
      <button v-on:click="logout">ログアウト</button>
          <button v-on:click="check">check</button>
      </div>
      <div>
          <h1>{{this.$store.state.test}}</h1>
      </div>
    </main>
  </div>
</template>
<script>
export default {
  name: "App",
  components: {},
  methods: {
    logout() {
      this.$store.dispatch("logout")
      },
      check() {
        this.$store.dispatch("check")
      }
    }
}
</script>

//ルートでログインしているかしていないか分ける


//ログインしていない場合はログイン画面に遷移
//ログインしてる場合はホームに飛ばす

//ログイン
//Ajaxでポスト通信 →
//ログイン情報を問い合わせ
//セッションに格納

//ログアウト
//キャッシュを削除
