import Vue from 'vue';
import Vuex from 'vuex';

Vue.use(Vuex);

export default new Vuex.Store({
  state: {
    stories: {},
  },
  mutations: {
    SOCKET_progress(state, { key, progress }) {
      Vue.set(state.stories, key, {
        key,
        progress,
      });
    },
    SOCKET_error(state, { key, msg }) {
      Vue.set(state.stories, key, {
        key,
        error: msg,
      });
    },
    SOCKET_complete(state, { key, url }) {
      Vue.set(state.stories, key, {
        key,
        url,
      });
    },
  },
  actions: {

  },
});
