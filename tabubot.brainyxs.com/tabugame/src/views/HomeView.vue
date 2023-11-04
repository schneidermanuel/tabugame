<template>
  <v-container class="container">
    <v-row class="verticalSpacer"/>
    <v-row>
      <v-spacer></v-spacer>
      <v-col style="text-align: center">
        <v-container>
          <v-row>
            <v-col style="text-align: center">
              <v-btn
                  color="#5865f2"
                  style="color: white"
                  href="https://discord.com/api/oauth2/authorize?client_id=1120061782082453515&redirect_uri=https://api.tabubot.brainyxs.com/auth/callback&response_type=code&scope=identify"
                  block
                  v-if="this.$store.state.user.username == null">
                <font-awesome-icon icon="fa-brands fa-discord"/>
                &emsp;Login with Discord
              </v-btn>
              <v-btn
                  color="red"
                  style="color: white"
                  block
                  v-if="this.$store.state.user.username != null"
                  v-on:click="LogOut"
              >
                <font-awesome-icon icon="fa-brands fa-discord"/>
                Log out as {{ this.$store.state.user.username }}
              </v-btn>
            </v-col>
          </v-row>
          <v-row>
            <v-col style="text-align: center;">
              <v-btn block
                     disabled
                     v-show="this.$store.state.user.username == null"
              >
                Host a Game
              </v-btn>
              <v-btn block
                     v-show="this.$store.state.user.username != null"
                     v-on:click="toggleHostAGame"
              >
                Host a Game
              </v-btn>
              <host-a-game-form v-show="this.hostGame.formOpen" v-if="this.$store.state.user.username != null"></host-a-game-form>
            </v-col>
          </v-row>
          <v-row>
            <v-col style="text-align: center;">
              <v-btn block
                     disabled
                     v-show="this.$store.state.user.username == null"
              >
                Join existing Game
              </v-btn>
              <v-btn block
                     v-show="this.$store.state.user.username != null"
                     v-on:click="toggleJoinAGame"
              >
                Join existing Game
              </v-btn>
              <JoinAGame v-show="this.joinGame.formOpen"/>
            </v-col>
          </v-row>
        </v-container>
      </v-col>
      <v-spacer></v-spacer>
    </v-row>
    <v-row class="verticalSpacer"/>
  </v-container>
</template>
<script>

import HostAGameForm from "@/components/Home/HostAGameForm.vue";
import JoinAGame from "@/components/Home/JoinAGame.vue";

export default {
  name: 'Home',
  components: {JoinAGame, HostAGameForm},
  data() {
    return {
      hostGame: {
        formOpen: false
      },
      joinGame: {
        formOpen: false
      }
    }
  },
  methods: {
    LogOut() {
      this.$cookies.remove("jwt");
      this.$store.state.user.username = null;
      this.$store.state.user.pburl = null;
      this.$store.state.snackbar.timeout = 2000;
      this.$store.state.snackbar.color = "orange";
      this.$store.state.snackbar.message = "Logged out";
      this.$store.state.snackbar.show = true;
      this.hostGame.formOpen = false;

    },
    toggleHostAGame()
    {
      this.hostGame.formOpen = !this.hostGame.formOpen;
      this.joinGame.formOpen = false;
    },
    toggleJoinAGame()
    {
      this.joinGame.formOpen = !this.joinGame.formOpen;
      this.hostGame.formOpen = false;
    }
  }

}
</script>


<style>

</style>