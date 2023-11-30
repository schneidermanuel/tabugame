<template>
  <v-container>
    <v-row>
      <h2 class="codeDisplay">Code: {{ code }}</h2>
    </v-row>
    <v-row>
      <v-col cols="12">
        <GameLobby ref="lobby" v-if="$store.state.isLobby"/>
        <GameMain v-if="$store.state.isGame" />
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import GameLobby from "@/components/Game/GameLobby.vue";
import GameMain from "@/components/Game/GameMain.vue";

export default {
  name: "GameView",
  components: {GameMain, GameLobby},
  data() {
    return {
      code: null
    }
  },
  methods: {
    init() {
      this.$store.state.loading = true;
      let code = this.$router.currentRoute.params["id"];
      this.code = code;
      fetch("https://api.tabubot.brainyxs.com/game/" + code + "/state", {
        method: 'GET',
        headers: {
          "Authorization": "Bearer " + this.$store.state.user.token
        }
      })
          .then(data => {
            if (!data.ok) {
              this.$router.push("/")
            }
            return data;
          })
          .then(data => data.json())
          .then(data => {
            let state = data.State;
            if (state == "OPEN") {
              this.$store.state.isLobby = true;
            }
            if (state == "GAME") {
              this.$store.state.isGame = true;
            }
            this.$store.state.loading = false;
          });
    }
  },
  async created() {
    this.init();
  },
  beforeRouteLeave(to, from, next) {
    if (this.$refs.lobby) {

      this.$refs.lobby.Stop();
    }
    next();
  }
}
</script>
<style>
.codeDisplay {
  font-family: Agbalumo;
  color: white;
}
</style>