<template>
  <v-form>
    <div>
      <v-select
          v-model="selected"
          :items="myGames"
          label="Select"
          v-show="mode.SelectMine"
          style="width: 75%; display: inline-block"
      ></v-select>
      <v-text-field
          label="Set-Id"
          style="width: 75%; display: inline-block"
          type="text"
          v-model="inputId"
          v-show="!this.mode.SelectMine"
      ></v-text-field>
      <v-btn style="width: 15%; margin-left: 5%"
             text
             v-on:click="ToggleMode">
        {{ mode.Text }}
      </v-btn>
    </div>
    <v-btn
        color="blue"
        block
        v-on:click="submit">
      <font-awesome-icon icon="play"/>
      Create game
    </v-btn>

  </v-form>
</template>

<script>

export default {
  data() {
    return {
      myGames: [],
      games: null,
      selected: null,
      inputId: null,
      mode: {
        SelectMine: true,
        Text: "Mine"
      }
    }
  },
  methods: {
    ToggleMode() {
      if (this.mode.SelectMine) {
        this.mode.SelectMine = false;
        this.mode.Text = "All"
      } else {
        this.mode.SelectMine = true;
        this.mode.Text = "Mine"
      }
    },
    Init() {
      this.$store.state.loading = true;
      let token = this.$store.state.user.token;
      if (!token) {
        return;
      }
      fetch("https://api.tabubot.brainyxs.com/user/sets", {
        method: 'GET',
        headers: {
          "Authorization": "Bearer " + token
        }
      }).then(data => data.json())
          .then(data => {
            data.forEach(item => this.myGames.push(item.Id + ") " + item.Name));
            this.games = data;
            this.$store.state.loading = false;
          });
    },
    submit() {
      let selectedSetId = this.inputId;
      if (this.mode.SelectMine === true && this.selected) {
        selectedSetId = this.selected.split(')')[0];
      }
      if (!selectedSetId) {
        this.$store.state.snackbar.color = "red";
        this.$store.state.snackbar.message = "No Cardset selected";
        this.$store.state.snackbar.timeout = 3500;
        this.$store.state.snackbar.show = true;
        return;
      }
      fetch("https://api.tabubot.brainyxs.com/cardset/id/" + selectedSetId, {
        method: "GET",
        headers: {
          "Authorization": "Bearer " + this.$store.state.user.token
        }
      })
          .then(data => data.json())
          .then(data => {
            if (!data.Id) {
              this.$store.state.snackbar.color = "red";
              this.$store.state.snackbar.message = "The selected Cardset could not be found";
              this.$store.state.snackbar.timeout = 3500;
              this.$store.state.snackbar.show = true;
              return;
            }
            this.$store.state.loading = true;
            fetch("https://api.tabubot.brainyxs.com/game/create", {
              method: "POST",
              headers: {
                "Authorization": "Bearer " + this.$store.state.user.token
              },
              body: JSON.stringify({
                SET_ID: selectedSetId
              })

            })
                .then(data => {
                  if (!data.ok) {
                    this.$store.state.snackbar.color = "red";
                    this.$store.state.snackbar.message = "The game could not be created";
                    this.$store.state.snackbar.timeout = 3500;
                    this.$store.state.snackbar.show = true;
                    return;
                  }
                  return data;
                })
                .then(data => data.json())
                .then(data => {
                  const gameCode = data.Game;
                  this.$router.push("/game/" + gameCode);
                  this.$store.state.loading = false;
                });
          });
    }
  },
  async created() {
    this.Init();
  }
}
</script>

<style scoped>
</style>