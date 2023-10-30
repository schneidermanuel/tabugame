<template>
  <v-container>
    <v-row class="verticalSpacer"/>
    <v-row class="verticalSpacer"/>
    <v-row>
      <v-col cols="12">
        <Loader/>
      </v-col>
    </v-row>
    <v-row class="verticalSpacer"/>
    <v-row class="verticalSpacer"/>
  </v-container>
</template>

<script>

import Loader from "@/components/Shared/Loader.vue";

export default {
  components: {
    Loader
  },
  methods: {
    init() {
      let token = this.$router.currentRoute.params["token"];
      this.$cookies.set("jwt", token)
      this.$store.state.loading = true;
      if (token != null) {
        fetch("https://api.tabubot.brainyxs.com/user/me", {
          method: 'GET',
          headers: {
            "Authorization": "Bearer " + token
          }
        }).then(data => data.json())
            .then(data => {
              if (data.authenticated) {
                this.$store.state.user.username = data.user.username;
                this.$store.state.user.pburl = data.user.pburl;
                this.$store.state.user.token = token;
                this.$store.state.loading = false;
                this.$router.push("/");
                this.$store.state.snackbar.timeout = 2000;
                this.$store.state.snackbar.color = "green";
                this.$store.state.snackbar.message = "Logged in";
                this.$store.state.snackbar.show = true;
              }
            });
      }
    }
  },
  async created() {
    this.init();
  }
}
</script>

<style scoped>

</style>