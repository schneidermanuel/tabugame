<template>
  <v-app id="app">
    <v-app-bar
        app
        color="primary"
        dark
    >
      <div class="d-flex align-center">
        <h1>Tabugame&emsp;</h1>
        <h4>by BrainyXS</h4>
      </div>

      <v-spacer></v-spacer>
      <v-btn text href="https://twitch.tv/brainyxs" target="_blank">
        <v-icon>mdi-twitch</v-icon>
      </v-btn>
      <v-btn text href="https://github.com/schneidermanuel" target="_blank">
        <v-icon>mdi-github</v-icon>
      </v-btn>
    </v-app-bar>

    <v-snackbar id="snackbar" v-model="$store.state.snackbar.show" bottom style="margin-bottom: 5%"
                :color="$store.state.snackbar.color" :timeout="$store.state.snackbar.timeout">
      {{ $store.state.snackbar.message }}
      <template v-slot:action="{ attrs }">
        <v-btn dark text @click="$store.state.snackbar.show = false" v-bind="attrs">Ok</v-btn>
      </template>
    </v-snackbar>
    <v-main>
      <router-view v-if="this.$store.state.inited" v-show="!this.$store.state.loading"/>
      <Loader v-show="this.$store.state.loading"/>
    </v-main>
  </v-app>
</template>

<script>

import Loader from "@/components/Shared/Loader.vue";

export default {
  name: 'App',
  components: {Loader},

  data: () => ({}),
  methods: {
    init() {
      this.$store.state.loading = true;
      let token = this.$cookies.get("jwt");
      if (token != null) {
        fetch("https://api.tabubot.brainyxs.com/user/me", {
          method: 'GET',
          headers: {
            "Authorization": "Bearer " + token
          }
        }).then(data => data.json())
            .then(data => {
              if (data.authenticated) {
                this.$store.state.user.username = data.user.username;
                this.$store.state.user.pburl = data.user.pburl;
                this.$store.state.user.token = token;
                this.$store.state.loading = false;
                this.$store.state.inited = true;
              }
            });
      } else {

        this.$store.state.loading = false;
        this.$store.state.inited = true;
      }
    }
  },
  async created() {
    this.init();
  }
}
;
</script>

<style>
#app {
  background-color: #3d424a;
}

.container {
  height: 100%;
}

.verticalSpacer {
  height: 20%;
}
</style>