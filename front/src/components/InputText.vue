<template>
  <div class="input-field" :class="{ focus }">
    <input
      type="text"
      :placeholder="placeholder"
      v-model="value"
      @focus="inputEvent"
      @focusout="inputEvent"
      @keyup="inputEvent"
      @keyup.enter="emitList"
    />
  </div>
</template>

<script>
export default {
  name: 'inputText',

  props: ['placeholder'],

  data() {
    return {
      focus: false,
      value: ''
    }
  },

  methods: {
    inputEvent(e) {
      if ((e.type === 'focus' || e.type === 'keyup') && this.value.length > 0) {
        this.focus = true
      } else {
        this.focus = false
      }
    },

    emitList(e) {
      if (e.target.value.trim().length >= 1) {
        this.$emit('inputEvent', this.value)
        this.value = ''
      }
    }
  }
}
</script>

<style lang="scss">
$primary: rgb(87, 87, 87);
$accent: #52acff;

*,
*::before,
*::after {
  box-sizing: border-box;
}

.input-field {
  position: relative;
  padding: 5px 0;
  box-shadow: 0 0 49px white;
  background-color: white;
  border-radius: 10px;
  font-size: 2em;
  margin-bottom: 2em;

  &::before,
  &::after {
    content: ' ';
    position: absolute;
    bottom: 0;
    border-bottom: 1px $primary solid;
    transition: 0.5s;
  }

  &::after {
    left: 50%;
    right: 50%;
    border-bottom: 3px $accent solid;
  }

  &::before,
  &.focus::after {
    left: 5px;
    right: 5px;
  }

  input[type='text'] {
    border: 0;
    outline: 0;
    background-color: transparent;
    font-family: 'San Francisco', 'Segoe UI', 'Roboto', sans-serif;
    font-size: 1em;
    width: 100%;
    color: $primary;
    padding-left: 1em;
  }

  &::placeholder {
    color: rgba(88, 88, 88, 0.5);
  }
}
</style>
