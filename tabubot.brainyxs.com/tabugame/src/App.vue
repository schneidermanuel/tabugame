<template>
  <v-app>
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

    <v-main>
      <router-view/>
    </v-main>
  </v-app>
</template>

<script>

export default {
  name: 'App',

  data: () => ({
    //
  }),
};
</script>
