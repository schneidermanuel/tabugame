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
          <div v-for="player in lobby.bluePlayers">
            <h5>{{ player.Name }}</h5>
            <v-avatar size="80" class="userAvatar" :class="{ host: player.IsHost == 1 }">
              <img :src="player.ImageUrl"/>
            </v-avatar>
          </div>
        </div>
      </v-col>
      <v-col cols="8">
        CARD
      </v-col>
      <v-col cols="2">
        <div style="background-color: #ff3f41; border-radius: 12px" class="avatarContainer">
          <div v-for="player in lobby.redPlayers">
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

<script setup>
export default {
  data() {
    return {
      players: []
    }
  },
  methods: {
    Init() {
      let code = this.$router.currentRoute.params["id"];
      let token = this.$store.state.user.token;
    }
  },
  async created() {
    this.Init();
  }
}

</script>

<style scoped>

</style>