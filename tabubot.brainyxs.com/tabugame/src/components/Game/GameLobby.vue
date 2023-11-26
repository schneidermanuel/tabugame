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
        <div style="background-color: #4fcaff; border-radius: 12px" class="avatarContainer">
          <draggable :list="lobby.bluePlayers" :group="isHost == 1 ? 'players' : null" @change="teamChanged">
            <div v-for="player in lobby.bluePlayers">
              <h5>{{ player.Name }}</h5>
              <v-avatar size="80" class="userAvatar" :class="{ host: player.IsHost == 1 }">
                <img :src="player.ImageUrl"/>
              </v-avatar>
            </div>
          </draggable>
        </div>
      </v-col>
      <v-spacer></v-spacer>
      <v-col cols="6" md="3" style="text-align: center">
        <h4>Team Red<br><br></h4>
        <div style="background-color: #ff3f41; border-radius: 12px" class="avatarContainer">
          <draggable :list="lobby.redPlayers" :group="isHost == 1 ? 'players' : null">
            <div v-for="player in lobby.redPlayers">
              <h5>{{ player.Name }}</h5>
              <v-avatar size="80" class="userAvatar" :class="{ host: player.IsHost == 1 }">
                <img :src="player.ImageUrl"/>
              </v-avatar>
            </div>
          </draggable>
        </div>
      </v-col>
    </v-row>
    <v-row>
      <v-spacer/>
      <v-col cols="1">
        <v-btn v-if="this.isHost == 1 && !this.canStart" disabled>
          Spiel starten
          <font-awesome-icon icon="play"/>
        </v-btn>
        <v-btn v-if="this.isHost == 1 && this.canStart" v-on:click="startGame">
          Spiel starten
          <font-awesome-icon icon="play"/>
        </v-btn>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>

import draggable from "vuedraggable";

export default {
  data() {
    return {
      lobby: {
        Players: [],
        bluePlayers: [],
        redPlayers: [],
      },
      isHost: false,
      canStart: false,
      eventSource: null
    }
  },
  components: {
    draggable
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
                this.isHost = data.Content.IsHost;
                this.lobby.Players.forEach(player => {
                  if (player.Team == "blue") {
                    this.lobby.bluePlayers.push(player);
                  } else {
                    this.lobby.redPlayers.push(player);
                  }
                });
                if (this.lobby.bluePlayers.length > 0 && this.lobby.redPlayers.length > 0) {
                  this.canStart = true;
                }
                this.$store.state.loading = false;
              }
              if (data.Type == "JOINED") {
                let player = data.Content;
                if (player.Team == "blue") {
                  this.lobby.bluePlayers.push(player);
                } else {
                  this.lobby.redPlayers.push(player);
                }
                if (this.lobby.bluePlayers.length > 0 && this.lobby.redPlayers.length > 0) {
                  this.canStart = true;
                }
              }
              if (data.Type == "TEAMCHANGE") {
                let player;
                this.lobby.bluePlayers.forEach(p => {
                  if (p.Id == data.Content.Player) {
                    player = p;
                    this.lobby.bluePlayers.splice(this.lobby.bluePlayers.indexOf(p), 1)
                  }
                });
                this.lobby.redPlayers.forEach(p => {
                  if (p.Id == data.Content.Player) {
                    player = p;
                    this.lobby.redPlayers.splice(this.lobby.redPlayers.indexOf(p), 1)
                  }
                });
                player.Team = data.Content.Team;
                if (data.Content.Team == "blue") {
                  this.lobby.bluePlayers.push(player);
                } else {
                  this.lobby.redPlayers.push(player);
                }
              }
              if (data.Type == "START") {
                this.InitGame();
              }
            }
          });
    },
    teamChanged() {
      let code = this.$router.currentRoute.params["id"]
      let token = this.$store.state.user.token;
      let newTeams = [];
      this.lobby.bluePlayers.forEach(player => newTeams.push({
        Id: player.Id,
        Team: "blue"
      }));
      this.lobby.redPlayers.forEach(player => newTeams.push({
        Id: player.Id,
        Team: "red"
      }));
      console.log(newTeams);
      fetch("https://api.tabubot.brainyxs.com/game/" + code + "/newTeams", {
        headers: {
          "Authorization": "Bearer " + token
        },
        method: "POST",
        body: JSON.stringify(newTeams)
      })
          .then(data => data.json())
          .then(data => {
            if (data.Status == "OK") {
              this.$store.state.snackbar.color = "green";
            }
            if (data.Status == "ERROR") {
              this.$store.state.snackbar.color = "red";
            }
            this.$store.state.snackbar.message = data.Message;
            this.$store.state.snackbar.timeout = 1000;
            this.$store.state.snackbar.show = true;
          })
    },
    Stop() {
      this.eventSource.close();
    },
    startGame() {
      let code = this.$router.currentRoute.params["id"]
      let token = this.$store.state.user.token;
      console.log(token);
      fetch("https://api.tabubot.brainyxs.com/game/" + code + "/start", {
        headers: {
          "Authorization": "Bearer " + token
        },
        method: "POST"
      })
          .then(data => data.json())
          .then(data => {
            if (data.Status == "OK") {
              this.$store.state.snackbar.color = "green";
            }
            if (data.Status == "ERROR") {
              this.$store.state.snackbar.color = "red";
            }
            this.$store.state.snackbar.message = data.Message;
            this.$store.state.snackbar.timeout = 5000;
            this.$store.state.snackbar.show = true;
          });
    },
    InitGame() {

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
  margin-bottom: 15%;
}

.avatarContainer {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-height: 100px;
}

.host {
  border: #ffbf00 5px solid;
}
</style>