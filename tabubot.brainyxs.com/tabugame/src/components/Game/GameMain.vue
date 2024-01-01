<template>
  <v-container style="font-family: Agbalumo; color: white; font-size: 120%">
    <v-row>
      <v-col cols="2">
        Blue: 0 Points
      </v-col>
      <v-col cols="8" class="text-center">
        {{ currentPlayername }}'s Turn
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
      <v-col cols="8" class="text-center cardcontainer">
        <div class="card" :class="{ hidden: !Card.Visible }">
          <h2>{{ Card.Title }}</h2>
          <p><br><br></p>
          <p>{{ Card.Word1 }}</p>
          <p>{{ Card.Word2 }}</p>
          <p>{{ Card.Word3 }}</p>
          <p>{{ Card.Word4 }}</p>
        </div>
        <div class="actions">
          <div class="time">{{ Timer.Display }}</div>
          <v-btn color="green" v-if="isMyTurn && !isInTurn" v-on:click="startTurnCommand">
            <font-awesome-icon icon="play"/>
            Start!
          </v-btn>
          <div v-if="isMyTurn && isInTurn">
            <v-btn color="red">
              <font-awesome-icon icon="trash"/>
              Skip card
            </v-btn>
            <v-btn color="green">
              <font-awesome-icon icon="check"/>
              Correct!
            </v-btn>
          </div>
        </div>
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
      currentPlayername: null,
      isMyTurn: false,
      isInTurn: false,
      Card: {
        Visible: false,
        Title: "Testwert",
        Word1: "Testwert",
        Word2: "Testwert",
        Word3: "Testwert",
        Word4: "Testwert"
      },
      Timer: {
        IsActive: false,
        InitialTimestamp: null,
        Display: "2:00"
      }
    }
  },
  methods: {
    Init() {
      this.$store.state.loading = true;
      let code = this.$router.currentRoute.params["id"];
      let token = this.$store.state.user.token;
      fetch("https://api.tabubot.brainyxs.com/game/" + code + "/otp", {
        headers: {
          "Authorization": "Bearer " + token
        }
      })
          .then(data => data.text())
          .then(otp => {

            let url = "https://api.tabubot.brainyxs.com/ingame/" + code + "/events/" + otp;
            this.eventSource = new EventSource(url);
            this.eventSource.onmessage = (e) => {
              let data = JSON.parse(e.data);
              if (data.Type == "INFO") {
                console.log(e.data);
              }
              if (data.Type == "INIT") {
                this.Players = data.Content.Players;
                this.isHost = data.Content.IsHost;
                this.isMyTurn = data.Content.IsMyTurn;
                this.Players.forEach(player => {
                  if (player.Team == "blue") {
                    this.bluePlayers.push(player);
                  } else {
                    this.redPlayers.push(player);
                  }
                });
                if (data.Content.Timestamp) {
                  this.StartTimer(data.Content.Timestamp);
                }
                if (data.Content.Card) {
                  this.Card.Visible = this.isMyTurn && this.Timer.IsActive;
                  this.Card.Title = data.Content.Card.CardWord;
                  this.Card.Word1 = data.Content.Card.Word1;
                  this.Card.Word2 = data.Content.Card.Word2;
                  this.Card.Word3 = data.Content.Card.Word3;
                  this.Card.Word4 = data.Content.Card.Word4;
                }
                if (this.bluePlayers.length > 0 && this.redPlayers.length > 0) {
                  this.canStart = true;
                }
                this.$store.state.loading = false;
              }
              if (data.Type == "TURNSTART") {
                let playerId = data.Content.PlayerId;
                let player = this.Players.filter(p => p.Id == playerId)[0];
                this.currentPlayername = player.Name;
                this.isMyTurn = data.Content.IsMyTurn;
              }
              if (data.Type == "TIMERSTART") {
                let timestamp = data.Content.Timestamp;
                this.StartTimer(timestamp);
              }
              if (data.Type == "CARDDISPLAY") {
                this.Card.Visible = this.isMyTurn && this.Timer.IsActive;
                this.Card.Title = data.Content.CardWord;
                this.Card.Word1 = data.Content.Word1;
                this.Card.Word2 = data.Content.Word2;
                this.Card.Word3 = data.Content.Word3;
                this.Card.Word4 = data.Content.Word4;
              }
            }
          });
    },
    startTurnCommand() {
      let code = this.$router.currentRoute.params["id"];
      let token = this.$store.state.user.token;
      fetch("https://api.tabubot.brainyxs.com/ingame/" + code + "/startturn", {
        method: "POST",
        headers: {
          "Authorization": "Bearer " + token
        }
      })
          .then(data => data.json())
          .then(data => {
            if (data.Status == "ERROR") {
              this.$store.state.snackbar.color = "red";
            }
            this.$store.state.snackbar.message = data.Message;
            this.$store.state.snackbar.timeout = 1000;
            this.$store.state.snackbar.show = true;
          });

    },
    StartTimer(timestamp) {
      this.isInTurn = true;
      this.Timer.IsActive = true;
      this.Timer.InitialTimestamp = timestamp;
      let ellapsed = (Date.now() - new Date(this.Timer.InitialTimestamp * 1000)) / 1000;
      if (ellapsed > 2 * 60) {
        this.Timer.IsActive = false;
      }
      console.log("Start sä timer")
      this.Tick();
    },
    Tick() {
      let ellapsed = (Date.now() - new Date(this.Timer.InitialTimestamp * 1000)) / 1000;
      if (ellapsed > 2 * 60) {
        this.Timer.IsActive = false;
        this.CompleteTimer();
      }
      if (this.Timer.IsActive) {
        let minutesEllapsed = Math.floor(ellapsed / 60);
        let secondsEllapsed = Math.floor(ellapsed % 60);
        let minutesDisplay = (1 - minutesEllapsed).toLocaleString("de-CH", {
          minimumIntegerDigits: 1
        });
        let secondsDisplay = (60 - secondsEllapsed).toLocaleString("de-CH", {
          minimumIntegerDigits: 2
        });
        this.Timer.Display = minutesDisplay + ":" + secondsDisplay;
        window.setTimeout(() => this.Tick(), 1000);
      }
    },
    CompleteTimer() {
      let code = this.$router.currentRoute.params["id"];
      let token = this.$store.state.user.token;
      fetch("https://api.tabubot.brainyxs.com/ingame/" + code + "/stopTurn", {
        method: "POST",
        headers: {
          "Authorization": "Bearer " + token
        },
        body: JSON.stringify({
          "TimerTimestamp": this.Timer.InitialTimestamp
        })
      })
          .then(data => data.json())
          .then(data => {

          });
    },
    Stop() {
      this.eventSource.close();
    }
  }
  ,
  async created() {
    this.Init();
  }
  ,
}

</script>

<style scoped>

.cardcontainer {
  display: flex;
  width: 100%;
  justify-content: center;
  flex-direction: column;
  align-items: center;
}

.card {
  padding-top: 20px;
  background-color: #9fff5e;
  aspect-ratio: 0.8;
  width: clamp(2.5rem, 50%, 20rem);
  border-radius: 6px;
  text-align: center;
  color: #3d424a;
}

.hidden {
  visibility: hidden;
}

.actions {
  display: flex;
  justify-content: center;
}

.actions * {
  margin: 8px;
}
</style>