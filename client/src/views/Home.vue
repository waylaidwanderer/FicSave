<template>
  <div class="page">
    <div style="padding-bottom: 1em;">
      <h1>FicSave</h1>
      <h3>An Open-Source Online Fanfiction Downloader</h3>
    </div>
    <div>
      <label for="story-url">Story URL</label>
      <input type="url" id="story-url" v-model="storyUrl" autocomplete="on"/>
      <label for="format">Format</label>
      <select id="format" disabled v-model="format">
        <option value="epub" selected>ePub</option>
      </select>
      <label for="email">Email (optional)</label>
      <input type="email" id="email" v-model="email" autocomplete="on"/>
      <button @click="download">Download</button>
    </div>
    <div v-show="Object.values(stories).length > 0">
      <h3>Downloaded Stories</h3>
      <ul>
        <li v-for="story in Object.values(stories)"
            :key="story.key">
          <a v-if="story.url"
             :href="story.url">
            {{ story.key }}
          </a>
          <span v-else>
            {{ story.key }} -
            <strong v-if="story.error" style="color: darkred;">{{ story.error }}</strong>
            <template v-else>
              {{ story.progress || 0 }}%
            </template>
          </span>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex';

export default {
  name: 'home',
  data() {
    return {
      storyUrl: '',
      format: 'epub',
      email: '',
    };
  },
  computed: {
    ...mapState([
      'stories',
    ]),
  },
  sockets: {
    complete({ url }) {
      window.location.href = url;
    },
  },
  methods: {
    download() {
      this.$socket.emit('download', {
        url: this.storyUrl,
        format: this.format,
        email: this.email,
      });
    },
  },
};
</script>
