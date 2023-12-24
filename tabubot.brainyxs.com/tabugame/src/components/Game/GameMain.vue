<template>
  <v-container style="font-family: Agbalumo; color: white; font-size: 120%">
    <v-row>
      <v-col cols="2">
        Blue: 0 Points
      </v-col>
      <v-col cols="8" class="text-center">
        Playername's Turn
      </v-col>
      <v-col cols="2">
        Red: 0 Points
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="2">
        <div style="background-color: #4fcaff; border-radius: 12px" class="avatarContainer">
          <div v-for="player in bluePlayers">
            <h5>{{ player.Name }}</h5>
            <v-avatar size="80" class="userAvatar" :class="{ host: player.IsHost == 1 }">
              <img :src="player.ImageUrl"/>
            </v-avatar>
          </div>
        </div>
      </v-col>
      <v-col cols="8" class="text-center">
        CARD
      </v-col>
      <v-col cols="2">
        <div style="background-color: #ff3f41; border-radius: 12px" class="avatarContainer">
          <div v-for="player in redPlayers">
            <h5>{{ player.Name }}</h5>
            <v-avatar size="80" class="userAvatar" :class="{ host: player.IsHost == 1 }">
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
      Players: [],
      bluePlayers: [],
      redPlayers: [],
    }
  },
  methods: {
    Init() {
      let code = this.$router.currentRoute.params["id"];
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
                this.Players = data.Content.Players;
                this.isHost = data.Content.IsHost;
                this.Players.forEach(player => {
                  if (player.Team == "blue") {
                    this.bluePlayers.push(player);
                  } else {
                    this.redPlayers.push(player);
                  }
                });
                if (this.bluePlayers.length > 0 && this.redPlayers.length > 0) {
                  this.canStart = true;
                }
                this.$store.state.loading = false;
              }
            }
          });
    }
  },
  async created() {
    this.Init();
  },
  Stop() {
    this.eventSource.close();
  },
}

</script>

<style scoped>

</style>