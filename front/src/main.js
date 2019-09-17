import Vue from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import BootstrapVue from 'bootstrap-vue'
import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue/dist/bootstrap-vue.css'
import axios from 'axios'
import { library } from '@fortawesome/fontawesome-svg-core'
import {
  faTimesCircle,
  faPlusCircle,
  faEllipsisV
} from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import VueLodash from 'vue-lodash'
import VueSwal from 'vue-swal'

Vue.config.productionTip = false

library.add(faTimesCircle, faPlusCircle, faEllipsisV)

Vue.component('font-awesome-icon', FontAwesomeIcon)

Vue.prototype.$http = axios // Import axios globalement
Vue.prototype.$base_url =
  'http://localhost/Projects/TodoList-Vue.js-poc/API/public/index.php/'

Vue.use(BootstrapVue)
Vue.use(VueLodash)
Vue.use(VueSwal)

new Vue({
  router,
  store,
  render: (h) => h(App)
}).$mount('#app')
