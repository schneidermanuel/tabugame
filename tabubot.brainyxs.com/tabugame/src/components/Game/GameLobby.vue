<template>

</template>

<script>

export default {
  methods: {
    init() {
      let code = this.$router.currentRoute.params["id"]
      let token = this.$store.state.user.token;
      let url = "https://api.tabubot.brainyxs.com/game/" + code + "/lobby";
      console.log(url);
      const eventSource = new EventSource(url);
      eventSource.onmessage = (e) => {
        console.log(e.data);
      }
    }
  },
  async created() {
    this.init();
  }
}
</script>

<style>

</style>