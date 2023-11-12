<template>
  <v-container style="font-family: Agbalumo; color: white; font-size: 120%">
    <v-row>
      <v-spacer/>
      <v-col cols="6" style="text-align: center"><h3>TABU!</h3></v-col>
      <v-spacer/>
    </v-row>
    <v-row>
      <v-col cols="6" md="3" style="text-align: center;">
        <h4>Team Blue<br><br></h4>
        <div style="height: 100%; background-color: red" class="avatarContainer">
          <div v-for="player in lobby.bluePlayers">
            <h5>{{ player.Name}}</h5>
            <v-avatar size="80" class="userAvatar">
              <img :src="player.ImageUrl"/>
            </v-avatar>
          </div>
        </div>
      </v-col>
      <v-spacer></v-spacer>
      <v-col cols="6" md="3" style="text-align: center">
        <h4>Team Red<br><br></h4>
        <div style="height: 100%; background-color: red" class="avatarContainer">
          <div v-for="player in lobby.redPlayers">
            <h5>{{ player.Name}}</h5>
            <v-avatar size="80" class="userAvatar">
              <img :src="player.ImageUrl"/>
            </v-avatar>
          </div>
        </div>
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
        bluePlayers: [],
        redPlayers: [],
        host: null
      },
      eventSource: null
    }
  },
  methods: {
    init() {
      this.$store.state.loading = true;
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
                this.lobby.Players.forEach(player => {
                  if (player.Team == "blue") {
                    this.lobby.bluePlayers.push(player);
                  } else {
                    this.lobby.redPlayers.push(player);
                  }
                });
              }
              this.$store.state.loading = false;
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
.userAvatar {
  margin-top: 5%;
}

.avatarContainer {
  display: flex;
  flex-direction: column;
  align-items: center;
}
</style>