<template>
  <div class="todoapp px-4 pt-5">
    <InputText
      placeholder="Nouvelle liste"
      v-on:inputEvent="addList"
      class="w-50 mx-auto"
    ></InputText>

    <b-card-group deck class="align-items-center align-items-sm-start">
      <div v-for="(list, index) in lists" :key="list.id">
        <List
          v-bind:list="list"
          v-bind:index="index"
          v-on:position="updateList"
          v-on:deleteList="deleteList"
          ref="list"
          class="shadow mb-3 mx-3"
        ></List>
      </div>
    </b-card-group>
  </div>
</template>

<script>
import List from './../components/List'
import InputText from './../components/InputText'

export default {
  name: 'TodoApp',

  components: { List, InputText },

  data() {
    return {
      lists: []
    }
  },

  methods: {
    addList(e) {
      let data = {
        name: e,
        listOrder: this.lists.length + 1,
        toDos: []
      }

      let lastIndex = this.lists.push(data)

      this.$http.post(this.$base_url + 'todolist', data).then((response) => {
        this.lists[lastIndex - 1].id = response.data.id
      })
    },

    updateList(event) {
      this.$refs.list[event.index].updateTask()
    },

    deleteList(index) {
      let listIdDelete = this.lists[index].id
      this.lists.splice(index, 1)
      this.$http.delete(this.$base_url + 'todolist/' + listIdDelete)
    }
  },

  created() {
    this.$http.get(this.$base_url + 'todolists').then((result) => {
      this.lists = result.data
    })
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style></style>
