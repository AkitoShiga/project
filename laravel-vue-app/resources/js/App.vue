<template>
  <div>
    <main>
      <div class="container">
          <router-view></router-view>
        <router-link :to="{ name: 'Login'}">Login</router-link>
      </div>
    </main>
  </div>
</template>