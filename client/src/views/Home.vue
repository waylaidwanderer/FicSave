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
  </div>
</template>

<script>
export default {
  name: 'home',
  data() {
    return {
      storyUrl: '',
      format: 'epub',
      email: '',
    };
  },
  methods: {
    download() {
      console.log(this.storyUrl, this.format, this.email);
      this.$socket.emit('download', {
        url: this.storyUrl,
        format: this.format,
        email: this.email,
      });
    },
  },
};
</script>
