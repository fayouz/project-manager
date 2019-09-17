<template>
  <div class="task">
    <!-- TASK  -->
    <b-list-group-item
      class="d-flex px-2 align-items-center"
      v-bind:class="{ done: todo.finished }"
      @mouseenter="showTrash"
      @mouseleave="showTrash"
    >
      <label class="switch mr-2">
        <input
          type="checkbox"
          autocomplete="off"
          v-model="todo.finished"
          @change="setDone(todo.id, $event)"
        />
        <span class="slider round"></span>
      </label>

      <span @click="showModal(todo)" class="task">{{ todo.description }}</span>

      <font-awesome-icon
        v-show="trash"
        icon="times-circle"
        size="xs"
        class="delete"
        title="Supprimer"
        @click="deleteTask"
      />
    </b-list-group-item>
    <!-- TASK -->

    <!-- MODAL -->
    <b-modal ref="my-modal" hide-footer title="Modifier tâche">
      <div class="d-block text-center">
        <b-form-textarea
          v-on:keyup.enter="saveTask"
          v-model.trim="todo_edit.description"
          autofocus
        ></b-form-textarea>
      </div>
      <b-button class="mt-3" variant="outline-success" block @click="saveTask"
        >Modifier</b-button
      >
    </b-modal>
    <!-- FIN MODAL -->
  </div>
</template>

<script>
export default {
  name: 'task',

  props: ['todo'],

  data() {
    return {
      todo_edit: {
        description: null
      },
      trash: false
    }
  },

  methods: {
    showModal(todo) {
      this.todo_edit = todo
      this.$refs['my-modal'].show()
    },

    saveTask() {
      let data = this.todo_edit
      this.$http
        .patch(this.$base_url + 'todo/' + data.id, data)
        .then((response) => console.log(response))
      this.$refs['my-modal'].hide()
    },

    setDone(id, event) {
      let data = { finished: event.target.checked }
      this.$http.patch(this.$base_url + 'todo/' + id, data)
    },

    showTrash() {
      this.trash = this.trash ? 0 : 1
    },

    deleteTask() {
      this.$http.delete(this.$base_url + 'todo/' + this.todo.id)
      this.$emit('delete', this.todo.id)
    }
  }
}
</script>

<style lang="scss" scoped>
.done {
  text-decoration: line-through;
  color: grey;
}

.task {
  cursor: pointer;
}

.delete {
  position: absolute;
  right: 4px;
  top: 4px;
  color: grey;

  &:hover {
    cursor: pointer;
    color: red;
  }
}

//********** Switch box **********//

/* The switch - the box around the slider */
.switch {
  position: relative;
  display: inline-block;
  width: 20px;
  height: 11px;
  margin-bottom: 0;
}

/* Hide default HTML checkbox */
.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

/* The slider */
.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #4f85b1;
  -webkit-transition: 0.4s;
  transition: 0.4s;

  &:before {
    position: absolute;
    content: '';
    height: 13px;
    width: 13px;
    left: -1px;
    bottom: -1px;
    box-shadow: 0 0 10px rgb(158, 158, 158);
    background-color: white;
    -webkit-transition: 0.4s;
    transition: 0.4s;
  }

  /* Rounded sliders */
  &.round {
    border-radius: 34px;

    &:before {
      border-radius: 50%;
    }
  }
}

input {
  &:checked + .slider {
    background-color: grey;
  }

  &:focus + .slider {
    box-shadow: 0 0 1px grey;
  }

  &:checked + .slider:before {
    -webkit-transform: translateX(9px);
    -ms-transform: translateX(9px);
    transform: translateX(9px);
  }
}

//********** Switch box **********//
</style>
