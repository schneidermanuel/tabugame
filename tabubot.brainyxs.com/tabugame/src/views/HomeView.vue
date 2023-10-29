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
                  block>
                <font-awesome-icon icon="fa-brands fa-discord"/>
                &emsp;Login with Discord
              </v-btn>
            </v-col>
          </v-row>
          <v-row>
            <v-col style="text-align: center;">
              <v-btn block
                     disabled
              >
                Host a Game
              </v-btn>
            </v-col>
          </v-row>
          <v-row>
            <v-col style="text-align: center;">
              <v-btn block
                     disabled
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

}
</script>


<style>
.verticalSpacer {
  height: 20%;
}

.container {
  height: 100%;
}
</style>