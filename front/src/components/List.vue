<template>
  <div class="list">
    <b-card no-body class="w-100 m-0" headerClass="bg-dark" header="Dark">
      <!-- HEADER -->
      <div slot="header" class="text-center text-light mb-0">
        <span class="h6">
          {{ list.name }}
        </span>
        <b-dropdown
          id="dropdown-dropup"
          dropup
          no-caret
          variant="dark"
          class="float-right position-absolute menu"
        >
          <template slot="button-content">
            <font-awesome-icon
              icon="ellipsis-v"
              size="1x"
              class="text-light"
              title="Ajouter"
            />
          </template>
          <b-dropdown-item @click="showModal(list.name, 'edit')"
            >Renonner</b-dropdown-item
          >
          <b-dropdown-item @click="showModal('', 'delete')"
            >Supprimer</b-dropdown-item
          >
        </b-dropdown>
      </div>
      <!-- HEADER -->

      <!-- ADD TASK -->
      <b-input-group>
        <b-form-input
          placeholder="Ajouter une tâche"
          class="border-0 new-task"
          v-model="text"
          @keyup.enter="newTask"
        ></b-form-input>
      </b-input-group>
      <!-- ADD TASK -->

      <!-- TASK -->
      <draggable
        v-model="list.toDos"
        v-bind="dragOptions"
        @change="changePosition(listId, $event)"
        emptyInsertThreshold="10"
      >
        <transition-group type="transition" :name="'flip-list'">
          <b-list-group
            flush
            v-for="todo in list.toDos"
            :key="todo.id"
            class="p-0"
          >
            <Task v-bind:todo="todo" v-on:delete="deleteTask" />
          </b-list-group>
        </transition-group>
      </draggable>
      <!-- TASK -->
    </b-card>

    <!-- MODAL -->
    <b-modal ref="list-edit-modal" hide-footer size="md" :title="modalTitle">
      <div class="d-block text-center">
        <b-form-input
          v-on:keyup.enter="saveList"
          v-model.trim="listName"
          v-show="modalTitle == 'Modifier'"
          autofocus
        ></b-form-input>
      </div>
      <b-button
        class="mt-3"
        variant="success"
        v-show="modalTitle == 'Modifier'"
        @click="saveList"
        block
        >Modifier</b-button
      >
      <b-button
        class="mt-3"
        variant="danger"
        v-show="modalTitle == 'Confirmer supression'"
        @click="deleteList"
        block
        >Confirmer</b-button
      >
    </b-modal>
    <!-- MODAL -->
  </div>
</template>

<script>
import Task from './Task'
import draggable from 'vuedraggable'
import _ from 'lodash'

export default {
  name: 'List',

  props: ['list', 'index'],

  components: { Task, draggable },

  data() {
    return {
      text: '',
      listId: this.list.id,
      listName: '',
      modalTitle: ''
    }
  },

  computed: {
    dragOptions() {
      return {
        animation: 100,
        group: 'task',
        disabled: false,
        ghostClass: 'ghost'
      }
    }
  },

  created() {
    this.list.toDos = _.orderBy(this.list.toDos, 'todoOrder')
  },

  methods: {
    newTask(event) {
      if (event.target.value.trim().length >= 1) {
        let data = {
          description: event.target.value,
          finished: false,
          list: this.list.id,
          todoOrder: this.list.toDos.length + 1,
          id: 0
        }

        this.text = ''

        this.$http.post(this.$base_url + 'todo', data).then((response) => {
          this.list.toDos.push(response.data)
        })
      }
    },

    deleteTask(taskId) {
      let todoToDelete = this.list.toDos.findIndex((todo) => {
        return todo.id === taskId
      })

      this.list.toDos.splice(todoToDelete, 1)

      this.savePosition()
    },

    changePosition(listId, event) {
      if (Object.keys(event)[0] === 'moved') {
        this.updateTask()
      }

      if (Object.keys(event)[0] === 'added') {
        let added = {
          listId: listId,
          index: this.index
        }
        this.$emit('position', added)
      }

      // if (Object.keys(event)[0] === 'removed') {
      //   let removed = {
      //     listId: listId,
      //     index: this.index
      //   }
      //   this.$emit('position', removed)
      // }
    },

    savePosition() {
      this.list.toDos.forEach((todo, index) => {
        todo.todoOrder = index + 1
        console.log('savePosition')
      })
    },

    updateTask() {
      this.savePosition()
      this.$http
        .patch(
          this.$base_url + 'todos/position/save/' + this.list.id,
          this.list.toDos
        )
        .then((response) => {
          console.log(response)
        })
    },

    showModal(listName, mode) {
      this.modalTitle = mode === 'edit' ? 'Modifier' : 'Confirmer supression'

      if (mode === 'edit') {
        this.listName = listName
        this.$refs['list-edit-modal'].show()
      } else if (mode === 'delete') {
        this.$refs['list-edit-modal'].show()
      }
    },

    saveList() {
      if (this.listName.length > 1) {
        this.list.name = this.listName
        let data = {
          name: this.listName
        }
        this.$http
          .patch(this.$base_url + 'todolist/' + this.list.id, data)
          .then((response) => {
            this.$refs['list-edit-modal'].hide()
            this.listName = ''
            this.modalTitle = ''
            console.log(response)
          })
      }
    },

    deleteList() {
      this.$emit('deleteList', this.index)
    }
  }
}
</script>

<style lang="scss">
.list {
  min-width: 250px;
  max-width: 480px;
}

.menu {
  top: 0;
  right: 0;
}

.add-icon {
  color: #4f85b1;
}

.new-task {
  background: -moz-linear-gradient(
    top,
    rgba(0, 0, 0, 0.05) 0%,
    rgba(0, 0, 0, 0) 100%
  ); /* FF3.6-15 */
  background: -webkit-linear-gradient(
    top,
    rgba(0, 0, 0, 0.05) 0%,
    rgba(0, 0, 0, 0) 100%
  ); /* Chrome10-25,Safari5.1-6 */
  background: linear-gradient(
    to bottom,
    rgba(0, 0, 0, 0.05) 0%,
    rgba(0, 0, 0, 0) 100%
  ); /* W3C, IE10+, FF16+, Chrome26+, Opera12+, Safari7+ */
  filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#a6000000', endColorstr='#00000000',GradientType=0 ); /* IE6-9 */
}

.card {
  .form-control:focus {
    box-shadow: none;
  }
}
</style>
