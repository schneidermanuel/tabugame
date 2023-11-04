<template>
  <v-container>
    <v-row>
      <h2 class="codeDisplay">Code: {{ code }}</h2>
    </v-row>
    <v-row>
      <v-col cols="12">
        <GameLobby v-if="state.isLobby"/>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import GameLobby from "@/components/Game/GameLobby.vue";

export default {
  name: "GameView",
  components: {GameLobby},
  data() {
    return {
      state: {
        isLobby: false,
        isGame: false
      },
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
            let players = data.Players;
            if (state == "OPEN")
            {
              this.state.isLobby = true;
            }
            if (state == "GAME")
            {
              this.state.isGame = true;
            }
            this.$store.state.loading = false;
          });
    }
  },
  async created() {
    this.init();
  }
}
</script>
<style>
.codeDisplay {
  font-family: Agbalumo;
  color: white;
}
</style>