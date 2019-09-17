<template>
  <div>
    <b-navbar toggleable="md" type="dark" variant="secondary">
      <b-navbar-brand :to="{ name: 'HelloWorld' }">Poc vue.js</b-navbar-brand>

      <b-navbar-toggle target="nav-collapse"></b-navbar-toggle>

      <b-collapse id="nav-collapse" is-nav>
        <b-navbar-nav>
          <b-nav-item v-bind:to="{ name: 'todoapp' }">TodoAPP</b-nav-item>
        </b-navbar-nav>

        <!-- Right aligned nav items -->
        <b-navbar-nav class="ml-auto">
          <b-nav-form>
            <b-form-input
              v-model.lazy="searchText"
              v-on:input="$emit('input', searchText)"
              size="sm"
              class="mr-sm-2"
              placeholder="Taper ici"
            ></b-form-input>
          </b-nav-form>
        </b-navbar-nav>
      </b-collapse>
    </b-navbar>
  </div>
</template>

<script>
export default {
  name: 'navbar',
  data() {
    return {
      brandName: 'TodoAPP',
      searchText: ''
    }
  }
}
</script>

<style lang="scss"></style>
