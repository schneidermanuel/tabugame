<template>
  <div>
    <v-form>
      <div>

        <v-text-field label="Room-Code" style="width: 65%; display: inline-block" type="text"
                      v-model="code">
        </v-text-field>
        <v-btn style="width: 25%; margin-left: 5%"
               color="blue"
               v-on:click="JoinGame">
          <font-awesome-icon icon="play"/>
          Join Game
        </v-btn>
      </div>
    </v-form>
  </div>
</template>
<script>
export default {
  data() {
    return {
      code: null
    }
  },
  methods: {
    JoinGame() {
      let token = this.$store.state.user.token;
      if (!token) {
        this.$store.state.snackbar.color = "yellow";
        this.$store.state.snackbar.message = "Session expired, please log out";
        this.$store.state.snackbar.timeout = 3000;
        this.$store.state.snackbar.show = true;
        return;
      }
      if (!this.code) {
        this.$store.state.snackbar.color = "red";
        this.$store.state.snackbar.message = "Please insert a game code";
        this.$store.state.snackbar.timeout = 3000;
        this.$store.state.snackbar.show = true;
        return;
      }
      fetch("https://api.tabubot.brainyxs.com/game/" + this.code, {
        headers: {
          "Authorization": "Bearer " + token
        }
      })
          .then(data => data.json())
          .then(data => {
            if (!data.canJoin) {
              this.$store.state.snackbar.color = "red";
              this.$store.state.snackbar.message = "Invalid room code";
              this.$store.state.snackbar.timeout = 3000;
              this.$store.state.snackbar.show = true;
              this.code = null;
              return;
            }
            fetch("https://api.tabubot.brainyxs.com/game/" + this.code + "/join",
                {
                  method: "POST",
                  headers: {
                    "Authorization": "Bearer " + token
                  }
                })
                .then(data => data.json())
                .then(data => {
                  if (data.Status == "ERROR") {
                    this.$store.state.snackbar.message = data.Message;
                    this.$store.state.snackbar.color = "Red";
                    this.$store.state.snackbar.timeout = 5000;
                    this.$store.state.snackbar.show = true;
                    return;
                  }
                  this.$store.state.snackbar.message = data.Message;
                  this.$store.state.snackbar.color = "Green";
                  this.$store.state.snackbar.timeout = 3000;
                  this.$store.state.snackbar.show = true;

                  this.$router.push("/game/" + this.code);
                });
          })
    }
  }
}

</script>


<style scoped>

</style>