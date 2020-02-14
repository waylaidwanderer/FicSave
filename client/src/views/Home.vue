<template>
  <div class="page">
    <div style="padding-bottom: 1em;">
      <h1>FicSave<sup style="font-size: small;">2.0 Beta</sup></h1>
      <h3>An Open-Source Online Fanfiction Downloader</h3>
    </div>
    <div>
      <div style="display: inline-block; margin-right: 1em;">
        <label for="story-url" style="margin-right: 0.333em;">Story URL</label>
        <input type="url" id="story-url" v-model="storyUrl" autocomplete="on"/>
      </div>
      <div style="display: inline-block; margin-right: 1em;">
        <label for="format" style="margin-right: 0.333em;">Format</label>
        <select id="format" disabled v-model="format">
          <option value="epub" selected>ePub</option>
        </select>
      </div>
      <!--
      <label for="email">Email (optional)</label>
      <input type="email" id="email" v-model="email" autocomplete="on"/>
      -->
      <div style="display: inline-block;">
        <button @click="download">Download</button>
      </div>
    </div>
    <div style="text-align: center;" v-show="error">
      <p style="color: darkred;">{{ error }}</p>
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
    <div>
      <h5 style="margin-bottom: 0;">Supported Sites</h5>
      <p style="font-size: small; margin-top: 0.5em;">
        - fanfiction.net<br/>
        - fictionpress.com<br/>
        - adult-fanfiction.org
      </p>
    </div>
    <p>
      <a href="https://github.com/waylaidwanderer/FicSave" target="_blank">GitHub</a>
      &#183;
      <a href="https://twitter.com/FicSave" target="_blank">Twitter</a>
    </p>
    <div style="padding-top: 1em; font-size: small;">
      <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
        <input type="hidden" name="cmd" value="_s-xclick">
        <input type="hidden" name="hosted_button_id" value="CTVGYBHBT475W">
        <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" name="submit" alt="PayPal - The safer, easier way to pay online!" border="0">
        <img alt="" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" style="display: none !important;" width="1" hidden="" height="1" border="0">
      </form>
      <div>
        Bitcoin: <em>37uVATU3kh2boAECGYBwDZRjPZPr1TCB9J</em><br/>
        Bitcoin Cash: <em>1KEgR1P2SgjSb6ftVJ7jaxdNqyRY2NhNTL</em><br/>
        Ethereum: <em>0x1eC8A54Dd96190f57a94a745499C641Be6cB80b9</em>
      </div>
      <p>
        <strong>Every donation helps keep the site running!</strong>
      </p>
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
      error: '',
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
    err({ key, msg }) {
      if (key) {
        return;
      }
      this.error = msg;
      setTimeout(() => {
        this.error = '';
      }, 10 * 1000);
    },
  },
  methods: {
    download() {
      this.storyUrl = this.storyUrl.trim();
      if (!this.storyUrl) {
        return;
      }
      this.$socket.emit('download', {
        url: this.storyUrl,
        format: this.format,
        email: this.email,
      });
      this.storyUrl = '';
    },
  },
};
</script>
