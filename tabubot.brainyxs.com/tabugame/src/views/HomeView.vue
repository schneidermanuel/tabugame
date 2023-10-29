<template>
  <v-container class="container">
    <v-row class="verticalSpacer"/>
    <v-row>
      <v-spacer></v-spacer>
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
              >
                Host a Game
              </v-btn>
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
              >
                Join existing Game
              </v-btn>
            </v-col>
          </v-row>
        </v-container>
      </v-col>
      <v-spacer></v-spacer>
      <v-spacer></v-spacer>
    </v-row>
    <v-row class="verticalSpacer"/>
  </v-container>
</template>

<script>

export default {
  name: 'Home',
  methods:{
    LogOut()
    {
      this.$cookies.remove("jwt");
      this.$store.state.user.username = null;
      this.$store.state.user.pburl = null;
      this.$store.state.snackbar.timeout = 2000;
      this.$store.state.snackbar.color = "orange";
      this.$store.state.snackbar.message = "Logged out";
      this.$store.state.snackbar.show = true;

    }
  }

}
</script>


<style>

</style>