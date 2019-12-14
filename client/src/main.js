import Vue from 'vue';
import VueSocketIO from 'vue-socket.io';

import App from './App.vue';
import router from './router';
import store from './store';

Vue.config.productionTip = false;

Vue.use(new VueSocketIO({
  debug: true,
  connection: 'http://localhost:3000',
  vuex: {
    store,
    actionPrefix: 'SOCKET_',
    mutationPrefix: 'SOCKET_',
  },
  options: {
    query: {
      token: 'test',
    },
  },
}));

new Vue({
  router,
  store,
  render(h) { return h(App); },
}).$mount('#app');
