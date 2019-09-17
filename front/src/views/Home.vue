<template>
  <div>
    <v-app-bar :collapse="!collapse" :collapse-on-scroll="collapse" fixed>
      <v-toolbar-title class="headline text-uppercase">
        <span>Vuetify</span>
        <span class="font-weight-light">MATERIAL DESIGN</span>
      </v-toolbar-title>
      <v-spacer></v-spacer>
      <v-btn text :to="{ name: 'about' }">
        <span class="mr-2">Layout</span>
        <v-icon>fas fa-external-link-alt</v-icon>
      </v-btn>
    </v-app-bar>
    <HelloWorld />
  </div>
</template>

<script>
import HelloWorld from '../components/HelloWorld'

export default {
  components: {
    HelloWorld
  },
  data: () => ({
    collapse: true
  })
}
</script>
