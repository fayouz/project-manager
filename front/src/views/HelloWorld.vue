<template>
  <div class="hello">
    <h1>
      Ceci est une propriété avec 2WaysBindings :
      <span class="prop">{{ data }}</span>
    </h1>
    <b-button variant="success" @click="alert">Button</b-button>
  </div>
</template>

<script>
export default {
  name: 'HelloWorld',
  data() {
    return {
      prenom: 'Antoine',
      objet: 'chocolat'
    }
  },
  props: ['data'],
  methods: {
    alert() {
      this.$swal('Confirmer suppression!', '', 'error',{dangerMode: true, buttons:['Annuler', 'Confirmer']})
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
h1,
h2 {
  text-align: center;
  font-weight: 600;
}
ul {
  list-style-type: none;
  padding: 0;
}
li {
  display: inline-block;
  margin: 0 10px;
}
a {
  color: #42b983;
}
.hello {
  margin: 10em 0;
}
.prop {
  background-color: cornflowerblue;
  border-radius: 10px;
}
</style>
