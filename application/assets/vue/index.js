import Vue from "vue";
import bootstrap from 'bootstrap';
import BootstrapVue from 'bootstrap-vue';
import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue/dist/bootstrap-vue.css'
import App from "./App";
import router from "./router";
import store from "./store";

Vue.use(BootstrapVue)




new Vue({
    components: { App },
    template: "<App/>",
    router,
    store
}).$mount("#app");