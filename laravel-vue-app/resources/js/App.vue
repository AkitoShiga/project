<template>
  <div>
    <main>
      <div class="container">
          <router-view></router-view>
        <router-link :to="{ name: 'shift'}">Shift</router-link>
        <router-link :to="{ name: 'login'}">Login</router-link>
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
