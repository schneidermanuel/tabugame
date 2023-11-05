<template>
  <v-container style="font-family: Agbalumo; color: white; font-size: 120%">
    <v-row>
      <v-spacer/>
      <v-col cols="6" style="text-align: center"><h3>TABU!</h3></v-col>
      <v-spacer/>
    </v-row>
    <v-row>
      <v-col cols="6" md="4" style="text-align: center;">
        <h4>Team Blue</h4>
      </v-col>
      <v-spacer></v-spacer>
      <v-col cols="6" md="4" style="text-align: center">
        <h4>Team Red</h4>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>

export default {
  data() {
    return {
      lobby: {
        Players: [],
        host: null
      },
      eventSource: null
    }
  },
  methods: {
    init() {
      let code = this.$router.currentRoute.params["id"]
      let token = this.$store.state.user.token;
      fetch("https://api.tabubot.brainyxs.com/game/" + code + "/otp", {
        headers: {
          "Authorization": "Bearer " + token
        }
      })
          .then(data => data.text())
          .then(otp => {
            let url = "https://api.tabubot.brainyxs.com/game/" + code + "/lobby/" + otp;
            this.eventSource = new EventSource(url);
            this.eventSource.onmessage = (e) => {
              let data = JSON.parse(e.data);
              if (data.Type == "INFO") {
                console.log(e.data);
              }
              if (data.Type == "INIT") {
                this.lobby.Players = data.Content.Players;
                console.log(this.lobby.Players);
              }
            }
          });
    },
    Stop() {
      this.eventSource.close();
    }
  },
  async created() {
    this.init();
  }
}
</script>

<style>

</style>