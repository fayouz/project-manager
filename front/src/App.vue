<template>
  <div id="app">
    <Navbar v-on:input="search"></Navbar>
    <router-view v-bind:data="searchText" />
  </div>
</template>

<script>
import Navbar from './layout/Header'

export default {
  name: 'App',
  components: {
    Navbar
  },
  data() {
    return {
      searchText: ' '
    }
  },
  methods: {
    search: function(text) {
      this.searchText = text
    }
  }
}
</script>

<style>
body {
  background-color: rgb(240, 240, 240);
}
</style>
