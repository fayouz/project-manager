import Vue from 'vue'
import Router from 'vue-router'
import TodoApp from '@/views/TodoApp'
import HelloWorld from '@/views/HelloWorld'

Vue.use(Router)

export default new Router({
  mode: 'history',
  base: process.env.BASE_URL,
  routes: [
    {
      path: '/',
      name: 'HelloWorld',
      component: HelloWorld
    },
    {
      path: '/todoapp/',
      name: 'todoapp',
      component: TodoApp
    }
  ]
})
