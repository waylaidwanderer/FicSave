<template>
  <div id="app">
    <router-view/>
    <footer>
      Copyright © 2015 - 2019 FicSave.xyz. All Rights Reserved.
    </footer>
  </div>
</template>

<script>
import cryptoRandomString from 'crypto-random-string';

import store from './store';

export default {
  name: 'App',
  store,
  mounted() {
    let idToken;
    try {
      idToken = window.localStorage.getItem('idToken');
    } catch (err) {
      // ignored
    }
    if (!idToken) {
      idToken = cryptoRandomString({ length: 32 });
    }
    this.$store.commit('setIdToken', { idToken });
    try {
      window.localStorage.setItem('idToken', idToken);
    } catch (err) {
      // ignored
    }
    this.$socket.io.opts.query = {
      token: idToken,
    };
    this.$socket.connect();
  },
};
</script>

<style lang="scss">
#app {
  font-family: Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
}

footer {
  margin-top: 2em;
}
</style>
