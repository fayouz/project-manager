import Vue from "vue";
import Vuex from "vuex";
import ProjectModule from "./project";

Vue.use(Vuex);

export default new Vuex.Store({
    modules: {
        project: ProjectModule
    }
});